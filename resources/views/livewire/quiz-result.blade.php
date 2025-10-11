<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('student.course.show', $attempt->subtopic->topic->document->course_id) }}" 
           class="text-indigo-600 hover:text-indigo-800">
            ← Back to Course
        </a>
    </div>

    <div class="max-w-4xl mx-auto">
        <!-- Summary Card -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Quiz Results</h1>
                <p class="text-gray-600 mt-2">{{ $attempt->subtopic->name }}</p>
                <p class="text-sm text-gray-500">{{ $attempt->subtopic->topic->name }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Score</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $attempt->score_percentage }}%</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Correct Answers</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $attempt->correct_answers }}/{{ $attempt->total_questions }}</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Time Spent</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $attempt->time_spent_minutes }} min</p>
                </div>
            </div>

            @if($attempt->is_adaptive)
                <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 mb-4">
                    <p class="text-indigo-800">
                        <strong>Adaptive Quiz:</strong> Questions were selected based on your ability level.
                    </p>
                </div>
            @endif

            <div class="flex justify-between items-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                    {{ $attempt->isPassed() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $attempt->isPassed() ? 'Passed' : 'Needs Improvement' }}
                </span>
                <p class="text-sm text-gray-500">Attempt #{{ $attempt->attempt_number }}</p>
            </div>
        </div>

        <!-- AI Feedback -->
        @if($attempt->feedback)
            <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">AI-Powered Feedback</h2>
                
                <div class="prose max-w-none">
                    <p class="text-gray-700 mb-4">{{ $attempt->feedback->feedback_text }}</p>
                </div>

                @if($attempt->feedback->strengths)
                    <div class="mt-6">
                        <h3 class="font-semibold text-green-800 mb-2">Strengths:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            @foreach(json_decode($attempt->feedback->strengths) as $strength)
                                <li>{{ $strength }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($attempt->feedback->areas_to_improve)
                    <div class="mt-6">
                        <h3 class="font-semibold text-yellow-800 mb-2">Areas to Improve:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            @foreach(json_decode($attempt->feedback->areas_to_improve) as $area)
                                <li>{{ $area }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($attempt->feedback->recommendations)
                    <div class="mt-6">
                        <h3 class="font-semibold text-indigo-800 mb-2">Recommendations:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            @foreach(json_decode($attempt->feedback->recommendations) as $recommendation)
                                <li>{{ $recommendation }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                <p class="text-yellow-800">
                    AI feedback is being generated. Please check back in a few moments.
                </p>
            </div>
        @endif

        <!-- Question Review -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Question Review</h2>
            
            <div class="space-y-4">
                @foreach($attempt->responses as $index => $response)
                    <div class="border rounded-lg p-4 {{ $response->is_correct ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-semibold text-gray-900">Question {{ $index + 1 }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $response->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $response->is_correct ? 'Correct' : 'Incorrect' }}
                            </span>
                        </div>
                        
                        <p class="text-gray-700 mb-3">{{ $response->item->question }}</p>
                        
                        <div class="grid grid-cols-1 gap-2 mb-3">
                            @foreach($response->item->options as $option)
                                <div class="p-2 rounded 
                                    {{ $option['option_letter'] === $response->item->correct_answer ? 'bg-green-100' : '' }}
                                    {{ $option['option_letter'] === $response->user_answer && !$response->is_correct ? 'bg-red-100' : '' }}">
                                    <span class="font-semibold">{{ $option['option_letter'] }}.</span>
                                    {{ $option['option_text'] }}
                                    @if($option['option_letter'] === $response->item->correct_answer)
                                        <span class="text-green-600 text-sm">✓ Correct</span>
                                    @endif
                                    @if($option['option_letter'] === $response->user_answer)
                                        <span class="text-gray-600 text-sm">(Your answer)</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($response->item->explanation)
                            <div class="text-sm text-gray-600 mt-2">
                                <strong>Explanation:</strong> {{ $response->item->explanation }}
                            </div>
                        @endif
                        
                        <p class="text-xs text-gray-500 mt-2">Time taken: {{ $response->time_taken_seconds }}s</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex justify-center space-x-4">
            <a href="{{ route('student.course.show', $attempt->subtopic->topic->document->course_id) }}" 
               class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Back to Course
            </a>
            <a href="{{ route('student.quiz.take', $attempt->subtopic_id) }}" 
               class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Retake Quiz
            </a>
        </div>
    </div>
</div>