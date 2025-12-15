@php
    use App\Models\Course;
    use App\Models\ObtlDocument;
    use Illuminate\Support\Str;

    $documentCount = $documents->count();
    $obtlDocument = $course->obtlDocument;
    $obtlStatus = $obtlDocument?->processing_status;
    $documentBatchMetaById = collect($documentBatchMeta ?? [])->keyBy('document_id');
    $activeBatchDocumentId = $activeQuizBatch['document_id'] ?? null;
    $activeBatchQueue = collect($activeQuizBatch['queue'] ?? []);
    // $activeBatchNexTopicId = $activeBatchQueue->first();
    $nextBatchTopicId = $activeBatchQueue->first();
    $activeBatchRemainingCount = $activeBatchQueue->count();
    $maxAttemptsAllowed = $maxAttempts ?? config('quiz.max_attempts', 3);
    $canquiz = true;
    $retake = false;
@endphp

<div wire:poll.5s="pollObtlStatus" class="mx-auto max-w-5xl space-y-8 text-slate-900 dark:text-slate-100">
    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-400/40 bg-emerald-100/70 px-4 py-3 text-emerald-800 shadow-sm dark:border-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-100">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex items-center">
        <a href="{{ route('student.courses') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-emerald-600 transition hover:border-emerald-200 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-emerald-300 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/20">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to My Courses
        </a>
    </div>

    <div class="rounded-3xl border border-emerald-100/70 bg-white/90 p-8 shadow-lg shadow-emerald-500/5 backdrop-blur dark:border-emerald-500/40 dark:bg-slate-900/70">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $course->course_code }}</h1>
                <p class="text-xl font-medium text-slate-600 dark:text-slate-300">{{ $course->course_title }}</p>
                @if($course->description)
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $course->description }}</p>
                @endif
                <div class="flex flex-wrap items-center gap-3 pt-1 text-sm font-semibold">
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-600 dark:bg-emerald-500/30 dark:text-emerald-200">
                        ✓ Enrolled
                    </span>

                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-500/15 px-3 py-1 text-blue-600 dark:bg-blue-500/25 dark:text-blue-200">
                        Workflow: {{ Str::headline($course->workflow_stage ?? Course::WORKFLOW_STAGE_DRAFT) }}
                    </span>

                    @if($obtlDocument)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-600 dark:bg-emerald-500/30 dark:text-emerald-200">
                            ✓ OBTL {{ Str::headline($obtlStatus) }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/20 px-3 py-1 text-amber-600 dark:bg-amber-500/30 dark:text-amber-200">
                            OBTL Pending
                        </span>
                    @endif

                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 px-3 py-1 text-slate-600 dark:border-slate-700 dark:text-slate-300">
                        {{ $documentCount }} {{ \Illuminate\Support\Str::plural('learning material', $documentCount) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if($course->tableOfSpecifications->isNotEmpty())
        <div class="flex justify-center gap-3">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-600 dark:bg-emerald-500/30 dark:text-emerald-200">
                <a href="{{ route('student.course.tos', ['courseId' => $course->id, 'term' => 'midterm']) }}">Midterm ToS</a>
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-600 dark:bg-emerald-500/30 dark:text-emerald-200">
                <a href="{{ route('student.course.tos', ['courseId' => $course->id, 'term' => 'final']) }}">Final ToS</a>
            </span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-md dark:border-slate-800/70 dark:bg-slate-900/70">
            <header class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        OBTL Document
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Upload the course Outcome-Based Teaching and Learning document (PDF only).
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $course->obtl_uploaded_at ? $course->obtl_uploaded_at->diffForHumans() : 'Not uploaded' }}
                </div>
            </header>

            @if (!$obtlDocument)
                @if ($this->canManageCourse)
                    <form wire:submit.prevent="uploadObtl" enctype="multipart/form-data" class="space-y-5">
                        <div class="space-y-2">
                            <label for="obtlUpload" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Select PDF file <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="obtlUpload"
                                type="file"
                                accept="application/pdf"
                                wire:model="obtlUpload"
                                class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-10 text-sm text-slate-500 shadow-inner transition hover:border-emerald-300 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-emerald-400 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                            />
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Maximum file size: 10 MB.
                            </p>

                            <div wire:loading.flex wire:target="obtlUpload" class="items-center gap-2 rounded-xl border border-emerald-300/60 bg-emerald-100/60 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Uploading document…
                            </div>

                            @error('obtlUpload')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="uploadObtl,obtlUpload"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 disabled:opacity-70 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                            >
                                <svg
                                    wire:loading
                                    wire:target="uploadObtl,obtlUpload"
                                    class="h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span>Upload OBTL</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/70 p-4 text-sm text-slate-600 shadow-inner dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        Only the course creator can upload the OBTL document.
                    </div>
                @endif
            @else
                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Document Details</h3>
                        <dl class="mt-3 grid grid-cols-1 gap-3 text-sm text-slate-600 dark:text-slate-400 sm:grid-cols-2">
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">File Name</dt>
                                <dd class="truncate">{{ $obtlDocument->title }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Uploaded</dt>
                                <dd>{{ $obtlDocument->uploaded_at?->diffForHumans() ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Status</dt>
                                <dd>
                                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                                        @class([
                                            'bg-emerald-500/20 text-emerald-600 dark:bg-emerald-500/30 dark:text-emerald-200' => $obtlStatus === ObtlDocument::PROCESSING_COMPLETED,
                                            'bg-amber-500/20 text-amber-600 dark:bg-amber-500/30 dark:text-amber-200' => $obtlStatus === ObtlDocument::PROCESSING_IN_PROGRESS,
                                            'bg-slate-500/20 text-slate-600 dark:bg-slate-500/30 dark:text-slate-300' => $obtlStatus === ObtlDocument::PROCESSING_PENDING,
                                            'bg-red-500/15 text-red-600 dark:bg-red-500/20 dark:text-red-300' => $obtlStatus === ObtlDocument::PROCESSING_FAILED,
                                        ])
                                    ">
                                        {{ Str::headline($obtlStatus) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 dark:text-slate-400">Learning Outcomes</dt>
                                <dd>{{ $obtlDocument->learningOutcomes()->count() }}</dd>
                            </div>
                        </dl>
                    </div>
                    @if ($obtlStatus !== ObtlDocument::PROCESSING_COMPLETED)
                        <div class="space-y-3">
                            <div class="rounded-xl border border-blue-200/60 bg-blue-50/80 p-4 dark:border-blue-500/30 dark:bg-blue-900/20">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        @if($this->pollingStatus === 'checking')
                                            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                                                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                </svg>
                                                <span class="text-sm font-semibold">Checking status...</span>
                                            </div>
                                        @elseif($this->pollingStatus === 'waiting')
                                            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-sm font-semibold">Waiting...</span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-sm font-semibold">Status monitoring inactive</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        @if($this->pollingActive)
                                            <p class="text-xs text-blue-600 dark:text-blue-300">
                                                ⏱️ Processing time: <strong>{{ $this->elapsedTime }}</strong> •
                                                Last checked: <strong>{{ $this->lastPolledAt }}</strong>
                                                {{-- Attempts: <strong>{{ $this->pollCount }}</strong> --}}
                                            </p>
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">
                                                OBTL extraction is running. This page will automatically refresh when processing completes. Learning materials upload remains locked until completion.
                                            </p>
                                        @else
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                                OBTL extraction is running. Learning materials upload remains locked until completion.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($this->pollingActive)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        {{-- <button
                                            wire:click="stopPolling"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                            </svg>
                                            Pause
                                        </button>
                                        <button
                                            wire:click="startPolling"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600 transition hover:bg-emerald-100 dark:border-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/30"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H15M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1M9 10v4a2 2 0 002 2h2a2 2 0 002-2v-4M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1" />
                                            </svg>
                                            Resume
                                        </button> --}}
                                    </div>
                                </div>
                            @else
                                @if($this->course->obtlDocument && $this->course->obtlDocument->processing_status !== ObtlDocument::PROCESSING_COMPLETED)
                                    <button
                                        wire:click="startPolling"
                                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-100 dark:border-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/30"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Start Auto-Refresh
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif

                    @if($pollingErrors)
                        <div class="rounded-xl border border-red-200/60 bg-red-50/80 p-4 dark:border-red-500/30 dark:bg-red-900/20">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <div class="flex-1">
                                    @foreach($pollingErrors as $error)
                                        <p class="text-sm font-medium text-red-700 dark:text-red-300 mb-1">{{ $error }}</p>
                                    @endforeach
                                    <button
                                        wire:click="clearPollingErrors"
                                        class="mt-2 text-xs font-semibold text-red-600 underline hover:text-red-800 dark:text-red-400 dark:hover:text-red-200"
                                    >
                                        Clear errors
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        <section class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-md dark:border-slate-800/70 dark:bg-slate-900/70">
            <header class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Learning Materials
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Upload lectures once the OBTL document has finished processing. Accepted formats: PDF, DOCX.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $course->materials_uploaded_at ? $course->materials_uploaded_at->diffForHumans() : 'Awaiting uploads' }}
                </div>
            </header>
            @if (!$obtlDocument)
                <div class="rounded-2xl border border-amber-200/60 bg-amber-50/70 p-4 text-sm text-amber-700 shadow-inner dark:border-amber-500/30 dark:bg-amber-900/20 dark:text-amber-200">
                    Upload the OBTL document first to unlock learning material uploads.
                </div>
            @elseif ($obtlStatus !== ObtlDocument::PROCESSING_COMPLETED)
                <div class="rounded-2xl border border-amber-200/60 bg-amber-50/70 p-4 text-sm text-amber-700 shadow-inner dark:border-amber-500/30 dark:bg-amber-900/20 dark:text-amber-200">
                    OBTL extraction is still in progress. You will be able to upload learning materials once the processing status is <strong>Completed</strong>.
                </div>
            @elseif (! $this->canManageCourse)
                <div class="rounded-2xl border border-slate-200/60 bg-slate-50/70 p-4 text-sm text-slate-600 shadow-inner dark:border-slate-700/50 dark:bg-slate-800/40 dark:text-slate-300">
                    Only the course creator can upload learning materials.
                </div>
            @else
                <form wire:submit.prevent="uploadMaterial" enctype="multipart/form-data" class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <label for="materialTitle" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Material Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="materialTitle"
                                type="text"
                                wire:model.defer="newMaterial.title"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                                placeholder="Lecture title"
                            />
                            @error('newMaterial.title')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <label for="materialUpload" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Upload File <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="materialUpload"
                                type="file"
                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                wire:model="newMaterialUpload"
                                class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-10 text-sm text-slate-500 shadow-inner transition hover:border-emerald-300 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-emerald-400 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                            />
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                PDF or DOCX. Maximum size: 20 MB.
                            </p>

                            <div wire:loading.flex wire:target="newMaterialUpload" class="items-center gap-2 rounded-xl border border-emerald-300/60 bg-emerald-100/60 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Uploading learning material…
                            </div>

                            @error('newMaterialUpload')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="uploadMaterial,newMaterialUpload"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 disabled:opacity-70 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                        >
                            <svg
                                wire:loading
                                wire:target="uploadMaterial,newMaterialUpload"
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>Upload Material</span>
                        </button>
                    </div>
                </form>
            @endif
        </section>
    </div>

    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Learning Materials</h2>

        @forelse($documents as $document)
            @php
                $meta = $documentBatchMetaById->get($document->id) ?? null;
                $isActiveBatch = $activeBatchDocumentId === $document->id;
                $remainingInBatch = $isActiveBatch ? $activeBatchRemainingCount : 0;
                $nextBatchTopicId = $activeBatchQueue->first();
                $eligibleQuizCount = $meta['eligible_quiz_count'] ?? 0;
            @endphp
            <article class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-md transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl dark	border-slate-800/70 dark:bg-slate-900/70 dark:hover:border-emerald-500/40">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-100 dark:text-slate-900">{{ $document->title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Uploaded {{ $document->uploaded_at->diffForHumans() }} • {{ $document->formatted_file_size }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-3 sm:flex-row sm:items-center">
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                                @class([
                                    'bg-emerald-500/20 text-emerald-600 dark:bg-emerald-500/25 dark:text-emerald-200' => $document->processing_status === \App\Models\Document::PROCESSING_COMPLETED,
                                    'bg-amber-500/20 text-amber-600 dark:bg-amber-500/25 dark:text-amber-200' => $document->processing_status === \App\Models\Document::PROCESSING_IN_PROGRESS,
                                    'bg-slate-500/20 text-slate-600 dark:bg-slate-500/30 dark:text-slate-300' => $document->processing_status === \App\Models\Document::PROCESSING_PENDING,
                                    'bg-red-500/15 text-red-600 dark:bg-red-500/20 dark:text-red-300' => $document->processing_status === \App\Models\Document::PROCESSING_FAILED,
                                ])
                            ">
                                {{ Str::headline($document->processing_status) }}
                            </span>
                        </div>
                    </div>
                </div>



                @if($document->short_content_summary)
                    <div class="mt-4 rounded-2xl border border-blue-200/70 bg-blue-50/80 p-4 text-sm text-blue-100 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-900">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                            <svg class="h-4 w-4 text-blue-300 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3-1.343 3-3S13.657 2 12 2 9 3.343 9 5s1.343 3 3 3zm0 0v13m-7-6a4 4 0 018 0" />
                            </svg>
                            Lecture Summary
                        </h4>
                        <p>{{ $document->short_content_summary }}</p>
                    </div>
                @endif

                @if($document->hasTos() && $document->topics->isNotEmpty())
                    <div class="mt-6 space-y-4">
                        <h4 class="flex items-center gap-2 text-lg font-semibold text-slate-100 dark:text-slate-900">
                            <svg class="h-5 w-5 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            Available Topics
                        </h4>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @if($materialProcessing)
                                <div class="col-span-2 text-blue-500 text-sm font-medium">
                                    ⏳ Processing learning material... extracting topics...
                                </div>
                            @endif

                            @foreach($document->topics as $topic)
                                @if($topic->items()->count() > 0)
                                    @php
                                        $attemptCount = $topic->user_attempts_count ?? 0;
                                        $maxAttempts = 3;
                                        $canRetake = $attemptCount < $maxAttempts;

                                        $canquiz = false;
                                        if($canquiz == false && $attemptCount == 3) {
                                            $canquiz = true;
                                        }

                                        if($attemptCount >= 1) {
                                            $retake = true;
                                        }
                                    @endphp

                                    <a href="{{ route('student.quiz.context', $topic->id) }}" class="flex items-center justify-between rounded-2xl border border-emerald-200/70 bg-gradient-to-r from-emerald-100/80 to-blue-100/80 p-4 shadow-sm transition hover:-translate-y-0.5 hover:from-emerald-100 hover:to-blue-100 hover:shadow-lg dark:border-emerald-500/40 dark:from-emerald-900/30 dark:to-blue-900/30 dark:hover:border-emerald-400/70">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-100 dark:text-slate-900">{{ $topic->name }}</p>
                                        </div>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- @if($document->processing_status === \App\Models\Document::PROCESSING_COMPLETED)
                    <div class="mt-4 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/60">
                        <div class="flex flex-col gap-3 text-sm text-slate-700 dark:text-slate-300 md:flex-row md:items-center md:justify-between">
                            <div class="space-y-1">
                                <p class="text-base font-semibold text-slate-900 dark:text-slate-100">Ready to take all quizzes for this learning material?</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $eligibleQuizCount }} {{ Str::plural('topic quiz', $eligibleQuizCount) }} available. Start a batch session to attempt them sequentially.
                                </p>
                                @if($canquiz)
                                    <p class="text-xs text-red-500 dark:text-red-400">
                                        You've used all 3 attempts on this course.
                                    </p>
                                @endif
                            </div>
                            <button
                                wire:click="startMaterialQuizBatch({{ $document->id }})"
                                @disabled($eligibleQuizCount === 0)
                                @disabled($canquiz)
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:cursor-not-allowed disabled:bg-slate-300 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:disabled:bg-slate-700"
                            >
                                {{ $retake ? "Re-take Quiz" : "Take Quiz" }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif --}}
            </article>
        @empty
            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-md dark:border-slate-800/70 dark:bg-slate-900/70">
                <svg class="mx-auto mb-4 h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-slate-500 dark:text-slate-400">No learning materials uploaded yet.</p>
                @if (!$obtlDocument)
                    <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">
                        Upload the OBTL document to unlock learning material uploads and quiz generation.
                    </p>
                @elseif ($obtlStatus !== ObtlDocument::PROCESSING_COMPLETED)
                    <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">
                        OBTL processing is underway. Learning materials will appear here once uploads complete.
                    </p>
                @else
                    <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">
                        Upload your first learning material using the form above.
                    </p>
                @endif
            </div>
        @endforelse
    </div>

    <!-- TOS Modal -->
    @if($showTosModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-md rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-lg backdrop-blur dark:border-slate-800/70 dark:bg-slate-900/70">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Configure Table of Specifications</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Enter the number of items for midterm and final examinations.</p>
                </div>

                <form wire:submit.prevent="submitTosItems" class="space-y-4">
                    <div>
                        <label for="midTermItems" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Mid Term Items</label>
                        <input
                            id="midTermItems"
                            type="number"
                            wire:model="midTermItems"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                            placeholder="e.g., 20"
                            min="1"
                            max="100"
                        />
                        @error('midTermItems')
                            <p class="mt-1 text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    |
                    <div>
                        <label for="finalTermItems" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Final Term Items</label>
                        <input
                            id="finalTermItems"
                            type="number"
                            wire:model="finalTermItems"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                            placeholder="e.g., 30"
                            min="1"
                            max="100"
                        />
                        @error('finalTermItems')
                            <p class="mt-1 text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="$set('showTosModal', false)"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                        >
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Quiz Count Modal -->
    @if($showQuizCountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-md rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-lg backdrop-blur dark:border-slate-800/70 dark:bg-slate-900/70">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Select Quiz Count</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Choose the number of quiz questions to generate.</p>
                </div>

                <form wire:submit.prevent="selectQuizCount" class="space-y-4">
                    <div>
                        <label for="quizCount" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Quiz Count</label>
                        <select
                            id="quizCount"
                            wire:model="selectedQuizCount"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                        >
                            <option value="">Select Count</option>
                            <option value="10">10 questions</option>
                            <option value="15">15 questions</option>
                            <option value="20">20 questions</option>
                            <option value="30">30 questions</option>
                            <option value="automatic">Automatic (recommended)</option>
                        </select>
                        @error('selectedQuizCount')
                            <p class="mt-1 text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="$set('showQuizCountModal', false)"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                        >
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>