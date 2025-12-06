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
use Illuminate\Support\Facades\Log;

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

    public bool $pollingActive = false;
    public array $pollingErrors = [];
    public $pollingStartedAt = null;
    public bool $hasProcessingDocuments = false;
    public bool $isPolling = false;
    public string $lastPolledAt = '';
    public int $pollCount = 0;

    // Listeners: include modal-driven polling control (stopPolling/startPolling)
    protected $listeners = [
        'materialUploaded' => '$refresh',
        'documentProcessed' => 'reloadDocuments',
        'stopPolling' => 'stopMaterialPolling',
        'startPolling' => 'startMaterialPolling',
    ];

    public bool $materialProcessing = false;


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

        if ($this->course->obtlDocument && $this->course->obtlDocument->processing_status !== ObtlDocument::PROCESSING_COMPLETED) {
            $this->pollingActive = true;
            $this->pollingStartedAt = now();
        }
    }


    public function reloadDocuments(): void
    {
        $this->materialProcessing = false;

        $this->course->refresh()->load('obtlDocument');

        $userId = auth()->id();

        $documents = $this->course->documents()
            ->with([
                'topics' => function ($query) use ($userId) {
                    $query->withCount('items')
                        ->withCount([
                            'quizAttempts as user_attempts_count' => function ($aq) use ($userId) {
                                $aq->where('user_id', $userId);
                            },
                        ]);
                },
                'tableOfSpecification',
            ])
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->get();

        $activeBatch = $this->documentQuizBatchService->currentBatch();

        $this->activeQuizBatch = $activeBatch;
        $this->documentBatchMeta = $this->buildDocumentBatchMeta($documents, $userId, $activeBatch)->toArray();

        $this->dispatch('$refresh');
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

        $this->pollingActive = true;
        $this->pollingStartedAt = now();
        $this->pollingErrors = [];

        $this->reset('obtlUpload');
        $this->refreshCourseState();

        session()->flash('message', 'OBTL document uploaded successfully. Extraction has started.');
    }

    public function uploadMaterial(): void
    {
        $this->materialProcessing = true;

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

        $this->materialProcessing = true;
        $this->dispatch('materialUploaded');

        session()->flash('message', 'Learning material uploaded successfully. Processing has started.');
    }

    public function pollObtlStatus()
    {
        if (!$this->pollingActive) {
            return;
        }

        $this->isPolling = true;
        $this->pollCount++;
        $this->lastPolledAt = now()->format('H:i:s');

        try {
            $this->course->refresh()->load('obtlDocument');
            $status = $this->course->obtlDocument?->processing_status;

            if ($status === ObtlDocument::PROCESSING_COMPLETED) {
                $this->pollingActive = false;
                $this->pollingStartedAt = null;
                $this->isPolling = false;
                session()->flash('message', '✅ OBTL processing completed successfully! You can now upload learning materials.');
            } elseif ($status === ObtlDocument::PROCESSING_FAILED) {
                $this->pollingActive = false;
                $this->isPolling = false;
                $this->pollingStartedAt = null;
                $this->pollingErrors[] = '❌ OBTL processing failed. Please try uploading again or contact support if the issue persists.';
            } elseif ($this->pollingStartedAt && now()->diffInSeconds($this->pollingStartedAt) > 300) {
                $this->pollingActive = false;
                $this->isPolling = false;
                $this->pollingStartedAt = null;
                $this->pollingErrors[] = '⏰ OBTL processing timed out after 5 minutes. The document may still be processing in the background. Please refresh manually or try uploading again.';
            }
        } catch (\Exception $e) {
            $this->pollingErrors[] = '🔄 Error checking OBTL status: ' . $e->getMessage();

            // Log the error but don't stop polling for temporary issues
            report($e);

            // If we've had too many consecutive errors, stop polling
            if ($this->pollCount >= 5) {
                $this->pollingActive = false;
                $this->isPolling = false;
                $this->pollingErrors[] = '🛑 Stopping automatic updates due to repeated errors. Please refresh the page manually.';
            }
        } finally {
            $this->isPolling = false;
        }
    }

    public function startPolling(): void
    {
        if (!$this->course->obtlDocument) {
            return;
        }

        $this->pollingActive = true;
        $this->pollingStartedAt = now();
        $this->pollCount = 0;
        $this->pollingErrors = [];
        $this->lastPolledAt = now()->format('H:i:s');

        session()->flash('message', '🔄 Started monitoring OBTL processing status. Updates will appear automatically.');
    }

    public function stopPolling(): void
    {
        $this->pollingActive = false;
        $this->isPolling = false;
        $this->pollingStartedAt = null;
        $this->pollCount = 0;
    }

    public function clearPollingErrors(): void
    {
        $this->pollingErrors = [];
    }

    public function getElapsedTimeProperty(): string
    {
        if (!$this->pollingStartedAt) {
            return '0s';
        }

        return now()->diffForHumans($this->pollingStartedAt, true);
    }

    public function getPollingStatusProperty(): string
    {
        if (!$this->pollingActive) {
            return 'inactive';
        }

        if ($this->isPolling) {
            return 'checking';
        }

        return 'waiting';
    }

    /**
     * Modal-driven polling control (P2: pause BOTH OBTL and material polling)
     * Called when topic assignment modal opens
     */
    public function stopMaterialPolling(): void
    {
        // Pause both OBTL extraction polling and material processing polling
        $this->pollingActive = false;
        $this->materialProcessing = false;
        $this->isPolling = false;
    }

    /**
     * Modal-driven polling control (resume both OBTL and material polling if needed)
     * Called when modal closes or after topic assignment completes
     */
    public function startMaterialPolling(): void
    {
        // Resume OBTL polling if there is an OBTL document still processing
        if ($this->course->obtlDocument &&
            $this->course->obtlDocument->processing_status !== ObtlDocument::PROCESSING_COMPLETED
        ) {
            $this->pollingActive = true;
            $this->pollingStartedAt = now();
        }

        // Resume material polling if there are documents still processing
        $this->hasProcessingDocuments = $this->course->documents()
            ->where('processing_status', '!=', Document::PROCESSING_COMPLETED)
            ->exists();

        if ($this->hasProcessingDocuments) {
            $this->materialProcessing = true;
        }
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
                                $attemptQuery->where('user_id', $userId)
                                            ->whereNotNull('completed_at');
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

        Log::debug('Batch: startMaterialQuizBatch => eligible topics result', [
            'document_id' => $documentId,
            'eligible_topic_ids' => $eligibleTopics->pluck('id'),
            'eligible_topics' => $eligibleTopics->toArray(),
        ]);

        $eligibleTopics = collect($eligibleTopics)->filter(fn($t) => is_object($t) && isset($t->id));

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

        $this->hasProcessingDocuments = $documents->where('processing_status', '!=', Document::PROCESSING_COMPLETED)->isNotEmpty();

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

        Log::debug('Batch: buildDocumentBatchMeta snapshot', [
            'document_id_list' => $documents->pluck('id'),
            'active_batch' => $activeBatch,
            'meta_preview' => $documents->map(function ($document) use ($maxAttempts, $activeDocumentId, $activeQueue) {
                return [
                    'document_id' => $document->id,
                    'topic_info' => $document->topics->map(function ($topic) {
                        return [
                            'id' => $topic->id,
                            'name' => $topic->name,
                            'items_count' => $topic->items_count,
                            'user_attempts_count' => $topic->user_attempts_count,
                        ];
                    }),
                ];
            }),
        ]);

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
