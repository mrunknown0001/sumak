<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Course;
use App\Models\Document;
use App\Models\ObtlDocument;
use App\Jobs\ExtractObtlDocumentJob;
use App\Jobs\ProcessDocumentJob;
use App\Services\DocumentQuizBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportRedirects\Redirector;

class CourseDetail extends Component
{
    use WithFileUploads;

    protected DocumentQuizBatchService $documentQuizBatchService;

    public Course $course;
    public bool $isEnrolled = false;
    public array $documentBatchMeta = [];
    public ?array $activeQuizBatch = null;

    public $obtlUpload;

    public $newMaterialUpload;
    public $newMaterial = [
        'title' => '',
        'lecture_number' => '',
        'hours_taught' => null,
    ];

    public function boot(DocumentQuizBatchService $documentQuizBatchService): void
    {
        $this->documentQuizBatchService = $documentQuizBatchService;
    }

    public function mount(Course $course): void
    {
        $this->course = $course->load('obtlDocument');
        $this->isEnrolled = $course->isEnrolledBy(auth()->id());

        if (! $this->isEnrolled) {
            redirect()->route('student.courses')
                ->with('error', 'You must enroll in this course first.')
                ->send();
        }
    }

    public function uploadObtl(): void
    {
        $this->ensureCanManageCourse();

        if ($this->course->obtlDocument) {
            $this->addError('obtlUpload', 'An OBTL document has already been uploaded for this course.');
            return;
        }

        $this->validate(
            $this->obtlValidationRules(),
            $this->obtlValidationMessages()
        );

        $relativePath = null;
        $obtlDocument = null;

        DB::beginTransaction();

        try {
            $relativePath = $this->obtlUpload->store('obtl_documents', 'private');
            $absolutePath = Storage::disk('private')->path($relativePath);

            $obtlDocument = ObtlDocument::create([
                'course_id' => $this->course->id,
                'user_id' => auth()->id(),
                'title' => $this->obtlUpload->getClientOriginalName(),
                'file_path' => $absolutePath,
                'file_type' => $this->obtlUpload->getClientOriginalExtension() ?: 'pdf',
                'file_size' => $this->obtlUpload->getSize(),
                'uploaded_at' => now(),
                'processing_status' => ObtlDocument::PROCESSING_PENDING,
            ]);

            $this->course->update([
                'workflow_stage' => Course::WORKFLOW_STAGE_OBTL_UPLOADED,
                'obtl_uploaded_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            if ($relativePath) {
                Storage::disk('private')->delete($relativePath);
            }

            report($exception);

            $this->addError('obtlUpload', 'Failed to upload the OBTL document. Please try again.');
            return;
        }

        ExtractObtlDocumentJob::dispatch($obtlDocument->id);

        $this->reset('obtlUpload');
        $this->refreshCourseState();

        session()->flash('message', 'OBTL document uploaded successfully. Extraction has started.');
    }

    public function uploadMaterial(): void
    {
        $this->ensureCanManageCourse();

        $obtlDocument = $this->course->obtlDocument;

        if (! $obtlDocument || $obtlDocument->processing_status !== ObtlDocument::PROCESSING_COMPLETED) {
            $this->addError('newMaterialUpload', 'OBTL extraction must complete before uploading learning materials.');
            return;
        }

        $this->validate(
            $this->materialValidationRules(),
            $this->materialValidationMessages()
        );

        $relativePath = null;
        $document = null;

        DB::beginTransaction();

        try {
            $relativePath = $this->newMaterialUpload->store('documents', 'public');
            $absolutePath = Storage::disk('public')->path($relativePath);

            $document = Document::create([
                'course_id' => $this->course->id,
                'user_id' => auth()->id(),
                'title' => $this->newMaterial['title'],
                'file_path' => $absolutePath,
                'file_type' => $this->newMaterialUpload->getClientOriginalExtension() ?: 'pdf',
                'file_size' => $this->newMaterialUpload->getSize(),
                'uploaded_at' => now(),
                'processing_status' => Document::PROCESSING_PENDING,
            ]);

            $this->course->update([
                'workflow_stage' => Course::WORKFLOW_STAGE_MATERIALS_UPLOADED,
                'materials_uploaded_at' => $this->course->materials_uploaded_at ?? now(),
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            if ($relativePath) {
                Storage::disk('public')->delete($relativePath);
            }

            report($exception);

            $this->addError('newMaterialUpload', 'Failed to upload the learning material. Please try again.');
            return;
        }

        ProcessDocumentJob::dispatch($document->id, [
            'lecture_number' => $this->newMaterial['lecture_number'] ?: null,
            'hours_taught' => $this->newMaterial['hours_taught'] ?: null,
            'has_obtl' => true,
        ]);

        $this->reset('newMaterialUpload');
        $this->resetNewMaterialForm();
        $this->refreshCourseState();

        session()->flash('message', 'Learning material uploaded successfully. Processing has started.');
    }

    public function startMaterialQuizBatch(int $documentId): RedirectResponse|Redirector|null
    {
        $userId = auth()->id();

        $document = $this->course->documents()
            ->with([
                'topics' => function ($topicQuery) use ($userId) {
                    $topicQuery
                        ->withCount('items')
                        ->withCount([
                            'quizAttempts as user_attempts_count' => function ($attemptQuery) use ($userId) {
                                $attemptQuery->where('user_id', $userId);
                            },
                        ]);
                },
            ])
            ->find($documentId);

        if (! $document) {
            session()->flash('error', 'Learning material not found.');
            return null;
        }

        $eligibleTopics = $this->documentQuizBatchService->eligibleSubtopicsForUser($document, $userId);

        if ($eligibleTopics->isEmpty()) {
            session()->flash('error', 'No eligible quizzes remain for this learning material.');
            return null;
        }

        $this->documentQuizBatchService->initialiseBatchSession($document, $eligibleTopics);

        $firstTopicId = (int) $eligibleTopics->first()->id;
        session()->put('quiz.context.topic', $firstTopicId);

        return redirect()->route('student.quiz.take', $firstTopicId);
    }

    public function continueBatch(int $topicId): RedirectResponse|Redirector|null
    {
        $batch = $this->documentQuizBatchService->currentBatch();

        if (! $batch || empty($batch['queue']) || ! in_array($topicId, $batch['queue'], true)) {
            session()->flash('error', 'Quiz batch session is no longer available.');
            return null;
        }

        session()->put('quiz.context.topic', $topicId);

        return redirect()->route('student.quiz.take', $topicId);
    }

    public function render()
    {
        $this->course->refresh()->load('obtlDocument');

        $userId = auth()->id();

        $documents = $this->course->documents()
            ->with([
                'tableOfSpecification',
                'topics' => function ($topicQuery) use ($userId) {
                    $topicQuery->withCount('items')
                            ->withCount([
                                'quizAttempts as user_attempts_count' => function ($attemptQuery) use ($userId) {
                                    $attemptQuery->where('user_id', $userId);
                                },
                            ]);
                    },
                ])
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->get();

        $activeBatch = $this->documentQuizBatchService->currentBatch();
        $this->activeQuizBatch = $activeBatch;
        $this->documentBatchMeta = $this->buildDocumentBatchMeta($documents, $userId, $activeBatch)->toArray();

        return view('livewire.course-detail', [
            'documents' => $documents,
            'documentBatchMeta' => $this->documentBatchMeta,
            'activeQuizBatch' => $this->activeQuizBatch,
            'maxAttempts' => (int) config('quiz.max_attempts', 3),
        ])->layout('layouts.app', [
            'title' => 'SumakQuiz | ' . $this->course->course_code,
            'pageTitle' => $this->course->course_title,
            'pageSubtitle' => $this->course->course_code . ' • Workflow: '
                . Str::headline($this->course->workflow_stage ?? Course::WORKFLOW_STAGE_DRAFT)
                . ' • Lecture materials and quiz readiness status.',
        ]);
    }

    protected function buildDocumentBatchMeta(Collection $documents, int $userId, ?array $activeBatch): Collection
    {
        $maxAttempts = (int) config('quiz.max_attempts', 3);
        $activeDocumentId = $activeBatch['document_id'] ?? null;
        $activeQueue = collect($activeBatch['queue'] ?? []);

        return $documents->map(function (Document $document) use ($maxAttempts, $activeDocumentId, $activeQueue) {
            $topics = $document->topics
                ->flatMap(fn ($topic)=> [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'items_count' => $topic->items_count ?? 0,
                    'user_attempts_count' => $topic->user_attempts_count ?? 0,
                    'can_retake' => ($topic->items_count ?? 0) > 0
                        && (($topic->user_attempts_count ?? 0) < $maxAttempts),
                ]);

            $eligibleCount = $topics->where('can_retake', true)->count();
            $inBatchQueue = $activeDocumentId === $document->id;

            return [
                'document_id' => $document->id,
                'eligible_quiz_count' => $eligibleCount,
                'in_active_batch' => $inBatchQueue,
                'active_queue' => $inBatchQueue ? $activeQueue->values()->all() : [],
                'total_topics' => $topics->count(),
            ];
        });
    }

    protected function refreshCourseState(): void
    {
        $this->course = $this->course->fresh([
            'obtlDocument',
        ]);
    }

    protected function resetNewMaterialForm(): void
    {
        $this->newMaterial = [
            'title' => '',
            'lecture_number' => '',
            'hours_taught' => null,
        ];
    }

    protected function ensureCanManageCourse(): void
    {
        if (! $this->canManageCourse) {
            abort(403);
        }
    }

    protected function obtlValidationRules(): array
    {
        return [
            'obtlUpload' => 'required|file|mimes:pdf|max:10240',
        ];
    }

    protected function obtlValidationMessages(): array
    {
        return [
            'obtlUpload.required' => 'Please select an OBTL document to upload.',
            'obtlUpload.mimes' => 'The OBTL document must be a PDF file.',
            'obtlUpload.max' => 'The OBTL document must not exceed 10MB.',
        ];
    }

    protected function materialValidationRules(): array
    {
        return [
            'newMaterial.title' => 'required|string|max:255',
            'newMaterial.lecture_number' => 'nullable|string|max:50',
            'newMaterial.hours_taught' => 'nullable|numeric|min:0|max:100',
            'newMaterialUpload' => 'required|file|mimes:pdf,docx|max:20480',
        ];
    }

    protected function materialValidationMessages(): array
    {
        return [
            'newMaterial.title.required' => 'A title is required for the learning material.',
            'newMaterial.lecture_number.max' => 'Lecture number may not be greater than 50 characters.',
            'newMaterial.hours_taught.numeric' => 'Hours taught must be a numeric value.',
            'newMaterial.hours_taught.min' => 'Hours taught cannot be negative.',
            'newMaterial.hours_taught.max' => 'Hours taught must not exceed 100.',
            'newMaterialUpload.required' => 'Please select a learning material file to upload.',
            'newMaterialUpload.mimes' => 'Learning materials must be PDF or DOCX files.',
            'newMaterialUpload.max' => 'Learning material files must not exceed 20MB.',
        ];
    }

    public function getCanManageCourseProperty(): bool
    {
        return $this->course->user_id === auth()->id();
    }
}