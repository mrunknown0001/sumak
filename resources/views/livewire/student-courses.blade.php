<div class="mx-auto max-w-7xl space-y-8 text-slate-900 dark:text-slate-100">
    <div class="rounded-2xl border border-slate-200/70 bg-white/90 px-6 py-6 shadow-sm backdrop-blur dark:border-slate-800/60 dark:bg-slate-900/70">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Courses</h1>
                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">Browse, create, and enroll in courses tailored to your learning goals.</p>
            </div>
            <button
                wire:click="openCreateCourseModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-5 py-3 text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400 dark:focus-visible:ring-offset-slate-900"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Course
            </button>
        </div>
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
        <nav class="flex flex-wrap gap-2 rounded-t-2xl bg-slate-100/70 px-4 py-2 dark:bg-slate-800/70">
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
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/25 dark:text-emerald-200">
                                        {{ \Illuminate\Support\Str::headline($course->workflow_stage ?? \App\Models\Course::WORKFLOW_STAGE_DRAFT) }} stage
                                    </span>

                                    @if($course->obtlDocument)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/25 dark:text-emerald-200">
                                            ✓ OBTL Available
                                        </span>
                                    @endif
                                </div>
                                <p class="flex items-center gap-2 font-medium">
                                    <svg class="h-4 w-4 text-emerald-500 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    {{ $course->documents_count ?? 0 }} {{ \Illuminate\Support\Str::plural('learning material', $course->documents_count) }}
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
                            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Enroll in an existing course or create your own to begin.</p>
                            <div class="flex flex-wrap justify-center gap-3">
                                <button
                                    wire:click="$set('activeTab', 'available')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                                >
                                    Browse Available Courses
                                </button>
                                <button
                                    wire:click="openCreateCourseModal"
                                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-400/50 bg-white px-6 py-2 text-sm font-semibold text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-emerald-500/60 dark:bg-slate-900 dark:text-emerald-300 dark:hover:border-emerald-400/80 dark:hover:bg-emerald-500/10"
                                >
                                    Create a Course
                                </button>
                            </div>
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
                                {{-- <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Instructor: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $course->user->name }}</span>
                                </p> --}}
                                <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    {{ $course->documents_count }} {{ \Illuminate\Support\Str::plural('learning material', $course->documents_count) }}
                                </p>
                                {{-- <p class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    {{ $course->enrollments_count }} students enrolled
                                </p> --}}
                                <div class="flex flex-wrap gap-2">
                                    @if($course->obtlDocument)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-500/25 dark:text-emerald-200">
                                            ✓ OBTL Available
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-600 dark:bg-amber-500/25 dark:text-amber-200">
                                            Awaiting OBTL upload
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <button
                                wire:click="enroll({{ $course->id }})"
                                class="mt-auto w-full rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-4 py-3 text-center text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                            >
                                🎓 Enroll in Course
                            </button>

                            @if($course->user_id == auth()->id())
                                <button
                                    wire:click="deleteCourse({{ $course->id }})"
                                    wire:confirm="Are you sure you want to delete this course? This action cannot be undone."
                                    class="mt-auto w-full rounded-xl bg-gradient-to-r from-red-600 to-red-700 px-4 py-3 text-center text-sm font-semibold text-red-600 hover:cursor-pointer shadow transition hover:from-red-500 hover:to-red-600 dark:from-red-500 dark:to-red-600 dark:hover:from-red-400 dark:hover:to-red-500"
                                >
                                    Delete Course
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-slate-200/70 bg-white/90 p-12 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/70">
                            <svg class="mx-auto mb-4 h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400">No courses available at the moment.</p>
                            <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">Check back later or create your own course to begin learning.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    @if($showCreateCourseModal)
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center px-4"
            wire:keydown.escape.window="closeCreateCourseModal"
        >
            <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" wire:click="closeCreateCourseModal"></div>

            <div class="relative z-10 w-full max-w-2xl rounded-3xl border border-slate-200/70 bg-white p-6 shadow-2xl dark:border-slate-800/70 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Create a Course</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Provide the course details. You can optionally upload the OBTL document now or add it later from the course view.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeCreateCourseModal"
                        class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        aria-label="Close create course modal"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                            <path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="createCourse" class="mt-6 space-y-6">
                    @error('createCourse')
                        <div class="rounded-xl border border-red-400/50 bg-red-100/70 px-4 py-2 text-sm font-semibold text-red-700 dark:border-red-500/50 dark:bg-red-900/40 dark:text-red-200">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="newCourseCode">
                                Course Code <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="newCourseCode"
                                type="text"
                                wire:model.defer="newCourse.course_code"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                                placeholder="e.g. MATH101"
                            />
                            @error('newCourse.course_code')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="newCourseTitle">
                                Course Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="newCourseTitle"
                                type="text"
                                wire:model.defer="newCourse.course_title"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                                placeholder="Descriptive course title"
                            />
                            @error('newCourse.course_title')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="newCourseDescription">
                                Description
                            </label>
                            <textarea
                                id="newCourseDescription"
                                rows="4"
                                wire:model.defer="newCourse.description"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/40"
                                placeholder="What will learners achieve in this course?"
                            ></textarea>
                            @error('newCourse.description')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="newCourseObtl">
                                OBTL Document <span class="text-slate-400">(optional)</span>
                            </label>
                            <input
                                id="newCourseObtl"
                                type="file"
                                accept="application/pdf"
                                wire:model="newCourseObtl"
                                class="w-full rounded-xl border border-dashed border-slate-300 bg-white px-3 py-9 text-sm text-slate-500 shadow-inner transition hover:border-emerald-300 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-emerald-400 dark:focus:border-emerald-500/60 dark:focus:ring-emerald-500/40"
                            />
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                PDF only. Maximum file size: 10&nbsp;MB.
                            </p>
                            <div
                                wire:loading.flex
                                wire:target="newCourseObtl"
                                class="items-center gap-2 rounded-xl border border-emerald-300/60 bg-emerald-100/60 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200"
                            >
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Uploading OBTL document...
                            </div>
                            @error('newCourseObtl')
                                <p class="text-xs font-semibold text-red-500 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="closeCreateCourseModal"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800/70"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="createCourse,newCourseObtl"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:from-emerald-500 hover:to-blue-500 disabled:opacity-70 dark:from-emerald-500 dark:to-blue-500 dark:hover:from-emerald-400 dark:hover:to-blue-400"
                        >
                            <svg
                                wire:loading
                                wire:target="createCourse,newCourseObtl"
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>
                                @if($creatingCourse)
                                    Creating...
                                @else
                                    Create Course
                                @endif
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('delete-course', event => {
        alert(event.detail.message);
    });
</script>
@endpush