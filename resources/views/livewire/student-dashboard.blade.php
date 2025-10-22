<div class="mx-auto max-w-7xl space-y-10 text-slate-900 dark:text-slate-100">
    <!-- Header with Navigation -->
    <div class="flex flex-col gap-6 rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800/60 dark:bg-slate-900/70 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Welcome back, {{ $studentData['name'] }}</h1>
            <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">Student ID: {{ $studentData['student_id'] }}</p>
        </div>
        <a
            href="{{ route('student.courses') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:border-emerald-400/30 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-slate-900"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Browse Courses
        </a>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('error'))
        <div class="rounded-xl border border-red-400/50 bg-red-100/70 px-4 py-3 text-red-800 shadow-sm dark:border-red-500/40 dark:bg-red-900/40 dark:text-red-100" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="rounded-xl border border-green-400/50 bg-green-100/70 px-4 py-3 text-green-800 shadow-sm dark:border-green-500/40 dark:bg-green-900/40 dark:text-green-100" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Stats Overview -->
    <div class="grid gap-6 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/60 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800/70 dark:bg-slate-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Quizzes Taken</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $overallStats['total_quizzes_taken'] }}</p>
                </div>
                <svg class="h-10 w-10 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/60 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800/70 dark:bg-slate-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Avg Accuracy</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $overallStats['avg_accuracy'] }}%</p>
                </div>
                <svg class="h-10 w-10 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/60 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800/70 dark:bg-slate-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Study Time</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $overallStats['total_study_time'] }}</p>
                </div>
                <svg class="h-10 w-10 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/60 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-slate-800/70 dark:bg-slate-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Overall Ability</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ round($overallStats['overall_ability'] * 100) }}%</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">IRT Estimate</p>
                </div>
                <svg class="h-10 w-10 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Enrolled Courses -->
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">My Enrolled Courses</h2>
            <a href="{{ route('student.courses') }}" class="text-sm font-semibold text-emerald-600 transition hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200">
                Browse All Courses →
            </a>
        </div>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse($courses as $course)
                @php
                    $abilityInfo = $this->getAbilityLabel($course['ability_level']);
                    $progressColor = $this->getProgressColor($course['progress']);
                @endphp
                <div class="group rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:border-emerald-500/40">
                    <div class="mb-5 flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $course['name'] }}</h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $course['code'] }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $abilityInfo['bg'] }} {{ $abilityInfo['color'] }} dark:bg-opacity-30 dark:text-slate-100">
                            {{ $abilityInfo['label'] }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-300">Progress</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $course['progress'] }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-200/70 dark:bg-slate-800">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $progressColor }} dark:opacity-90" style="width: {{ $course['progress'] }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                            <span>Quizzes</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $course['quizzes_taken'] }}/{{ $course['total_quizzes'] }}</span>
                        </div>

                        <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                            <span>Avg Score</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $course['avg_score'] }}%</span>
                        </div>

                        <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                            <span>Ability Level</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ round($course['ability_level'] * 100) }}%</span>
                        </div>
                    </div>

                    <button
                        wire:click="viewCourse({{ $course['id'] }})"
                        class="mt-5 w-full rounded-xl bg-emerald-600 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                    >
                        View Course
                    </button>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
                    <svg class="mx-auto mb-4 h-16 w-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-slate-100">No Enrolled Courses</h3>
                    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Enroll in a course to start learning and taking quizzes</p>
                    <a
                        href="{{ route('student.courses') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Browse Available Courses
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Quizzes -->
    <div id="recent-quizzes" class="space-y-4 scroll-mt-28">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Recent Quiz Results</h2>
        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white/90 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/80 dark:divide-slate-800/80">
                    <thead class="bg-slate-100/80 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Topic</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Attempts</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 bg-white/80 dark:divide-slate-800/80 dark:bg-slate-900/70">
                        @foreach($recentQuizzes as $quiz)
                            @php
                                $percentage = ($quiz['score'] / $quiz['total']) * 100;
                                $scoreColor = $percentage >= 80 ? 'text-emerald-500 dark:text-emerald-400' : ($percentage >= 60 ? 'text-blue-500 dark:text-blue-400' : 'text-amber-500 dark:text-amber-400');
                            @endphp
                            <tr class="transition hover:bg-slate-100/70 dark:hover:bg-slate-800/70">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $quiz['course'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $quiz['topic'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <div class="flex items-center">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $quiz['score'] }}/{{ $quiz['total'] }}</span>
                                        <span class="ml-2 text-xs font-semibold {{ $scoreColor }}">({{ round($percentage) }}%)</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $quiz['duration'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $quiz['date'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $quiz['attempts_used'] }}/3</span>
                                    @if($quiz['attempts_remaining'] > 0)
                                        <span class="ml-1 text-xs text-slate-500 dark:text-slate-400">({{ $quiz['attempts_remaining'] }} left)</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <div class="flex gap-2">
                                        <button
                                            wire:click="viewQuiz({{ $quiz['id'] }})"
                                            class="text-emerald-600 transition hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
                                        >
                                            View
                                        </button>
                                        @if($quiz['attempts_remaining'] > 0)
                                            <button
                                                wire:click="retakeQuiz({{ $quiz['id'] }})"
                                                class="inline-flex items-center gap-1 text-emerald-600 transition hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
                                            >
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Retake
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- AI-Powered Feedback -->
    {{-- <div class="space-y-4">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
            <svg class="h-6 w-6 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            AI-Powered Personalized Feedback
        </h2>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach($aiFeedback as $feedback)
                <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:border-emerald-500/40">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $feedback['course'] }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $feedback['topic'] }}</p>
                    </div>

                    <div class="mb-4 rounded-2xl border border-emerald-200/50 bg-emerald-50/70 p-4 text-sm text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/30 dark:text-emerald-200">
                        {{ $feedback['feedback'] }}
                    </div>

                    <div class="space-y-4 text-sm text-slate-700 dark:text-slate-300">
                        <div>
                            <h4 class="mb-2 flex items-center gap-1 text-sm font-semibold text-emerald-600 dark:text-emerald-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                Strengths
                            </h4>
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach($feedback['strengths'] as $strength)
                                    <li>{{ $strength }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h4 class="mb-2 flex items-center gap-1 text-sm font-semibold text-blue-600 dark:text-blue-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                Areas to Improve
                            </h4>
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach($feedback['areas_to_improve'] as $area)
                                    <li>{{ $area }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h4 class="mb-2 flex items-center gap-1 text-sm font-semibold text-purple-600 dark:text-purple-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Recommendations
                            </h4>
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach($feedback['recommendations'] as $rec)
                                    <li>{{ $rec }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div> --}}

    <!-- Performance Analytics -->
    <div class="space-y-4">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
            <svg class="h-6 w-6 text-blue-500 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Performance Overview
        </h2>
        <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-xl border border-blue-200/40 bg-blue-50/60 p-4 text-center dark:border-blue-400/30 dark:bg-blue-900/20">
                    <p class="mb-2 text-sm font-medium text-blue-600 dark:text-blue-200">Current Mastery Level</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-100">{{ $overallStats['mastery_level'] }}</p>
                    <p class="mt-1 text-xs text-blue-500 dark:text-blue-200/80">Based on IRT Analysis</p>
                </div>

                <div class="rounded-xl border border-emerald-200/40 bg-emerald-50/70 p-4 text-center dark:border-emerald-400/30 dark:bg-emerald-900/20">
                    <p class="mb-2 text-sm font-medium text-emerald-600 dark:text-emerald-200">Quiz Completion Rate</p>
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-100">{{ $this->calculateCompletionRate() }}%</p>
                    <p class="mt-1 text-xs text-emerald-500 dark:text-emerald-200/80">Across all courses</p>
                </div>

                <div class="rounded-xl border border-purple-200/40 bg-purple-50/70 p-4 text-center dark:border-purple-400/30 dark:bg-purple-900/20">
                    <p class="mb-2 text-sm font-medium text-purple-600 dark:text-purple-200">Active Courses</p>
                    <p class="text-2xl font-bold text-purple-700 dark:text-purple-100">{{ count(array_filter($courses, fn($c) => $c['status'] === 'active')) }}</p>
                    <p class="mt-1 text-xs text-purple-500 dark:text-purple-200/80">Currently enrolled</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 dark:border-slate-800/70 dark:bg-slate-900/60">
                <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-200">Quick Tips</h3>
                <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-blue-500 dark:text-blue-300">•</span>
                        <span>Each quiz allows up to 3 attempts with reworded questions to help reinforce learning</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-blue-500 dark:text-blue-300">•</span>
                        <span>Your ability level is calculated using Item Response Theory (1PL model) for personalized difficulty</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-blue-500 dark:text-blue-300">•</span>
                        <span>Focus on topics marked &ldquo;Needs Practice&rdquo; to improve your overall mastery level</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>