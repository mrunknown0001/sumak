<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Courses</h1>
        <p class="text-gray-600 mt-1">Browse and enroll in available courses</p>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'enrolled')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                           {{ $activeTab === 'enrolled' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                My Enrolled Courses ({{ $enrolledCourses->count() }})
            </button>
            <button wire:click="$set('activeTab', 'available')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                           {{ $activeTab === 'available' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Available Courses ({{ $availableCourses->count() }})
            </button>
        </nav>
    </div>

    <!-- Enrolled Courses Tab -->
    @if($activeTab === 'enrolled')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($enrolledCourses as $course)
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $course->course_code }}</h3>
                        <p class="text-gray-600">{{ $course->course_title }}</p>
                        @if($course->description)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $course->description }}</p>
                        @endif
                    </div>

                    <div class="space-y-2 mb-4">
                        @if($course->obtlDocument)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ OBTL Available
                            </span>
                        @endif
                        <p class="text-sm text-gray-500">
                            <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            {{ $course->documents_count }} lectures available
                        </p>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('student.course.show', $course->id) }}" 
                           class="block w-full text-center bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                            View Course
                        </a>
                        <button wire:click="unenroll({{ $course->id }})" 
                                wire:confirm="Are you sure you want to unenroll from this course?"
                                class="w-full text-center bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                            Unenroll
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 bg-white rounded-lg shadow">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p class="text-gray-500 mb-4">You're not enrolled in any courses yet.</p>
                    <button wire:click="$set('activeTab', 'available')" 
                            class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Browse Available Courses
                    </button>
                </div>
            @endforelse
        </div>
    @endif

    <!-- Available Courses Tab -->
    @if($activeTab === 'available')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($availableCourses as $course)
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $course->course_code }}</h3>
                        <p class="text-gray-600">{{ $course->course_title }}</p>
                        @if($course->description)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-3">{{ $course->description }}</p>
                        @endif
                    </div>

                    <div class="space-y-2 mb-4">
                        <p class="text-sm text-gray-500">
                            <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Instructor: {{ $course->user->name }}
                        </p>
                        <p class="text-sm text-gray-500">
                            <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            {{ $course->documents_count }} lectures
                        </p>
                        <p class="text-sm text-gray-500">
                            <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            {{ $course->enrollments_count }} students enrolled
                        </p>
                        @if($course->obtlDocument)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                ✓ OBTL Available
                            </span>
                        @endif
                    </div>

                    <button wire:click="enroll({{ $course->id }})" 
                            class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                        Enroll in Course
                    </button>
                </div>
            @empty
                <div class="col-span-full text-center py-12 bg-white rounded-lg shadow">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500">No courses available at the moment.</p>
                    <p class="text-sm text-gray-400 mt-2">Check back later or contact your instructor.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>