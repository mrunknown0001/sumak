<div class="container mx-auto px-4 py-8" x-data="{ 
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
        if (this.timerMode === 'free') return 'bg-gray-400';
        if (this.timerMode === 'pomodoro') return 'bg-purple-500';
        if (this.timeRemaining > 30) return 'bg-green-500';
        if (this.timeRemaining > 10) return 'bg-yellow-500';
        return 'bg-red-500';
    },
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }
}" x-init="$watch('timeRemaining', value => {
    if ((value === 60 || value === 1500) && $wire.quizStarted && !$wire.showFeedback) {
        stopTimer();
        startTimer();
    }
})">

    @if(!$timerMode)
        <!-- Timer Mode Selection Screen -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $subtopic->name }}</h1>
                <p class="text-gray-600 mb-8">{{ $subtopic->topic->name }}</p>
                
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Choose Your Quiz Timer Mode:</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Pomodoro Mode -->
                    <button wire:click="selectTimerMode('pomodoro')" 
                            class="p-6 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition text-left">
                        <div class="flex items-center mb-4">
                            <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="ml-3 text-lg font-semibold text-gray-900">Pomodoro</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">Focused 25-minute study sessions with 5-minute breaks</p>
                        <ul class="text-xs text-gray-500 space-y-1">
                            <li>✓ 25 min work sessions</li>
                            <li>✓ 5 min breaks</li>
                            <li>✓ Best for focused study</li>
                        </ul>
                    </button>

                    <!-- Free Time Mode -->
                    <button wire:click="selectTimerMode('free')" 
                            class="p-6 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition text-left">
                        <div class="flex items-center mb-4">
                            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="ml-3 text-lg font-semibold text-gray-900">Free Time</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">No time pressure - study at your own pace</p>
                        <ul class="text-xs text-gray-500 space-y-1">
                            <li>✓ No timer</li>
                            <li>✓ Take your time</li>
                            <li>✓ Best for deep learning</li>
                        </ul>
                    </button>

                    <!-- Standard Mode -->
                    <button wire:click="selectTimerMode('standard')" 
                            class="p-6 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition text-left">
                        <div class="flex items-center mb-4">
                            <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="ml-3 text-lg font-semibold text-gray-900">Standard</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">60 seconds per question with auto-submit</p>
                        <ul class="text-xs text-gray-500 space-y-1">
                            <li>✓ 60s per question</li>
                            <li>✓ Color-coded timer</li>
                            <li>✓ Tests quick recall</li>
                        </ul>
                    </button>
                </div>
            </div>
        </div>

    @elseif(!$quizStarted)
        <!-- Quiz Start Screen -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $subtopic->name }}</h1>
                <p class="text-gray-600 mb-6">{{ $subtopic->topic->name }}</p>
                
                <div class="bg-indigo-50 rounded-lg p-6 mb-6">
                    <h2 class="font-semibold text-indigo-900 mb-3">Quiz Information:</h2>
                    <ul class="space-y-2 text-indigo-800">
                        <li>• 20 multiple-choice questions</li>
                        <li>• Timer Mode: <strong class="capitalize">{{ $timerMode }}</strong></li>
                        @if($timerMode === 'pomodoro')
                            <li>• 25-minute work sessions with 5-minute breaks</li>
                        @elseif($timerMode === 'free')
                            <li>• No time limit - study at your own pace</li>
                        @else
                            <li>• 60 seconds per question with color-coded timer</li>
                        @endif
                        <li>• Immediate feedback after each answer</li>
                    </ul>
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('timerMode', null)" 
                            class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300">
                        Change Timer Mode
                    </button>
                    <button wire:click="startQuiz" 
                            class="flex-1 bg-indigo-600 text-white py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700">
                        Start Quiz
                    </button>
                </div>
            </div>
        </div>

    @elseif($quizCompleted)
        <!-- Quiz Completed Screen -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mb-6">
                    @if($attempt->score_percentage >= 70)
                        <svg class="w-20 h-20 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-green-600 mt-4">Great Job!</h2>
                    @else
                        <svg class="w-20 h-20 mx-auto text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-yellow-600 mt-4">Keep Practicing!</h2>
                    @endif
                </div>

                <div class="text-center mb-8">
                    <p class="text-5xl font-bold text-gray-900">{{ $attempt->score_percentage }}%</p>
                    <p class="text-gray-600 mt-2">{{ $attempt->correct_answers }} out of {{ $attempt->total_questions }} correct</p>
                </div>

                <div class="flex justify-center space-x-4">
                    <a href="{{ route('student.course.show', $subtopic->topic->document->course_id) }}" 
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Back to Course
                    </a>
                    <a href="{{ route('student.quiz.result', $attempt->id) }}" 
                       class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        View Detailed Results
                    </a>
                </div>
            </div>
        </div>

    @elseif($isBreakTime)
        <!-- Pomodoro Break Screen -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <svg class="w-20 h-20 mx-auto text-purple-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-purple-600 mb-2">Break Time!</h2>
                <p class="text-gray-600 mb-6">Take a 5-minute break. Stretch, hydrate, relax!</p>
                
                <div class="mb-6">
                    <p class="text-4xl font-bold text-gray-900" x-text="formatTime(timeRemaining)"></p>
                    <p class="text-sm text-gray-500 mt-2">Time remaining in break</p>
                </div>
                
                <button wire:click="endBreak" 
                        class="bg-purple-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700">
                    Skip Break & Continue
                </button>
            </div>
        </div>

    @else
        <!-- Quiz Question Screen -->
        <div class="max-w-3xl mx-auto">
            <!-- Timer Bar -->
            @if($timerMode !== 'free')
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Question {{ $currentQuestionIndex + 1 }} of {{ $questions->count() }}</span>
                        @if($timerMode === 'pomodoro')
                            <span class="font-medium text-purple-600" x-text="'Session: ' + formatTime(timeRemaining)"></span>
                        @else
                            <span x-text="timeRemaining + 's'"></span>
                        @endif
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-1000" 
                             :class="getTimerColor()"
                             :style="`width: ${timerMode === 'pomodoro' ? (timeRemaining / {{ $pomodoroSessionTime }}) * 100 : (timeRemaining / 60) * 100}%`"></div>
                    </div>
                </div>
            @else
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Question {{ $currentQuestionIndex + 1 }} of {{ $questions->count() }}</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Free Time Mode - No Rush!
                        </span>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-lg p-8">
                @if($questions->count() > 0)
                    @php $question = $questions[$currentQuestionIndex]; @endphp
                    
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $question->question }}</h2>

                    @if(!$showFeedback)
                        <div class="space-y-3">
                            @foreach($question->options as $option)
                                <button wire:click="$set('selectedAnswer', '{{ $option['option_letter'] }}')" 
                                        class="w-full text-left p-4 rounded-lg border-2 transition
                                               {{ $selectedAnswer === $option['option_letter'] ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <span class="font-semibold">{{ $option['option_letter'] }}.</span>
                                    {{ $option['option_text'] }}
                                </button>
                            @endforeach
                        </div>

                        <button wire:click="submitAnswer" 
                                :disabled="!$wire.selectedAnswer"
                                class="mt-6 w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            Submit Answer
                        </button>
                    @else
                        <!-- Feedback -->
                        <div class="mb-6 p-4 rounded-lg {{ $isCorrect ? 'bg-green-50 border-2 border-green-200' : 'bg-red-50 border-2 border-red-200' }}">
                            <p class="font-semibold {{ $isCorrect ? 'text-green-800' : 'text-red-800' }}">
                                {{ $isCorrect ? '✓ Correct!' : '✗ Incorrect' }}
                            </p>
                            @if(!$isCorrect)
                                <p class="text-red-700 mt-2">The correct answer is: <strong>{{ $correctAnswer }}</strong></p>
                            @endif
                            @if($question->explanation)
                                <p class="text-gray-700 mt-3"><strong>Explanation:</strong> {{ $question->explanation }}</p>
                            @endif
                        </div>

                        <button wire:click="nextQuestion" x-on:click="stopTimer()"
                                class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700">
                            {{ $currentQuestionIndex + 1 < $questions->count() ? 'Next Question' : 'Complete Quiz' }}
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>