<div
    class="mx-auto max-w-5xl px-4 py-8 text-slate-900 dark:text-slate-100"
    x-data="{
        timeRemaining: @entangle('timeRemaining'),
        timerMode: @entangle('timerMode'),
        isBreakTime: @entangle('isBreakTime'),
        timerInterval: null,
        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.timeRemaining > 0 && this.timerMode !== 'free') {
                    this.timeRemaining--;
                } else if (this.timeRemaining === 0 && this.timerMode !== 'free') {
                    clearInterval(this.timerInterval);
                    if (this.timerMode === 'standard') {
                        @this.call('submitAnswer');
                    } else if (this.timerMode === 'pomodoro' && !this.isBreakTime) {
                        @this.call('startBreak');
                    }
                }
            }, 1000);
        },
        stopTimer() {
            clearInterval(this.timerInterval);
        },
        getTimerColor() {
            if (this.timerMode === 'free') return 'bg-slate-400 dark:bg-slate-600';
            if (this.timerMode === 'pomodoro') return 'bg-purple-500 dark:bg-purple-400';
            if (this.timeRemaining > 30) return 'bg-emerald-500 dark:bg-emerald-400';
            if (this.timeRemaining > 10) return 'bg-amber-500 dark:bg-amber-400';
            return 'bg-rose-500 dark:bg-rose-400';
        },
        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
    }"
    x-init="$watch('timeRemaining', value => {
        if ((value === 60 || value === 1500) && $wire.quizStarted && !$wire.showFeedback) {
            stopTimer();
            startTimer();
        }
    })"
>
    @if($hasReachedAttemptLimit && !$quizStarted && !$attempt)
        <div class="mx-auto max-w-3xl">
            <div class="rounded-3xl border border-rose-200/70 bg-white/90 p-10 text-center shadow-xl shadow-rose-500/10 backdrop-blur dark:border-rose-500/40 dark:bg-slate-900/70">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border-4 border-rose-400/60 bg-rose-500/15 text-rose-600 dark:border-rose-400/50 dark:bg-rose-500/20 dark:text-rose-200">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2 2 2m-2-2V6m8 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Quiz Attempt Limit Reached</h2>
                <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300">
                    You have completed the maximum of {{ $maxAttemptsAllowed }} attempts for this quiz.
                    Review your results and continue with other course materials.
                </p>
                <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Completed Attempts: {{ $completedAttemptsCount }} / {{ $maxAttemptsAllowed }}
                </p>
                <div class="mt-8 flex justify-center">
                    <a
                        href="{{ route('student.course.show', $subtopic->topic->document->course_id) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                    >
                        ← Back to Course
                    </a>
                </div>
            </div>
        </div>
    @elseif(!$timerMode)
        <div class="mx-auto max-w-4xl">
            <div class="rounded-3xl border border-emerald-200/70 bg-white/90 p-10 shadow-xl shadow-emerald-500/10 backdrop-blur dark:border-emerald-500/40 dark:bg-slate-900/70">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $subtopic->name }}</h1>
                <p class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">{{ $subtopic->topic->name }}</p>

                <h2 class="mt-8 text-2xl font-semibold text-slate-900 dark:text-slate-100">Choose Your Quiz Timer Mode</h2>

                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    <button
                        wire:click="selectTimerMode('pomodoro')"
                        class="group rounded-2xl border-2 border-purple-300/60 bg-gradient-to-br from-purple-50 via-white to-emerald-50 p-6 text-left shadow-sm transition hover:-translate-y-1 hover:border-purple-400 hover:shadow-xl dark:border-purple-500/40 dark:from-purple-900/30 dark:via-slate-900/50 dark:to-emerald-900/30 dark:hover:border-purple-400/60"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-200">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pomodoro</span>
                        </div>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Focused 25-minute sessions with 5-minute breaks.</p>
                        <ul class="mt-4 space-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <li>✓ 25 min work sessions</li>
                            <li>✓ 5 min breaks</li>
                            <li>✓ Best for deep focus</li>
                        </ul>
                    </button>

                    <button
                        wire:click="selectTimerMode('free')"
                        class="group rounded-2xl border-2 border-emerald-300/60 bg-gradient-to-br from-emerald-50 via-white to-slate-50 p-6 text-left shadow-sm transition hover:-translate-y-1 hover:border-emerald-400 hover:shadow-xl dark:border-emerald-500/40 dark:from-emerald-900/30 dark:via-slate-900/50 dark:to-slate-900/30 dark:hover:border-emerald-400/60"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">Free Time</span>
                        </div>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No time pressure—review at your own pace.</p>
                        <ul class="mt-4 space-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <li>✓ No timer</li>
                            <li>✓ Unlimited review time</li>
                            <li>✓ Ideal for mastery</li>
                        </ul>
                    </button>

                    <button
                        wire:click="selectTimerMode('standard')"
                        class="group rounded-2xl border-2 border-emerald-300/60 bg-gradient-to-br from-emerald-50 via-white to-blue-50 p-6 text-left shadow-sm transition hover:-translate-y-1 hover:border-emerald-400 hover:shadow-xl dark:border-emerald-500/40 dark:from-emerald-900/30 dark:via-slate-900/50 dark:to-blue-900/30 dark:hover:border-emerald-400/60"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">Standard</span>
                        </div>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Timed challenge with 60 seconds per question.</p>
                        <ul class="mt-4 space-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <li>✓ 60s per question</li>
                            <li>✓ Auto-submit when time ends</li>
                            <li>✓ Boost rapid recall</li>
                        </ul>
                    </button>
                </div>
            </div>
        </div>
    @elseif(!$quizStarted)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-3xl border border-emerald-200/70 bg-white/90 p-8 shadow-xl shadow-emerald-500/10 backdrop-blur dark:border-emerald-500/40 dark:bg-slate-900/70">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $subtopic->name }}</h1>
                <p class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">{{ $subtopic->topic->name }}</p>

                <div class="mt-6 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 p-6 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/30 dark:text-emerald-100">
                    <h2 class="mb-3 text-lg font-semibold flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Quiz Information
                    </h2>
                    <ul class="space-y-2 font-medium">
                        {{-- <li>• 20 multiple-choice questions</li> --}}
                        <li>• Timer Mode: <strong class="capitalize">{{ $timerMode }}</strong></li>
                        @if($timerMode === 'pomodoro')
                            <li>• 25-minute focus sessions with 5-minute breaks</li>
                        @elseif($timerMode === 'free')
                            <li>• No time limit for each question</li>
                        @else
                            <li>• 60 seconds per question with automated submission</li>
                        @endif
                        <li>• Immediate feedback after submission</li>
                    </ul>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        wire:click="$set('timerMode', null)"
                        class="flex-1 rounded-xl border border-slate-300/70 bg-slate-100/70 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                    >
                        Change Timer Mode
                    </button>
                    <button
                        wire:click="startQuiz"
                        class="flex-1 rounded-xl bg-emerald-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                    >
                        Start Quiz
                    </button>
                </div>
            </div>
        </div>
    @elseif($quizCompleted)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-10 text-center shadow-xl shadow-emerald-500/10 dark:border-slate-800/70 dark:bg-slate-900/70">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border-4 border-emerald-400/50 bg-emerald-500/20 text-emerald-600 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-200">
                    @if($attempt->score_percentage >= 70)
                        <svg class="h-10 w-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-7.071 7.071a1 1 0 0 1-1.414 0l-3.536-3.536a1 1 0 0 1 1.414-1.414l2.829 2.828 6.364-6.364a1 1 0 0 1 1.414 0z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-10 w-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 4a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1zm0 10a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                    {{ $attempt->score_percentage >= 70 ? 'Great Job!' : 'Keep Practicing!' }}
                </h2>
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">You completed the quiz.</p>

                <div class="mt-6 text-center">
                    <p class="text-5xl font-black text-slate-900 dark:text-slate-100">{{ $attempt->score_percentage }}%</p>
                    <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $attempt->correct_answers }} out of {{ $attempt->total_questions }} correct</p>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a
                        href="{{ route('student.course.show', $subtopic->topic->document->course_id) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300/70 bg-slate-100/70 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                    >
                        ← Back to Course
                    </a>
                    <a
                        href="{{ route('student.quiz.result', $attempt->id) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                    >
                        View Detailed Results
                    </a>
                </div>
            </div>
        </div>
    @elseif($isBreakTime)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-3xl border border-purple-200/70 bg-white/90 p-8 text-center shadow-xl shadow-purple-500/10 dark:border-purple-500/40 dark:bg-slate-900/70">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border-4 border-purple-400/60 bg-purple-500/15 text-purple-600 dark:border-purple-400/50 dark:bg-purple-500/20 dark:text-purple-200">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Break Time!</h2>
                <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Take a moment to recharge before the next session.</p>

                <div class="mt-6">
                    <p class="text-4xl font-semibold text-slate-900 dark:text-slate-100" x-text="formatTime(timeRemaining)"></p>
                    <p class="mt-1 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Time remaining in break</p>
                </div>

                <button
                    wire:click="endBreak"
                    class="mt-6 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-500 dark:bg-purple-500 dark:hover:bg-purple-400"
                >
                    Skip Break & Continue
                </button>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-3xl space-y-6">
            @if($timerMode !== 'free')
                <div class="rounded-2xl border border-slate-200/70 bg-white/80 p-5 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
                    <div class="mb-2 flex items-center justify-between text-sm font-semibold text-slate-600 dark:text-slate-300">
                        <span>Question {{ $currentQuestionIndex + 1 }} of {{ $items->count() }}</span>
                        @if($timerMode === 'pomodoro')
                            <span class="text-purple-600 dark:text-purple-300" x-text="'Session: ' + formatTime(timeRemaining)"></span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2" />
                                </svg>
                                <span x-text="timeRemaining + 's'"></span>
                            </span>
                        @endif
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-200/80 dark:bg-slate-800">
                        <div
                            class="h-2 rounded-full transition-all duration-1000"
                            :class="getTimerColor()"
                            :style="`width: ${timerMode === 'pomodoro' ? (timeRemaining / {{ $pomodoroSessionTime }}) * 100 : (timeRemaining / 60) * 100}%`"
                        ></div>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-emerald-300/50 bg-emerald-50/60 p-5 shadow-sm dark:border-emerald-400/30 dark:bg-emerald-900/20">
                    <div class="flex flex-wrap justify-between gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-200">
                        <span>Question {{ $currentQuestionIndex + 1 }} of {{ $items->count() }}</span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-emerald-600 ring-1 ring-emerald-200 dark:bg-slate-900/70 dark:text-emerald-200 dark:ring-emerald-500/40">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Free Time Mode - No Rush!
                        </span>
                    </div>
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-8 shadow-lg shadow-emerald-500/5 dark:border-slate-800/70 dark:bg-slate-900/70">
                @if($items->count() > 0)
                    @php $item = $items[$currentQuestionIndex]; @endphp

                    <h2 class="text-xl font-semibold leading-relaxed text-slate-900 dark:text-slate-100">{{ $item['question'] }}</h2>

                    @if(!$showFeedback)
                        <div class="mt-6 space-y-3">
                            @foreach($item['options'] as $option)
                                @php
                                    $isSelected = $selectedAnswer === $option['option_letter'];
                                @endphp
                                <button
                                    wire:click="$set('selectedAnswer', '{{ $option['option_letter'] }}')"
                                    class="w-full rounded-2xl border-2 px-4 py-4 text-left text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 {{ $isSelected ? 'border-emerald-400 bg-emerald-500/10 text-emerald-700 shadow-sm dark:border-emerald-500/70 dark:bg-emerald-500/20 dark:text-emerald-200' : 'border-slate-200/80 bg-white/70 hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/15' }}"
                                >
                                    <span class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-full border border-current text-sm font-bold">{{ $option['option_letter'] }}</span>
                                    <span>{{ $option['option_text'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <button
                            wire:click="submitAnswer"
                            :disabled="!$wire.selectedAnswer"
                            class="mt-6 w-full rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-300 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:disabled:bg-slate-700"
                        >
                            Submit Answer
                        </button>
                    @else
                        <div class="mt-6 rounded-2xl border-2 p-5 shadow-sm {{ $isCorrect ? 'border-emerald-300 bg-emerald-50/80 text-emerald-700 dark:border-emerald-500/60 dark:bg-emerald-900/20 dark:text-emerald-200' : 'border-rose-300 bg-rose-50/80 text-rose-700 dark:border-rose-500/60 dark:bg-rose-900/20 dark:text-rose-200' }}">
                            <p class="text-lg font-semibold">
                                {{ $isCorrect ? '✓ Correct!' : '✗ Incorrect' }}
                            </p>
                            @unless($isCorrect)
                                <p class="mt-2 text-sm font-medium">The correct answer is: <span class="font-bold">{{ $correctAnswer }}</span></p>
                            @endunless
                            @if($item['explanation'])
                                <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">
                                    <span class="font-semibold">Explanation:</span> {{ $item['explanation'] }}
                                </p>
                            @endif
                        </div>

                        <button
                            wire:click="nextQuestion"
                            x-on:click="stopTimer()"
                            class="mt-6 w-full rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                        >
                            {{ $currentQuestionIndex + 1 < $items->count() ? 'Next Question' : 'Complete Quiz' }}
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>