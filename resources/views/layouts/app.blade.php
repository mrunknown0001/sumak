<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Student Portal' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles
    <style data-student-layout>
        :root {
            --student-sidebar-width: 18rem;
        }

        [data-layout-root] {
            min-height: 100vh;
        }

        [data-layout-root][data-sidebar-collapsed="true"] {
            --student-sidebar-width: 6rem;
        }

        [data-main-wrapper] {
            min-width: 0;
            width: 100%;
            margin-left: 0;
            transition: margin-left 0.3s ease, padding 0.3s ease;
        }

        [data-main-wrapper] > * {
            width: 100%;
        }

        [data-student-content] {
            min-width: 0;
        }

        @media (min-width: 1024px) {
            [data-main-wrapper] {
                margin-left: var(--student-sidebar-width);
            }
        }

        @media (max-width: 1023px) {
            [data-student-sidebar] {
                width: min(20rem, 92vw);
                max-width: 92vw;
            }
        }

        [data-layout-root][data-sidebar-collapsed="true"] [data-student-sidebar] {
            overflow-x: hidden;
        }

        [data-layout-root][data-sidebar-collapsed="true"] [data-student-sidebar] [data-sidebar-header] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        [data-layout-root][data-sidebar-collapsed="true"] [data-student-sidebar] [data-sidebar-controls] {
            gap: 0.375rem;
        }

        [data-layout-root][data-sidebar-collapsed="true"] [data-student-sidebar] [data-sidebar-footer-action] {
            justify-content: center;
            gap: 0.25rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
    </style>
    <script>
        (() => {
            const storageKey = 'studentPortalTheme';
            const classList = document.documentElement.classList;

            const setInitialColorScheme = (theme) => {
                const meta = document.querySelector('meta[name="color-scheme"]');
                const nextTheme = theme === 'dark' ? 'dark' : 'light';
                meta?.setAttribute('content', nextTheme === 'dark' ? 'dark light' : 'light dark');
                document.documentElement.style.colorScheme = nextTheme;
                document.documentElement.dataset.theme = nextTheme;
            };

            try {
                const storedTheme = window.localStorage.getItem(storageKey);
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                    classList.add('dark');
                    setInitialColorScheme('dark');
                } else {
                    classList.remove('dark');
                    setInitialColorScheme('light');
                }
            } catch (error) {
                /* no-op */
            }
        })();
    </script>
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100">
    @php
        $navItems = [
            [
                'label' => 'Dashboard',
                'href' => route('student.dashboard'),
                'icon' => 'home',
                'isActive' => request()->routeIs('student.dashboard'),
            ],
            [
                'label' => 'Courses',
                'href' => route('student.courses'),
                'icon' => 'courses',
                'isActive' => request()->routeIs('student.courses') || request()->routeIs('student.course.*'),
            ],
            [
                'label' => 'Quiz Hub',
                'href' => route('student.dashboard') . '#recent-quizzes',
                'icon' => 'quiz',
                'isActive' => request()->routeIs('student.quiz.*'),
            ],
        ];

        $computedPageTitle = $pageTitle
            ?? ($headerTitle ?? match (true) {
                request()->routeIs('student.dashboard') => 'Dashboard',
                request()->routeIs('student.courses') => 'Courses',
                request()->routeIs('student.course.*') => 'Course Details',
                request()->routeIs('student.quiz.take') => 'Take Quiz',
                request()->routeIs('student.quiz.result') => 'Quiz Results',
                default => 'Student Portal',
            });
    @endphp

    <div data-layout-root class="relative min-h-screen lg:flex">
        <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-900/60 backdrop-blur-sm transition-opacity dark:bg-slate-950/70 lg:hidden"></div>

        <aside data-student-sidebar data-sidebar-collapsed="false" class="fixed inset-y-0 left-0 z-40 flex w-[var(--student-sidebar-width)] flex-col overflow-y-auto overflow-x-hidden border-r border-slate-200 bg-white shadow-xl transition-transform duration-300 ease-in-out transform -translate-x-full dark:border-slate-800 dark:bg-slate-900 lg:h-screen lg:flex-shrink-0 lg:translate-x-0">
            <div data-sidebar-header class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div data-sidebar-brand class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-lg font-bold text-white">SP</span>
                    <div data-sidebar-text>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-200">Student Portal</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Personalized learning hub</p>
                    </div>
                </div>
                <div data-sidebar-controls class="flex items-center gap-2">
                    <button type="button" class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:hidden" data-sidebar-close aria-label="Close sidebar">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                            <path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <button type="button" class="hidden rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:flex" data-sidebar-collapse-toggle aria-label="Collapse sidebar" aria-pressed="false">
                        <svg data-icon="collapse" class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                            <path d="M11.5 5l-4 5 4 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <svg data-icon="expand" class="hidden h-5 w-5" viewBox="0 0 20 20" fill="none">
                            <path d="M8.5 5l4 5-4 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6">
                <ul class="space-y-1.5">
                    @foreach ($navItems as $item)
                        @php
                            $linkBase = $item['isActive']
                                ? 'bg-emerald-50 text-emerald-600 shadow-inner shadow-emerald-100 dark:bg-emerald-500/20 dark:text-emerald-300 dark:shadow-none'
                                : 'text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 dark:text-slate-300 dark:hover:text-emerald-300 dark:hover:bg-emerald-500/10';
                            $iconBase = $item['isActive']
                                ? 'border-emerald-200 text-emerald-600 dark:border-emerald-400/60 dark:text-emerald-300'
                                : 'border-slate-200 text-slate-400 group-hover:border-emerald-200 group-hover:text-emerald-500 dark:border-slate-700 dark:text-slate-500 dark:group-hover:border-emerald-400/60 dark:group-hover:text-emerald-300';
                        @endphp
                        <li>
                            <a href="{{ $item['href'] }}"
                               data-nav-link
                               data-sidebar-label="{{ $item['label'] }}"
                               class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-colors {{ $linkBase }}"
                               aria-label="{{ $item['label'] }}"
                               aria-current="{{ $item['isActive'] ? 'page' : 'false' }}">
                                <span data-sidebar-icon class="flex h-9 w-9 items-center justify-center rounded-lg border bg-white transition-colors dark:border-slate-700 dark:bg-slate-800 {{ $iconBase }}">
                                    @switch($item['icon'])
                                        @case('home')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                <path d="M3 11.5L12 4l9 7.5V21a1 1 0 01-1 1h-5v-6h-6v6H4a1 1 0 01-1-1v-9.5z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            @break
                                        @case('courses')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            @break
                                        @case('quiz')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                                <path d="M8 9h8m-8 4h5m-9 6h16a1 1 0 001-1V6.5a1 1 0 00-.553-.894l-8-4a1 1 0 00-.894 0l-8 4A1 1 0 003 6.5V18a1 1 0 001 1z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            @break
                                    @endswitch
                                </span>
                                <span data-sidebar-text>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="border-t border-slate-200 px-6 py-5 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 font-semibold dark:bg-emerald-500/20 dark:text-emerald-200">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                    </div>
                    <div data-sidebar-text class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ auth()->user()->name ?? 'Student' }}</p>
                        <p class="truncate text-xs text-slate-400 dark:text-slate-500">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                </div>
                <a href="{{ route('logout.get') }}"
                   data-sidebar-footer-action
                   data-sidebar-label="Sign out"
                   class="mt-4 group flex w-full items-center justify-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-500 transition hover:border-emerald-200 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-emerald-400/60 dark:hover:text-emerald-200"
                   aria-label="Sign out">
                    <span data-sidebar-icon class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition-colors group-hover:border-emerald-200 group-hover:text-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500 dark:group-hover:border-emerald-400/60 dark:group-hover:text-emerald-300">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                            <path d="M15 3h4a1 1 0 011 1v16a1 1 0 01-1 1h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span data-sidebar-text>Sign out</span>
                </a>
            </div>
        </aside>

        <div data-main-wrapper class="flex min-h-screen flex-col transition-[margin,padding] duration-300 ease-in-out">
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800/80 dark:bg-slate-900/80">
                <div class="flex w-full items-center justify-between px-4 py-4 sm:px-6 lg:px-8 xl:px-12">
                    <div class="flex flex-1 items-center gap-3">
                        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-emerald-200 hover:text-emerald-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-emerald-400/60 dark:hover:text-emerald-200 dark:focus-visible:ring-offset-slate-900 lg:hidden" data-sidebar-toggle aria-label="Open sidebar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <h1 class="text-xl font-semibold text-slate-800 sm:text-2xl dark:text-slate-100">{{ $computedPageTitle }}</h1>
                            @isset($pageSubtitle)
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $pageSubtitle }}</p>
                            @endisset
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300 md:flex">
                            <span class="mr-1 h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            {{ now()->format('M d, Y') }}
                        </div>
                        <span class="hidden text-sm text-slate-500 dark:text-slate-300 sm:inline">Level up your mastery today ✨</span>
                        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-emerald-200 hover:text-emerald-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-emerald-400/60 dark:hover:text-emerald-200 dark:focus-visible:ring-offset-slate-900" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
                            <span class="sr-only">Toggle dark mode</span>
                            <svg data-theme-icon="sun" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <path d="M12 4V2m0 20v-2m8-8h2M2 12h2m14.142 7.071l1.414 1.414M4.444 4.444l1.414 1.414m0 12.728l-1.414 1.414m14.142-14.142l1.414-1.414M12 18a6 6 0 100-12 6 6 0 000 12z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <svg data-theme-icon="moon" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 0010.6 9.79z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div data-student-content class="mx-auto flex w-full max-w-7xl flex-col gap-6">
                    {{ $slot }}
                </div>
            </main>

            <footer class="border-t border-slate-200/80 bg-white/60 px-4 py-4 text-xs text-slate-500 dark:border-slate-800/80 dark:bg-slate-900/60 dark:text-slate-400 sm:px-6 lg:px-8">
                <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ date('Y') }} SumakQuiz. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-emerald-600 dark:hover:text-emerald-300">Privacy</a>
                        <a href="#" class="hover:text-emerald-600 dark:hover:text-emerald-300">Terms</a>
                        <a href="#" class="hover:text-emerald-600 dark:hover:text-emerald-300">Support</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('[data-student-sidebar]');
            const overlay = document.querySelector('[data-sidebar-overlay]');
            const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
            const closeButtons = document.querySelectorAll('[data-sidebar-close]');
            const themeToggle = document.querySelector('[data-theme-toggle]');
            const themeIcons = {
                sun: themeToggle?.querySelector('[data-theme-icon="sun"]'),
                moon: themeToggle?.querySelector('[data-theme-icon="moon"]'),
            };
            const themeStorageKey = 'studentPortalTheme';
            const persistKey = 'studentSidebarExpanded';
            const colorSchemeMeta = document.querySelector('meta[name="color-scheme"]');

            const syncColorScheme = (theme) => {
                const nextTheme = theme === 'dark' ? 'dark' : 'light';
                colorSchemeMeta?.setAttribute('content', nextTheme === 'dark' ? 'dark light' : 'light dark');
                document.documentElement.style.colorScheme = nextTheme;
                document.documentElement.dataset.theme = nextTheme;
            };

            let storedThemeSnapshot = null;
            try {
                storedThemeSnapshot = localStorage.getItem(themeStorageKey);
            } catch (error) {
                console.warn('[StudentPortal] theme storage read failed', error);
            }

            console.info('[StudentPortal] bootstrap', {
                hasSidebar: !!sidebar,
                hasThemeToggle: !!themeToggle,
                storedThemeSnapshot,
                viewportWidth: window.innerWidth,
            });

            if (sidebar) {
                const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;
                const isOpen = () => !sidebar.classList.contains('-translate-x-full');

                const layoutRoot = document.querySelector('[data-layout-root]');
                const mainWrapper = document.querySelector('[data-main-wrapper]');
                const collapseToggle = sidebar.querySelector('[data-sidebar-collapse-toggle]');
                const sidebarTexts = sidebar.querySelectorAll('[data-sidebar-text]');
                const navLinks = sidebar.querySelectorAll('[data-nav-link]');
                const iconWrappers = sidebar.querySelectorAll('[data-sidebar-icon]');
                const footerActions = sidebar.querySelectorAll('[data-sidebar-footer-action]');
                const collapseStorageKey = 'studentSidebarCollapsed';
                const sidebarWidthVariable = '--student-sidebar-width';
                const expandedSidebarWidth = '18rem';
                const collapsedSidebarWidth = '6rem';

                const setSidebarWidthVariable = (value) => {
                    const target = layoutRoot ?? document.documentElement;
                    target.style.setProperty(sidebarWidthVariable, value);
                };

                setSidebarWidthVariable(expandedSidebarWidth);

                console.debug('[StudentPortal] sidebar detected', {
                    initialClassList: sidebar.className,
                    initialIsDesktop: isDesktop(),
                    initialIsOpen: isOpen(),
                    viewportWidth: window.innerWidth,
                });

                const applyOverlayState = (open) => {
                    if (!overlay) {
                        console.debug('[StudentPortal] overlay absent, skipping overlay controls');
                        return;
                    }

                    if (isDesktop()) {
                        overlay.classList.add('hidden');
                        overlay.classList.remove('opacity-100');
                        document.body.classList.remove('overflow-hidden');
                        return;
                    }

                    overlay.classList.toggle('hidden', !open);
                    overlay.classList.toggle('opacity-100', open);
                    document.body.classList.toggle('overflow-hidden', open);
                };

                const applyCollapsedState = (collapsed, persist = true) => {
                    const resolvedCollapsed = collapsed && isDesktop();
                    const widthValue = resolvedCollapsed ? collapsedSidebarWidth : expandedSidebarWidth;

                    sidebar.dataset.sidebarCollapsed = resolvedCollapsed ? 'true' : 'false';
                    layoutRoot?.setAttribute('data-sidebar-collapsed', resolvedCollapsed ? 'true' : 'false');
                    mainWrapper?.setAttribute('data-sidebar-collapsed', resolvedCollapsed ? 'true' : 'false');

                    collapseToggle?.setAttribute('aria-pressed', resolvedCollapsed ? 'true' : 'false');
                    collapseToggle?.setAttribute('aria-label', resolvedCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
                    collapseToggle?.querySelector('[data-icon="collapse"]')?.classList.toggle('hidden', resolvedCollapsed);
                    collapseToggle?.querySelector('[data-icon="expand"]')?.classList.toggle('hidden', !resolvedCollapsed);

                    sidebarTexts.forEach((element) => {
                        element.classList.toggle('hidden', resolvedCollapsed);
                    });

                    navLinks.forEach((link) => {
                        link.classList.toggle('justify-center', resolvedCollapsed);
                        link.classList.toggle('px-3', resolvedCollapsed);
                        link.classList.toggle('px-4', !resolvedCollapsed);
                        link.classList.toggle('gap-0', resolvedCollapsed);
                        link.classList.toggle('gap-3', !resolvedCollapsed);

                        if (resolvedCollapsed) {
                            const label = link.dataset.sidebarLabel ?? link.getAttribute('aria-label') ?? '';
                            if (label) {
                                link.setAttribute('title', label);
                            }
                        } else {
                            link.removeAttribute('title');
                        }
                    });

                    iconWrappers.forEach((icon) => {
                        icon.classList.toggle('h-11', resolvedCollapsed);
                        icon.classList.toggle('w-11', resolvedCollapsed);
                        icon.classList.toggle('rounded-xl', resolvedCollapsed);
                        icon.classList.toggle('h-9', !resolvedCollapsed);
                        icon.classList.toggle('w-9', !resolvedCollapsed);
                        icon.classList.toggle('rounded-lg', !resolvedCollapsed);
                    });

                    footerActions.forEach((action) => {
                        if (resolvedCollapsed) {
                            const label = action.dataset.sidebarLabel ?? action.getAttribute('aria-label') ?? '';
                            if (label) {
                                action.setAttribute('title', label);
                            }
                        } else {
                            action.removeAttribute('title');
                        }
                    });

                    setSidebarWidthVariable(widthValue);

                    if (persist && isDesktop()) {
                        try {
                            localStorage.setItem(collapseStorageKey, resolvedCollapsed ? 'true' : 'false');
                        } catch (error) {
                            console.warn('[StudentPortal] collapse persistence failed', error);
                        }
                    }
                };

                const setSidebar = (open, persist = true) => {
                    console.debug('[StudentPortal] setSidebar', {
                        open,
                        persist,
                        isDesktop: isDesktop(),
                        overlayPresent: !!overlay,
                    });

                    sidebar.classList.toggle('-translate-x-full', !open);
                    sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
                    sidebar.setAttribute('aria-expanded', open ? 'true' : 'false');
                    sidebar.dataset.sidebarExpanded = open ? 'true' : 'false';
                    applyOverlayState(open);

                    if (persist && isDesktop()) {
                        try {
                            localStorage.setItem(persistKey, open ? 'true' : 'false');
                        } catch (error) {
                            console.warn('[StudentPortal] sidebar persistence failed', error);
                        }
                    }

                    if (!isDesktop()) {
                        applyCollapsedState(false, false);
                    }
                };

                const initializeSidebar = () => {
                    let storedOpen = null;
                    let storedCollapsed = null;

                    try {
                        storedOpen = localStorage.getItem(persistKey);
                        storedCollapsed = localStorage.getItem(collapseStorageKey);
                    } catch (error) {
                        console.warn('[StudentPortal] sidebar storage read failed', error);
                    }

                    const shouldOpen = isDesktop() ? storedOpen !== 'false' : false;
                    const shouldCollapse = isDesktop() ? storedCollapsed === 'true' : false;

                    console.debug('[StudentPortal] initializeSidebar', {
                        storedOpen,
                        storedCollapsed,
                        shouldOpen,
                        shouldCollapse,
                        isDesktop: isDesktop(),
                    });

                    setSidebar(shouldOpen, false);
                    applyCollapsedState(shouldCollapse, false);
                };

                toggleButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        console.debug('[StudentPortal] sidebar toggle clicked');
                        setSidebar(!isOpen());
                    });
                });

                collapseToggle?.addEventListener('click', () => {
                    if (!isDesktop()) {
                        return;
                    }

                    const nextState = sidebar.dataset.sidebarCollapsed !== 'true';
                    console.debug('[StudentPortal] sidebar collapse toggled', {
                        previousState: sidebar.dataset.sidebarCollapsed === 'true',
                        nextState,
                    });
                    applyCollapsedState(nextState);
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        console.debug('[StudentPortal] sidebar close clicked');
                        setSidebar(false);
                    });
                });

                overlay?.addEventListener('click', () => {
                    console.debug('[StudentPortal] overlay clicked');
                    setSidebar(false);
                });

                document.addEventListener('keyup', (event) => {
                    if (event.key === 'Escape' && isOpen() && !isDesktop()) {
                        console.debug('[StudentPortal] escape key closing sidebar');
                        setSidebar(false);
                    }
                });

                window.addEventListener('resize', () => {
                    if (isDesktop()) {
                        let storedOpen = 'true';
                        let storedCollapsed = 'false';

                        try {
                            storedOpen = localStorage.getItem(persistKey) ?? 'true';
                            storedCollapsed = localStorage.getItem(collapseStorageKey) ?? 'false';
                        } catch (error) {
                            console.warn('[StudentPortal] resize storage read failed', error);
                        }

                        console.debug('[StudentPortal] resize desktop mode', {
                            storedOpen,
                            storedCollapsed,
                        });

                        setSidebar(storedOpen !== 'false', false);
                        applyCollapsedState(storedCollapsed === 'true', false);
                    } else {
                        if (isOpen()) {
                            console.debug('[StudentPortal] resize mobile collapse sidebar');
                            setSidebar(false, false);
                        }
                        applyCollapsedState(false, false);
                    }
                });

                initializeSidebar();
            }

            const applyTheme = (theme) => {
                const isDark = theme === 'dark';

                console.debug('[StudentPortal] applyTheme', {
                    theme,
                    isDark,
                    hasToggle: !!themeToggle,
                });

                document.documentElement.classList.toggle('dark', isDark);
                syncColorScheme(isDark ? 'dark' : 'light');
                themeToggle?.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                themeIcons.sun?.classList.toggle('hidden', isDark);
                themeIcons.moon?.classList.toggle('hidden', !isDark);

                requestAnimationFrame(() => {
                    const htmlClassList = document.documentElement.className;
                    const bodyBg = getComputedStyle(document.body).backgroundColor;
                    const headerBg = getComputedStyle(document.querySelector('header')).backgroundColor;

                    console.debug('[StudentPortal] post-applyTheme snapshot', {
                        htmlClassList,
                        bodyBg,
                        headerBg,
                    });
                });
            };

            const getStoredTheme = () => {
                try {
                    return localStorage.getItem(themeStorageKey);
                } catch (error) {
                    return null;
                }
            };

            const setTheme = (theme, persist = true) => {
                const nextTheme = theme === 'dark' ? 'dark' : 'light';
                applyTheme(nextTheme);
                if (persist) {
                    try {
                        localStorage.setItem(themeStorageKey, nextTheme);
                    } catch (error) {
                        /* no-op */
                    }
                }
            };

            const resolveInitialTheme = () => {
                const stored = getStoredTheme();
                if (stored) {
                    return stored;
                }

                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };

            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            const initialTheme = resolveInitialTheme();
            console.info('[StudentPortal] initial theme', { initialTheme });
            applyTheme(initialTheme);

            themeToggle?.addEventListener('click', () => {
                const isCurrentlyDark = document.documentElement.classList.contains('dark');
                const nextTheme = isCurrentlyDark ? 'light' : 'dark';

                console.info('[StudentPortal] theme toggle', {
                    previousTheme: isCurrentlyDark ? 'dark' : 'light',
                    nextTheme,
                });

                setTheme(nextTheme);
            });

            mediaQuery.addEventListener('change', (event) => {
                if (!getStoredTheme()) {
                    applyTheme(event.matches ? 'dark' : 'light');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>