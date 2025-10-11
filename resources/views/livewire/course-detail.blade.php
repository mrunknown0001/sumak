<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('student.courses') }}" class="text-indigo-600 hover:text-indigo-800">
            ← Back to My Courses
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900">{{ $course->course_code }}</h1>
                <p class="text-xl text-gray-600 mt-2">{{ $course->course_title }}</p>
                @if($course->description)
                    <p class="text-gray-500 mt-2">{{ $course->description }}</p>
                @endif
                
                <div class="mt-4 flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                        ✓ Enrolled
                    </span>
                    @if($course->obtlDocument)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            ✓ OBTL Available
                        </span>
                    @endif
                    <span class="text-gray-500">{{ $course->documents->count() }} lectures</span>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($documents as $document)
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $document->title }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Uploaded {{ $document->uploaded_at->diffForHumans() }} • {{ $document->formatted_file_size }}
                        </p>
                    </div>
                    
                    @if($document->hasTos())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ✓ Ready for Quizzes
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Processing...
                        </span>
                    @endif
                </div>

                @if($document->content_summary)
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-2">Lecture Summary:</h4>
                        <p class="text-sm text-gray-600">{{ $document->content_summary }}</p>
                    </div>
                @endif

                @if($document->hasTos() && $document->topics->isNotEmpty())
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            Available Quizzes:
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($document->topics as $topic)
                                @foreach($topic->subtopics as $subtopic)
                                    <a href="{{ route('student.quiz.take', $subtopic->id) }}" 
                                       class="flex justify-between items-center p-4 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg hover:from-indigo-100 hover:to-blue-100 transition border border-indigo-200">
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-gray-900">{{ $subtopic->name }}</span>
                                            <p class="text-xs text-gray-500 mt-1">{{ $topic->name }}</p>
                                        </div>
                                        <div class="text-right ml-4">
                                            <span class="text-sm font-semibold text-indigo-600">{{ $subtopic->items()->count() }}</span>
                                            <span class="text-xs text-gray-500">questions</span>
                                        </div>
                                    </a>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500">No lecture materials available yet.</p>
                <p class="text-sm text-gray-400 mt-2">Your instructor will upload materials soon.</p>
            </div>
        @endforelse
    </div>
</div>