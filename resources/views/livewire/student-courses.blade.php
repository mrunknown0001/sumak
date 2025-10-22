<div class="mx-auto max-w-7xl space-y-8 text-slate-900 dark:text-slate-100">
    <div class="rounded-2xl border border-slate-200/70 bg-white/90 px-6 py-6 shadow-sm backdrop-blur dark:border-slate-800/60 dark:bg-slate-900/70">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Courses</h1>
        <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">Browse and enroll in available courses</p>
    </div>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-400/40 bg-emerald-100/70 px-4 py-3 text-emerald-800 shadow-sm dark:border-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-100">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-xl border border-red-400/40 bg-red-100/70 px-4 py-3 text-red-800 shadow-sm dark:border-red-500/40 dark:bg-red-900/40 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200/70 bg-white/80 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
        <nav class="flex gap-2 rounded-t-2xl bg-slate-100/70 px-4 py-2 dark:bg-slate-800/70">
            <button
                wire:click="$set('activeTab', 'enrolled')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 {{ $activeTab === 'enrolled' ? 'border border-emerald-400/50 bg-emerald-500/20 text-emerald-600 dark:border-emerald-400/50 dark:bg-emerald-500/20 dark:text-emerald-200' : 'border border-transparent text-slate-500 hover:border-slate-300 hover:bg-white hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800/60 dark:hover:text-slate-200' }}"
            >
                My Enrolled Courses ({{ $enrolledCourses->count() }})
            </button>
            <button
                wire:click="$set('activeTab', 'available')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 {{ $activeTab === 'available' ? 'border border-emerald-400/50 bg-emerald-500/20 text-emerald-600 dark:border-emerald-400/50 dark:bg-emerald-500/20 dark:text-emerald-200' : 'border border-transparent text-slate-500 hover:border-slate-300 hover:bg-white hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800/60 dark:hover:text-slate-200' }}"
            >
                Available Courses ({{ $availableCourses->count() }})
            </button>
        </nav>

        <div class="p-6">
            @if($activeTab === 'enrolled')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($enrolledCourses as $course)
                        <div class="group flex h-full flex-col rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:border-emerald-500/40">
                            <div class="mb-4 space-y-2">
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $course->course_code }}</h3>
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $course->course_title }}</p>
                                @if($course->description)
                                    <p class="text-sm text-slate-500 line-clamp-2 dark:text-slate-400">{{ $course->description }}</p>
                                @endif
                            </div>

                            <div class="mb-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                @if($course->obtlDocument)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/25 dark:text-emerald-200">
                                        ✓ OBTL Available
                                    </span>
                                @endif
                                <p class="flex items-center gap-2 font-medium">
                                    <svg class="h-4 w-4 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    {{ $course->documents_count }} lectures available
                                </p>
                            </div>

                            <div class="mt-auto space-y-3">
                                <a
                                    href="{{ route('student.course.show', $course->id) }}"
                                    class="block w-full rounded-xl bg-emerald-600 px-4 py-3 text-center text-sm font-semibold text-white shadow transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                                >
                                    View Course
                                </a>
                                <button
                                    wire:click="unenroll({{ $course->id }})"
                                    wire:confirm="Are you sure you want to unenroll from this course?"
                                    class="w-full rounded-xl border border-slate-300/60 bg-slate-100/70 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                                >
                                    Unenroll
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
                            <svg class="mx-auto mb-4 h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-slate-100">You're not enrolled in any courses yet.</h3>
                            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Enroll in a course to personalize your learning journey.</p>
                            <button
                                wire:click="$set('activeTab', 'available')"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                            >
                                Browse Available Courses
                            </button>
                        </div>
                    @endforelse
                </div>
            @endif

            @if($activeTab === 'available')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($availableCourses as $course)
                        <div class="group flex h-full flex-col rounded-2xl border border-emerald-200/50 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-xl dark:border-emerald-500/40 dark:bg-slate-900/70 dark:hover:border-emerald-400">
                            <div class="mb-4 space-y-2">
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $course->course_code }}</h3>
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $course->course_title }}</p>
                                @if($course->description)
                                    <p class="text-sm text-slate-500 line-clamp-3 dark:text-slate-400">{{ $course->description }}</p>
                                @endif
                            </div>

                            <div class="mb-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Instructor: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $course->user->name }}</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    {{ $course->documents_count }} lectures
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    {{ $course->enrollments_count }} students enrolled
                                </p>
                                @if($course->obtlDocument)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-500/25 dark:text-emerald-200">
                                        ✓ OBTL Available
                                    </span>
                                @endif
                            </div>

                            <button
                                wire:click="enroll({{ $course->id }})"
                                class="mt-auto w-full rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-4 py-3 text-center text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                            >
                                🎓 Enroll in Course
                            </button>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
                            <svg class="mx-auto mb-4 h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400">No courses available at the moment.</p>
                            <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">Check back later or contact your instructor.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>