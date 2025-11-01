<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>
        @php
            $resolvedTitle = trim($__env->yieldContent('title'));
        @endphp
        {{ $resolvedTitle !== '' ? $resolvedTitle : __('Something went wrong') }} | {{ config('app.name', 'SumakQuiz') }}
    </title>

    @vite(['resources/css/app.css'])
    @stack('error-head')

    <script>
        (() => {
            const storageKey = 'studentPortalTheme';
            const root = document.documentElement;
            const meta = document.querySelector('meta[name="color-scheme"]');

            const applyTheme = (theme) => {
                const isDark = theme === 'dark';
                root.classList.toggle('dark', isDark);
                root.style.colorScheme = isDark ? 'dark' : 'light';
                root.dataset.theme = isDark ? 'dark' : 'light';
                meta?.setAttribute('content', isDark ? 'dark light' : 'light dark');
            };

            try {
                const storedTheme = window.localStorage.getItem(storageKey);
                if (storedTheme === 'dark' || storedTheme === 'light') {
                    applyTheme(storedTheme);
                    return;
                }
            } catch (error) {
                /* no-op */
            }

            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'dark' : 'light');
        })();
    </script>
</head>
<body class="relative min-h-screen bg-slate-100 text-slate-900 antialiased transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100">
    @php
        $statusCode = trim($__env->yieldContent('code'));
        $title = $resolvedTitle !== '' ? $resolvedTitle : __('Something went wrong');
        $message = trim($__env->yieldContent('message'));
        $message = $message !== '' ? $message : __('We couldn’t load this page right now. Please try again, or contact support if the issue continues.');
    @endphp

    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 right-12 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl dark:bg-emerald-400/10"></div>
        <div class="absolute -bottom-48 left-16 h-80 w-80 rounded-full bg-emerald-600/10 blur-3xl dark:bg-emerald-500/20"></div>
    </div>

    <main class="relative z-10 flex min-h-screen items-center justify-center px-6 py-16">
        <div class="w-full max-w-3xl">
            <div class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 p-10 shadow-xl shadow-emerald-500/10 backdrop-blur dark:border-slate-800/70 dark:bg-slate-900 dark:shadow-emerald-900/20 sm:p-12">
                <div class="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-4 text-emerald-600 dark:text-emerald-300">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/15 text-3xl font-semibold text-emerald-600 shadow-inner shadow-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-200">
                            {{ $statusCode !== '' ? $statusCode : '—' }}
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">{{ __('Error') }}</p>
                            <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl dark:text-slate-100">{{ $title }}</h1>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1 font-medium dark:border-slate-700/70 dark:bg-slate-800/70">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-300"></span>
                            {{ __('System status monitored') }}
                        </span>
                        <time datetime="{{ now()->toIso8601String() }}" class="font-medium">
                            {{ now()->format('M d, Y · H:i') }}
                        </time>
                    </div>
                </div>

                <p class="mt-8 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $message }}
                </p>

                @if (trim($__env->yieldContent('extra')) !== '')
                    <div class="mt-8 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-6 py-4 text-sm text-emerald-700 shadow-inner dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                        @yield('extra')
                    </div>
                @endif

                <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center mt-2">
                    @if (trim($__env->yieldContent('actions')) !== '')
                        @yield('actions')
                    @else
                        <button type="button" onclick="window.history.length ? window.history.back() : window.location.assign('{{ url('/') }}')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-500/60 dark:hover:text-emerald-300 dark:focus-visible:ring-offset-slate-950">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                                <path d="M7.5 15L2.5 10m0 0l5-5M2.5 10H17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ __('Go back') }}
                        </button>

                        <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-slate-950">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                                <path d="M10 3l6 4.5V17a1 1 0 01-1 1h-3.5v-4h-3v4H5a1 1 0 01-1-1V7.5L10 3z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ __('Return home') }}
                        </a>
                    @endif
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                {{ __('If the issue persists, please contact the support team with the error details above.') }}
            </p>
        </div>
    </main>
</body>
</html>