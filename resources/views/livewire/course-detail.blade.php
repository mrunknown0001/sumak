@php
    $documentCount = $documents->count();
@endphp
<div class="mx-auto max-w-5xl space-y-8 text-slate-900 dark:text-slate-100">
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
                    @if($course->obtlDocument)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-600 dark:bg-emerald-500/30 dark:text-emerald-200">
                            ✓ OBTL Available
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 px-3 py-1 text-slate-600 dark:border-slate-700 dark:text-slate-300">
                        {{ $documentCount }} {{ \Illuminate\Support\Str::plural('lecture', $documentCount) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($documents as $document)
            <article class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-md transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:border-emerald-500/40">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $document->title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Uploaded {{ $document->uploaded_at->diffForHumans() }} • {{ $document->formatted_file_size }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($document->hasTos())
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-500/25 dark:text-emerald-200">
                                ✓ Ready for Quizzes
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-600 dark:bg-amber-500/25 dark:text-amber-200">
                                ⏳ Processing…
                            </span>
                        @endif
                    </div>
                </div>

                @if($document->content_summary)
                    <div class="mt-4 rounded-2xl border border-blue-200/70 bg-blue-50/80 p-4 text-sm text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-100">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                            <svg class="h-4 w-4 text-blue-500 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3-1.343 3-3S13.657 2 12 2 9 3.343 9 5s1.343 3 3 3zm0 0v13m-7-6a4 4 0 018 0" />
                            </svg>
                            Lecture Summary
                        </h4>
                        <p>{{ $document->content_summary }}</p>
                    </div>
                @endif

                @if($document->hasTos() && $document->topics->isNotEmpty())
                    <div class="mt-6 space-y-4">
                        <h4 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-slate-100">
                            <svg class="h-5 w-5 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            Available Quizzes
                        </h4>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach($document->topics as $topic)
                                @foreach($topic->subtopics as $subtopic)
                                    @php
                                        $attemptCount = $subtopic->user_attempts_count ?? 0;
                                        $maxAttempts = 3;
                                        $canRetake = $attemptCount < $maxAttempts;
                                    @endphp

                                    @if($canRetake)
                                        <a href="{{ route('student.quiz.context', $subtopic->id) }}" class="flex items-center justify-between rounded-2xl border border-emerald-200/70 bg-gradient-to-r from-emerald-100/80 to-blue-100/80 p-4 shadow-sm transition hover:-translate-y-0.5 hover:from-emerald-100 hover:to-blue-100 hover:shadow-lg dark:border-emerald-500/40 dark:from-emerald-900/30 dark:to-blue-900/30 dark:hover:border-emerald-400/70">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $subtopic->name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">📚 {{ $topic->name }}</p>
                                                <p class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-300">
                                                    Available • {{ $attemptCount }} / {{ $maxAttempts }} attempts used
                                                </p>
                                            </div>
                                            <div class="rounded-xl border border-emerald-300/70 bg-white/80 px-3 py-2 text-center shadow-sm dark:border-emerald-500/40 dark:bg-slate-900/70">
                                                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-300">{{ $subtopic->items_count ?? $subtopic->items()->count() }}</p>
                                                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">questions</span>
                                            </div>
                                        </a>
                                    @else
                                        <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-slate-100/70 p-4 opacity-70 shadow-sm transition cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/70">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $subtopic->name }}</p>
                                                <p class="text-xs text-slate-400 dark:text-slate-500">📚 {{ $topic->name }}</p>
                                                <p class="mt-2 text-xs font-semibold text-red-500 dark:text-red-400">
                                                    Unavailable • Max attempts reached ({{ $attemptCount }} / {{ $maxAttempts }})
                                                </p>
                                            </div>
                                            <div class="rounded-xl border border-slate-300/70 bg-white/70 px-3 py-2 text-center shadow-sm dark:border-slate-600/70 dark:bg-slate-900/70">
                                                <p class="text-lg font-bold text-slate-500 dark:text-slate-400">{{ $subtopic->items_count ?? $subtopic->items()->count() }}</p>
                                                <span class="text-xs font-medium text-slate-400 dark:text-slate-500">questions</span>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-md dark:border-slate-800/70 dark:bg-slate-900/70">
                <svg class="mx-auto mb-4 h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-slate-500 dark:text-slate-400">No lecture materials available yet.</p>
                <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">Your instructor will upload materials soon.</p>
            </div>
        @endforelse
    </div>
</div>