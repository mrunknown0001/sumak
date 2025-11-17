<x-filament-panels::page>
    <div class="mx-auto max-w-7xl space-y-8 px-4 pb-10 pt-6 sm:px-6 lg:px-8 rounded-xl bg-white dark:bg-gray-900 shadow-sm text-gray-800 dark:text-gray-200">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white/95 shadow-xl shadow-slate-900/5 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
            <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 px-6 py-6 sm:px-8 sm:py-8 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800">
                <div class="absolute inset-0 opacity-30">
                    <div class="h-full w-full bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.25),_transparent_60%)] dark:bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.8),_transparent_65%)]"></div>
                </div>
                <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold tracking-wide text-white sm:text-2xl">Table of Specification (ToS)</h2>
                        <p class="mt-2 text-sm text-slate-200/90 lg:text-base">
                            Generated {{ $record->generated_at?->timezone(config('app.timezone'))->format('F d, Y g:i A') ?? '—' }}
                            for <span class="font-semibold text-white/90">{{ $course?->course_title ?? '—' }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-right text-xs font-medium uppercase tracking-wider text-slate-200/80">
                        <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 backdrop-blur-sm">
                            {{ $record->document?->title ?? 'Learning material unavailable' }}
                        </span>
                        {{-- <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 backdrop-blur-sm">
                            {{ $record->lots_percentage ? number_format((float) $record->lots_percentage, 0) . '%' : '—' }} Bloom's Focus
                        </span> --}}
                    </div>
                </div>
            </div>

            <div class="px-6 py-6 sm:px-8 sm:py-8">
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50/80 p-5 text-sm font-medium text-slate-600 shadow-inner shadow-white/40 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-300">
                        <dt class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Course</dt>
                        <dd class="mt-2 text-base font-semibold text-slate-800 dark:text-white">
                            {{ $course?->course_title ?? 'Not available' }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-slate-50/80 p-5 text-sm font-medium text-slate-600 shadow-inner shadow-white/40 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-300">
                        <dt class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Learning Material</dt>
                        <dd class="mt-2 text-base font-semibold text-slate-800 dark:text-white">
                            {{ $record->document?->title ?? 'Not available' }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-slate-50/80 p-5 text-sm font-medium text-slate-600 shadow-inner shadow-white/40 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-300">
                        <dt class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Items</dt>
                        <dd class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $totals['items'] }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-slate-50/80 p-5 text-sm font-medium text-slate-600 shadow-inner shadow-white/40 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-300">
                        <dt class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Bloom's Focus</dt>
                        <dd class="mt-2 text-base font-semibold text-slate-800 dark:text-white">
                            {{ $record->lots_percentage ? number_format((float) $record->lots_percentage, 0) . '%' : '—' }}
                        </dd>
                    </div>
                </dl>

                @if ($totals['distribution']->isNotEmpty())
                    <div class="mt-6 space-y-3">
                        <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                            Cognitive Level Distribution
                        </h3>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($totals['distribution'] as $segment)
                                <div class="flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/90 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800/70 dark:bg-slate-950/70 dark:text-slate-100">
                                    <span class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ $segment['label'] }}
                                    </span>
                                    <span class="rounded-full bg-emerald-100/80 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">
                                        {{ $segment['percentage'] }}%
                                    </span>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">
                                        ({{ $segment['count'] }})
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white/95 shadow-xl shadow-slate-900/5 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
            <div class="px-4 py-6 sm:px-6 lg:px-8">
                @php
                    $hasRows = count($rows) > 0;
                @endphp

                <div class="space-y-4 lg:hidden">
                    @if (! $hasRows)
                        <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-6 text-center text-sm font-medium text-slate-500 dark:border-slate-800/70 dark:bg-slate-950/60 dark:text-slate-400">
                            No Table of Specification rows available for this record.
                        </div>
                    @else
                        @foreach ($rows as $row)
                            @php
                                $item = $row['model'];
                                $learningOutcome = $item->learningOutcome?->description ?? $item->learningOutcome?->outcome_statement ?? '—';
                                $topic = $item->subtopic?->topic?->name;
                                $subtopic = $item->subtopic?->name;
                                $coverage = collect([$topic, $subtopic])->filter()->unique()->implode(' • ');
                                $sampleRange = $row['sample_range'] ?? null;
                            @endphp
                            <article class="space-y-3 rounded-2xl border border-slate-200/70 bg-white/95 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800/70 dark:bg-slate-950/60">
                                <header class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $learningOutcome }}</p>
                                    @if ($item->learningOutcome?->outcome_code)
                                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                            {{ $item->learningOutcome->outcome_code }}
                                        </p>
                                    @endif
                                </header>

                                <dl class="grid gap-3 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <dt class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Topic Coverage</dt>
                                        <dd>{{ $coverage ?: '—' }}</dd>
                                    </div>

                                    <div class="space-y-1">
                                        <dt class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Cognitive Level</dt>
                                        <dd>
                                            <span class="inline-flex items-center rounded-full bg-emerald-100/70 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">
                                                {{ \Illuminate\Support\Str::headline($item->cognitive_level ?? 'Unspecified') }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div class="space-y-1">
                                        <dt class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Number of Items</dt>
                                        <dd class="text-base font-semibold text-slate-900 dark:text-white">
                                            {{ $item->num_items }}
                                        </dd>
                                    </div>

                                    <div class="space-y-1">
                                        <dt class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">% Distribution</dt>
                                        <dd>{{ number_format((float) $item->weight_percentage, 1) }}%</dd>
                                    </div>

                                    <div class="space-y-1 sm:col-span-2">
                                        <dt class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Sample Item No.</dt>
                                        <dd class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ $sampleRange ?? '—' }}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach

                        <div class="rounded-2xl border border-slate-200/70 bg-slate-900 text-white shadow-sm dark:border-slate-800/70 dark:bg-slate-950">
                            <dl class="grid divide-y divide-white/10 text-sm sm:grid-cols-2 sm:divide-y-0 sm:divide-x">
                                <div class="space-y-1 px-5 py-4">
                                    <dt class="text-xs uppercase tracking-[0.3em] text-slate-300">Total Items</dt>
                                    <dd class="text-lg font-semibold text-emerald-300">{{ $totals['items'] }}</dd>
                                </div>
                                <div class="space-y-1 px-5 py-4">
                                    <dt class="text-xs uppercase tracking-[0.3em] text-slate-300">Total Weight</dt>
                                    <dd class="text-lg font-semibold text-emerald-300">{{ number_format((float) $totals['weight'], 1) }}%</dd>
                                </div>
                            </dl>
                        </div>
                    @endif
                </div>

                <div class="hidden lg:block">
                    <div class="overflow-hidden rounded-2xl bg-white/95 shadow-sm dark:border-slate-800/70 dark:bg-slate-950/50">
                        <div class="overflow-x-auto">
                            <table class="min-w-[960px] divide-y divide-slate-200 text-left dark:divide-slate-800 bg-transparent dark:bg-transparent">
                                <thead class="bg-slate-900 text-xs font-semibold uppercase tracking-wider dark:text-white dark:bg-slate-950">
                                    <tr>
                                        <th scope="col" class="px-6 py-4">Learning Outcome (from OBTL)</th>
                                        <th scope="col" class="px-6 py-4">Topic Coverage</th>
                                        <th scope="col" class="px-6 py-4">Cognitive Level (Bloom's)</th>
                                        <th scope="col" class="px-6 py-4 text-right">Number of Items</th>
                                        <th scope="col" class="px-6 py-4 text-right">% Distribution</th>
                                        <th scope="col" class="px-6 py-4 text-right">Sample Item No.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm text-slate-700 dark:bg-slate-700 dark:divide-slate-800  dark:text-slate-500">
                                    @forelse ($rows as $row)
                                        @php
                                            $item = $row['model'];
                                            $learningOutcome = $item->learningOutcome?->description ?? $item->learningOutcome?->outcome_statement ?? '—';
                                            $topic = $item->subtopic?->topic?->name;
                                            $subtopic = $item->subtopic?->name;
                                            $coverage = collect([$topic, $subtopic])->filter()->unique()->implode(' • ');
                                            $sampleRange = $row['sample_range'] ?? null;
                                        @endphp
                                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-600">
                                            <!-- Learning Outcome -->
                                            <td class="px-6 py-4 align-top font-medium text-slate-800 dark:text-primary">
                                                <div>{{ $learningOutcome }}</div>
                                                @if ($item->learningOutcome?->outcome_code)
                                                    <div class="mt-1 text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                                        {{ $item->learningOutcome->outcome_code }}
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Coverage -->
                                            <td class="px-6 py-4 align-top text-slate-600 dark:text-slate-300">
                                                {{ $coverage ?: '—' }}
                                            </td>

                                            <!-- Cognitive Level -->
                                            <td class="px-6 py-4 align-top">
                                                <span class="inline-flex items-center rounded-full bg-emerald-100/70 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">
                                                    {{ \Illuminate\Support\Str::headline($item->cognitive_level ?? 'Unspecified') }}
                                                </span>
                                            </td>

                                            <!-- No. of Items -->
                                            <td class="px-6 py-4 align-top text-right font-semibold text-slate-800 dark:text-slate-100">
                                                {{ $item->num_items }}
                                            </td>

                                            <!-- Weight Percentage -->
                                            <td class="px-6 py-4 align-top text-right text-slate-600 dark:text-slate-300">
                                                {{ number_format((float) $item->weight_percentage, 1) }}%
                                            </td>

                                            <!-- Sample Range -->
                                            <td class="px-6 py-4 align-top text-right text-slate-500 dark:text-slate-400">
                                                {{ $sampleRange ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-sm font-medium text-slate-500 dark:text-slate-400">
                                                No Table of Specification rows available for this record.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>

                                <tfoot class="bg-slate-900/90 text-xs font-semibold uppercase tracking-wider dark:text-white dark:bg-slate-950">
                                    <tr>
                                        <td colspan="3" class="px-6 py-4">Total</td>
                                        <td class="px-6 py-4 text-right text-lg font-bold text-emerald-300">
                                            {{ $totals['items'] }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-lg font-bold text-emerald-300">
                                            {{ number_format((float) $totals['weight'], 1) }}%
                                        </td>
                                        <td class="px-6 py-4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>