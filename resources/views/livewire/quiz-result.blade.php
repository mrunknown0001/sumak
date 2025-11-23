<div class="mx-auto max-w-5xl space-y-8 px-4 py-8 text-slate-900 dark:text-slate-100">
    <div>
        <a
            href="{{ route('student.course.show', $attempt->topic->document->course_id) }}"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-emerald-600 transition hover:border-emerald-200 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-emerald-300 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/20"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Course
        </a>
    </div>

    <section class="mx-auto max-w-4xl space-y-6">
        <div class="rounded-3xl border border-emerald-200/70 bg-white/90 p-8 shadow-xl shadow-emerald-500/10 backdrop-blur dark:border-emerald-500/40 dark:bg-slate-900/70">
            <header class="text-center">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">🎯 Quiz Results</h1>
                <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $attempt->topic->name }}</p>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $attempt->topic->name }}</p>
            </header>

            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <article class="rounded-2xl border border-blue-200/60 bg-gradient-to-br from-blue-50 via-white to-emerald-50 p-5 text-center shadow-sm dark:border-blue-500/40 dark:from-blue-900/20 dark:via-slate-900/50 dark:to-emerald-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-300">Score</p>
                    <p class="mt-2 text-4xl font-black text-blue-700 dark:text-blue-200">{{ $attempt->score_percentage }}%</p>
                </article>

                <article class="rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-5 text-center shadow-sm dark:border-emerald-500/40 dark:from-emerald-900/20 dark:via-slate-900/50 dark:to-teal-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Correct Answers</p>
                    <p class="mt-2 text-4xl font-black text-emerald-700 dark:text-emerald-200">{{ $attempt->correct_answers }}/{{ $attempt->total_questions }}</p>
                </article>

                <article class="rounded-2xl border border-purple-200/60 bg-gradient-to-br from-purple-50 via-white to-pink-50 p-5 text-center shadow-sm dark:border-purple-500/40 dark:from-purple-900/20 dark:via-slate-900/50 dark:to-pink-900/30">
                    <p class="text-xs font-semibold uppercase tracking-wide text-purple-600 dark:text-purple-300">Time Spent</p>
                    <p class="mt-2 text-4xl font-black text-purple-700 dark:text-purple-200">{{ $attempt->time_spent_minutes }} min</p>
                </article>
            </div>

            @if($attempt->is_adaptive)
                <div class="mt-6 rounded-2xl border-l-4 border-emerald-400/70 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-400/60 dark:bg-emerald-500/20 dark:text-emerald-200">
                    🎯 Adaptive Quiz: Questions were tailored to your ability level.
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/90 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm dark:bg-emerald-500/80">
                    {{ $attempt->isPassed() ? '✓ Passed' : '⚠ Needs Improvement' }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-200/70 px-4 py-1.5 text-xs font-semibold text-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                    Attempt #{{ $attempt->attempt_number }}
                </span>
            </div>
        </div>

        @if($attempt->feedback)
            <section class="space-y-4 rounded-3xl border border-purple-200/70 bg-white/90 p-8 shadow-xl shadow-purple-500/10 dark:border-purple-500/40 dark:bg-slate-900/70">
                <header class="flex items-center gap-3">
                    <svg class="h-8 w-8 text-purple-500 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">AI-Powered Feedback</h2>
                </header>

                <p class="rounded-2xl border border-purple-200/50 bg-purple-500/10 px-5 py-4 text-sm font-medium text-slate-700 dark:border-purple-500/40 dark:bg-purple-500/20 dark:text-purple-100">
                    {{ $attempt->feedback->feedback_text }}
                </p>

                @php
                    $normalizeFeedbackList = static function ($value): array {
                        if (is_string($value)) {
                            $decoded = json_decode($value, true);
                            $value = $decoded ?? [];
                        }

                        if ($value instanceof \Illuminate\Support\Collection) {
                            $value = $value->toArray();
                        }

                        return array_values(array_filter(is_array($value) ? $value : []));
                    };

                    $strengths = $normalizeFeedbackList($attempt->feedback->strengths ?? []);
                    $areasToImprove = $normalizeFeedbackList($attempt->feedback->areas_to_improve ?? $attempt->feedback->weaknesses ?? []);
                    $recommendations = $normalizeFeedbackList($attempt->feedback->recommendations ?? []);
                @endphp

                @if(!empty($strengths))
                    <section class="rounded-2xl border border-emerald-200/60 bg-emerald-50/80 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/20 dark:text-emerald-100">
                        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 1 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Strengths
                        </h3>
                        <ul class="space-y-1 font-medium">
                            @foreach($strengths as $strength)
                                <li class="mt-3">
                                    <strong>Area:</strong> {{ $strength['area'] }}<br/>
                                    <strong>Evidence:</strong> {{ $strength['evidence'] }}<br/>
                                    <strong>Description:</strong> {{ $strength['description'] }}
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(!empty($areasToImprove))
                    <section class="rounded-2xl border border-amber-200/70 bg-amber-50/80 px-5 py-4 text-sm text-amber-900 dark:border-amber-500/50 dark:bg-amber-500/20 dark:text-amber-100">
                        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Areas to Improve
                        </h3>
                        <ul class="space-y-1 font-medium">
                            @foreach($areasToImprove as $area)
                                <li class="mb-3">
                                    <strong>Area:</strong> {{ $area['area'] }}<br/>
                                    <strong>Priority:</strong> {{ $area['priority'] }}<br/>
                                    <strong>Gap Analysis:</strong> {{ $area['gap_analysis'] }}<br/>
                                    <strong>Current Level:</strong> {{ $area['current_level'] }}
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(!empty($recommendations))
                    <section class="rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/20 dark:text-emerald-100">
                        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Recommendations
                        </h3>
                        <ul class="space-y-1 font-medium">
                            @foreach($recommendations as $recommendation)
                                <li class="mb-3">
                                    <strong>Recommendation:</strong> {{ $recommendation['recommendation'] }}<br/>
                                    <strong>Topic:</strong> {{ $recommendation['topic'] }}<br/>
                                    <strong>Reference:</strong> <a href="{{ $recommendation['resources'][0] ?? "#" }}" target="_blank">{{ $recommendation['resources'][1] ?? "N/A"}}</a><br/>
                                    <strong>Time:</strong> {{ $recommendation['estimated_time'] }}<br/>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </section>
        @else
            <div class="rounded-3xl border border-amber-200/70 bg-amber-500/20 px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/25 dark:text-amber-100">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    AI feedback is being generated. Please check back shortly.
                </div>
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200/80 bg-white/90 p-8 shadow-xl shadow-slate-500/10 dark:border-slate-800/70 dark:bg-slate-900/70">
            <h2 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
                <svg class="h-6 w-6 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 5.75h-3A1.75 1.75 0 003.75 7.5v9a1.75 1.75 0 001.75 1.75h3m7-12.5h2.25a2 2 0 012 2v8.5a2 2 0 01-2 2H15.5m-7-16h7a1 1 0 011 1v14a1 1 0 01-1 1h-7a1 1 0 01-1-1v-14a1 1 0 011-1z" />
                </svg>
                Question Review
            </h2>

            <div class="mt-6 space-y-5">
                @foreach($attempt->responses as $index => $response)
                    <article class="rounded-2xl border-2 p-5 shadow-sm transition {{ $response->is_correct ? 'border-emerald-300 bg-emerald-50/80 dark:border-emerald-500/60 dark:bg-emerald-900/20' : 'border-rose-300 bg-rose-50/80 dark:border-rose-500/60 dark:bg-rose-900/20' }}">
                        <header class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Question {{ $index + 1 }}</h3>
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold text-white shadow-sm {{ $response->is_correct ? 'bg-emerald-500 dark:bg-emerald-500/90' : 'bg-rose-500 dark:bg-rose-500/90' }}">
                                {{ $response->is_correct ? '✓ Correct' : '✗ Incorrect' }}
                            </span>
                        </header>

                        <p class="mt-4 text-sm font-medium text-slate-800 dark:text-slate-200">{{ $response->item->question }}</p>

                        <div class="mt-4 grid gap-2">
                            @foreach($response->item->options as $option)
                                @php
                                    $isCorrectChoice = $option['option_letter'] === $response->item->correct_answer;
                                    $isUserChoice = $option['option_letter'] === $response->user_answer;
                                @endphp
                                <div class="rounded-xl border-2 px-4 py-3 text-sm font-medium transition {{ $isCorrectChoice ? 'border-emerald-500 bg-emerald-500/90 text-white shadow-sm' : ($isUserChoice && !$response->is_correct ? 'border-rose-500 bg-rose-500/90 text-white shadow-sm' : 'border-slate-200/70 bg-white/80 text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300') }}">
                                    <span class="mr-2 font-semibold">{{ $option['option_letter'] }}.</span>
                                    <span>{{ $option['option_text'] }}</span>
                                    @if($isCorrectChoice)
                                        <span class="ml-2 font-bold">✓</span>
                                    @endif
                                    @if($isUserChoice)
                                        <span class="ml-2 text-xs italic">(Your answer)</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($response->item->explanation)
                            <p class="mt-4 rounded-xl border-l-4 border-emerald-400/70 bg-emerald-500/10 px-4 py-3 text-xs text-slate-700 dark:border-emerald-500/50 dark:bg-emerald-500/20 dark:text-emerald-100">
                                <span class="font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-200">Explanation:</span>
                                <span class="ml-1">{{ $response->item->explanation }}</span>
                            </p>
                        @endif

                        <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-slate-200/70 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                            ⏱ Time taken: {{ $response->time_taken_seconds }}s
                        </p>
                    </article>
                @endforeach
            </div>
        </section>

        <footer class="flex flex-wrap justify-center gap-3">
            <a
                href="{{ route('student.course.show', $attempt->topic->document->course_id) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300/70 bg-slate-100/70 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                ← Back to Course
            </a>
            {{-- <a
                href="{{ route('student.quiz.context', $attempt->topic_id) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-emerald-500 hover:to-blue-500 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
            >
                🔄 Retake Quiz
            </a> --}}
        </footer>
    </section>
</div>