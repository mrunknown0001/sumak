<div class="mx-auto max-w-5xl space-y-8 text-slate-900 dark:text-slate-100">
    <div class="flex items-center">
        <a href="{{ route('student.course.show', $course->id) }}"
           class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-emerald-600 transition hover:border-emerald-200 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-emerald-300 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/20">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Course
        </a>
    </div>

    <div class="rounded-3xl border border-emerald-100/70 bg-white/90 p-8 shadow-lg shadow-emerald-500/5 backdrop-blur dark:border-emerald-500/40 dark:bg-slate-900/70">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-2">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $subtopic->topic->name }}</h1>
                <p class="text-xl font-medium text-slate-600 dark:text-slate-300">{{ $subtopic->name }}</p>

                @if($document?->content_summary)
                    <div class="mt-4 rounded-2xl border border-blue-200/70 bg-blue-50/80 p-4 text-sm text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-100">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide">
                            <svg class="h-4 w-4 text-blue-500 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8c1.657 0 3-1.343 3-3S13.657 2 12 2 9 3.343 9 5s1.343 3 3 3zm0 0v13m-7-6a4 4 0 018 0"/>
                            </svg>
                            Lecture Summary
                        </h4>
                        <p>{{ $document->content_summary }}</p>
                    </div>
                @endif
            </div>
 
            <div class="flex flex-col gap-4 rounded-2xl border border-slate-200/70 bg-white/80 p-4 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Course</dt>
                        <dd class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $course->course_code }} — {{ $course->course_title }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Attempts Used</dt>
                        <dd class="text-sm font-semibold {{ $hasReachedAttemptLimit ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-600 dark:text-emerald-300' }}">
                            {{ $completedAttemptsCount }} / {{ $maxAttemptsAllowed }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Status</dt>
                        <dd class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold
                            {{ $hasReachedAttemptLimit
                                ? 'bg-rose-500/15 text-rose-600 dark:bg-rose-500/20 dark:text-rose-200'
                                : 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200' }}">
                            {{ $hasReachedAttemptLimit ? 'Attempt limit reached' : 'Ready to start' }}
                        </dd>
                    </div>
                </dl>

                @if($materialPreviewUrl || $materialDownloadUrl)
                    <div class="flex flex-col gap-2">
                        @if($materialPreviewUrl)
                            <a href="{{ $materialPreviewUrl }}"
                               target="_blank"
                               rel="noopener"
                               class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300/70 bg-white/80 px-4 py-2 text-xs font-semibold text-emerald-600 shadow-sm transition hover:border-emerald-400 hover:bg-emerald-100/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 dark:border-emerald-500/40 dark:bg-slate-900/70 dark:text-emerald-200 dark:hover:border-emerald-400/60 dark:hover:bg-emerald-900/30">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-1.138a1 1 0 011.21.978v4.32a1 1 0 01-1.21.978L15 14m0-4v4m0-4H5a2 2 0 00-2 2v0a2 2 0 002 2h10m4-2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Open Lecture Material
                            </a>
                        @endif

                        @if($materialDownloadUrl)
                            <a href="{{ $materialDownloadUrl }}"
                               class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300/70 bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:from-emerald-400 hover:to-teal-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 dark:border-emerald-500/40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                </svg>
                                Download Lecture Material
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($materialPreviewUrl)
        <section class="space-y-4 rounded-3xl border border-slate-200/70 bg-white/90 p-8 shadow-lg shadow-slate-500/5 dark:border-slate-800/70 dark:bg-slate-900/70">
            <header class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Lecture Material Preview</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Review the relevant sections of the lecture material below before attempting the quiz.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $materialPreviewUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg border border-emerald-300/70 px-3 py-1 text-xs font-semibold text-emerald-600 transition hover:border-emerald-400 hover:bg-emerald-100/50 dark:border-emerald-500/40 dark:text-emerald-200 dark:hover:border-emerald-400/70 dark:hover:bg-emerald-500/10">
                        Open in new tab
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10v11h11" />
                        </svg>
                    </a>
                </div>
            </header>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50/60 shadow-inner dark:border-slate-700 dark:bg-slate-900/60" style="min-height: 600px;">
                <iframe src="{{ $materialPreviewUrl }}"
                        class="h-full w-full"
                        title="Lecture material preview"
                        loading="lazy"></iframe>
            </div>
        </section>
    @elseif($materialDownloadUrl)
        <section class="rounded-3xl border border-amber-200/70 bg-amber-50/70 p-6 text-sm text-amber-800 shadow-md dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-200">
            <p class="font-semibold">Lecture material preview is unavailable for this file format.</p>
            <p class="mt-2">
                Please download the lecture material using the button above and review it before proceeding to the quiz.
            </p>
        </section>
    @endif

    @if($documentTopics->isNotEmpty())
        <section class="space-y-6 rounded-3xl border border-slate-200/70 bg-white/90 p-8 shadow-lg shadow-slate-500/5 dark:border-slate-800/70 dark:bg-slate-900/70">
            <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Learning Outline</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Study the lecture breakdown below before attempting the quiz.
                    </p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200/70 px-4 py-1 text-xs font-semibold text-emerald-600 dark:border-emerald-500/40 dark:text-emerald-200">
                    {{ $documentTopics->count() }} {{ \Illuminate\Support\Str::plural('Topic', $documentTopics->count()) }}
                </span>
            </header>

            <div class="space-y-4">
                @foreach($documentTopics as $topic)
                    <article class="rounded-2xl border border-slate-200/70 bg-white/80 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:border-emerald-500/40">
                        <header class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $topic->name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $topic->subtopics->count() }} {{ \Illuminate\Support\Str::plural('Subtopic', $topic->subtopics->count()) }}
                                </p>
                            </div>
                        </header>

                        @if($topic->subtopics->isNotEmpty())
                            <ul class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                @foreach($topic->subtopics as $outlineSubtopic)
                                    @php
                                        $isActiveSubtopic = ($currentSubtopicId ?? null) === $outlineSubtopic->id;
                                    @endphp
                                    <li class="flex flex-col gap-2 rounded-xl border border-slate-200/60 bg-slate-50/70 px-3 py-3 transition dark:border-slate-700 dark:bg-slate-900/60 md:flex-row md:items-center md:justify-between {{ $isActiveSubtopic ? 'border-emerald-400/70 bg-emerald-100/40 shadow-sm dark:border-emerald-400/60 dark:bg-emerald-900/30' : '' }}">
                                        <div>
                                            <p class="font-medium text-slate-900 dark:text-slate-100">{{ $outlineSubtopic->name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                Quiz items available: {{ $outlineSubtopic->items_count ?? $outlineSubtopic->items()->count() }}
                                            </p>
                                            @if($isActiveSubtopic)
                                                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-600 dark:bg-emerald-500/25 dark:text-emerald-200">
                                                    Currently reviewing
                                                </span>
                                            @endif
                                        </div>
                                        <a href="{{ route('student.quiz.context', $outlineSubtopic->id) }}"
                                           aria-current="{{ $isActiveSubtopic ? 'true' : 'false' }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-emerald-300/60 px-3 py-1 text-xs font-semibold text-emerald-600 transition hover:border-emerald-400 hover:bg-emerald-100/50 dark:border-emerald-500/40 dark:text-emerald-200 dark:hover:border-emerald-400/70 dark:hover:bg-emerald-500/10">
                                            Review & Quiz
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 rounded-xl border border-slate-200/60 bg-slate-50/70 px-3 py-2 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                                Subtopics will appear here once the instructor finishes processing this lecture.
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($tableOfSpecification)
        <section class="space-y-6 rounded-3xl border border-slate-200/70 bg-white/90 p-8 shadow-lg shadow-slate-500/5 dark:border-slate-800/70 dark:bg-slate-900/70">
            <header>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Learning Outcomes Overview</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    These outcomes were mapped from the course's table of specifications. Review the expectations and indicators before attempting the quiz.
                </p>
            </header>

            <div class="space-y-4">
                @forelse($learningOutcomeSummaries as $summary)
                    <article class="rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900/70 dark:hover:border-emerald-400/70">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $summary['outcome']->outcome_code ?? 'Learning Outcome' }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $summary['outcome']->description }}</p>
                            </div>
                            @if($summary['cognitive_levels']->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($summary['cognitive_levels'] as $level)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
                                            {{ $level }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if($summary['sample_indicators']->isNotEmpty())
                            <div class="mt-4 rounded-2xl border border-slate-200/60 bg-slate-50/70 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                                <h4 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Performance Indicators</h4>
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach($summary['sample_indicators'] as $indicator)
                                        <li>{{ $indicator }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100/80 px-3 py-1 dark:bg-slate-800/80">
                                Planned Items: {{ $summary['planned_items'] }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100/80 px-3 py-1 dark:bg-slate-800/80">
                                Generated Questions: {{ $summary['generated_items'] }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-slate-200/70 bg-white/80 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">
                        No learning outcomes were mapped for this subtopic yet. Contact your instructor if this persists.
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="rounded-3xl border border-emerald-200/70 from-emerald-50 via-white to-blue-50 p-8 shadow-lg shadow-emerald-500/10 dark:border-emerald-500/40 dark:from-emerald-900/30 dark:via-slate-900/70 dark:to-blue-900/30">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Ready to take the quiz?</h2>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    Make sure you have reviewed the learning outcomes above. When you are prepared, continue to the quiz and pick the timer mode that works best for you.
                </p>
            </div>

            @if($hasReachedAttemptLimit)
                <div class="rounded-2xl border border-rose-300/60 bg-rose-50/70 p-6 text-center shadow-sm dark:border-rose-500/40 dark:bg-rose-900/30">
                    <p class="text-lg font-semibold text-rose-600 dark:text-rose-200">Attempt Limit Reached</p>
                    <p class="mt-2 text-sm text-rose-500 dark:text-rose-300">You have used all {{ $maxAttemptsAllowed }} attempts for this quiz.</p>
                    <a href="{{ route('student.course.show', $course->id) }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-300/70 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                        Review Course Materials
                    </a>
                </div>
            @else
                <button wire:click="startQuiz"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 dark:bg-emerald-500 dark:hover:bg-emerald-400">
                    Start Quiz
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            @endif
        </div>
    </section>
</div>