<div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header with Navigation -->
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Welcome back, {{ $studentData['name'] }}</h1>
                    <p class="text-gray-600 mt-1">Student ID: {{ $studentData['student_id'] }}</p>
                </div>
                <a href="{{ route('student.courses') }}"
                   class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Browse Courses
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if (session()->has('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Quizzes Taken</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $overallStats['total_quizzes_taken'] }}</p>
                    </div>
                    <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Avg Accuracy</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $overallStats['avg_accuracy'] }}%</p>
                    </div>
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Study Time</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $overallStats['total_study_time'] }}</p>
                    </div>
                    <svg class="w-10 h-10 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Overall Ability</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ round($overallStats['overall_ability'] * 100) }}%</p>
                        <p class="text-xs text-gray-500 mt-1">IRT Estimate</p>
                    </div>
                    <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Enrolled Courses -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900">My Enrolled Courses</h2>
                <a href="{{ route('student.courses') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                    Browse All Courses →
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse($courses as $course)
                    @php
                        $abilityInfo = $this->getAbilityLabel($course['ability_level']);
                        $progressColor = $this->getProgressColor($course['progress']);
                    @endphp
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-900">{{ $course['name'] }}</h3>
                                    <p class="text-sm text-gray-500">{{ $course['code'] }}</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $abilityInfo['bg'] }} {{ $abilityInfo['color'] }}">
                                    {{ $abilityInfo['label'] }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600">Progress</span>
                                        <span class="font-medium text-gray-900">{{ $course['progress'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="{{ $progressColor }} h-2 rounded-full transition-all" style="width: {{ $course['progress'] }}%"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Quizzes</span>
                                    <span class="font-medium text-gray-900">{{ $course['quizzes_taken'] }}/{{ $course['total_quizzes'] }}</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Avg Score</span>
                                    <span class="font-medium text-gray-900">{{ $course['avg_score'] }}%</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Ability Level</span>
                                    <span class="font-medium text-gray-900">{{ round($course['ability_level'] * 100) }}%</span>
                                </div>
                            </div>

                            <button wire:click="viewCourse({{ $course['id'] }})" 
                                    class="mt-4 w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                View Course
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-white rounded-lg shadow">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Enrolled Courses</h3>
                        <p class="text-gray-500 mb-4">Enroll in a course to start learning and taking quizzes</p>
                        <a href="{{ route('student.courses') }}"
                           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Browse Available Courses
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Quizzes -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Recent Quiz Results</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Topic</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentQuizzes as $quiz)
                                @php
                                    $percentage = ($quiz['score'] / $quiz['total']) * 100;
                                    $scoreColor = $percentage >= 80 ? 'text-green-600' : ($percentage >= 60 ? 'text-blue-600' : 'text-orange-600');
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $quiz['course'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $quiz['topic'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-900">{{ $quiz['score'] }}/{{ $quiz['total'] }}</span>
                                            <span class="ml-2 text-xs {{ $scoreColor }}">({{ round($percentage) }}%)</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $quiz['duration'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $quiz['date'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="text-gray-900">{{ $quiz['attempts_used'] }}/3</span>
                                        @if($quiz['attempts_remaining'] > 0)
                                            <span class="ml-1 text-gray-500">({{ $quiz['attempts_remaining'] }} left)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex gap-2">
                                            <button wire:click="viewQuiz({{ $quiz['id'] }})" 
                                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                                View
                                            </button>
                                            @if($quiz['attempts_remaining'] > 0)
                                                <button wire:click="retakeQuiz({{ $quiz['id'] }})" 
                                                        class="text-green-600 hover:text-green-800 font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                AI-Powered Personalized Feedback
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($aiFeedback as $feedback)
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="mb-4">
                            <h3 class="font-semibold text-lg text-gray-900">{{ $feedback['course'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $feedback['topic'] }}</p>
                        </div>

                        <div class="mb-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                            <p class="text-sm text-gray-700">{{ $feedback['feedback'] }}</p>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <h4 class="font-medium text-sm text-gray-900 mb-2 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    Strengths
                                </h4>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($feedback['strengths'] as $strength)
                                        <li class="text-sm text-gray-600">{{ $strength }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-medium text-sm text-gray-900 mb-2 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    Areas to Improve
                                </h4>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($feedback['areas_to_improve'] as $area)
                                        <li class="text-sm text-gray-600">{{ $area }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-medium text-sm text-gray-900 mb-2 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Recommendations
                                </h4>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($feedback['recommendations'] as $rec)
                                        <li class="text-sm text-gray-600">{{ $rec }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Performance Analytics -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Performance Overview
            </h2>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">Current Mastery Level</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $overallStats['mastery_level'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Based on IRT Analysis</p>
                    </div>

                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">Quiz Completion Rate</p>
                        <p class="text-2xl font-bold text-green-600">{{ $this->calculateCompletionRate() }}%</p>
                        <p class="text-xs text-gray-500 mt-1">Across all courses</p>
                    </div>

                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">Active Courses</p>
                        <p class="text-2xl font-bold text-purple-600">{{ count(array_filter($courses, fn($c) => $c['status'] === 'active')) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Currently enrolled</p>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium text-gray-900 mb-3">Quick Tips</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 mt-1">•</span>
                            <span>Each quiz allows up to 3 attempts with reworded questions to help reinforce learning</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 mt-1">•</span>
                            <span>Your ability level is calculated using Item Response Theory (1PL model) for personalized difficulty</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 mt-1">•</span>
                            <span>Focus on topics marked "Needs Practice" to improve your overall mastery level</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>