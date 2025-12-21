<div class="mx-auto max-w-6xl px-4 py-8 text-slate-900 dark:text-slate-100">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold tracking-tight">
            Table of Specification
            <span class="block text-sm font-normal text-slate-500 dark:text-slate-400">
                {{ $course->course_title }}
            </span>
        </h1>
    </div>

    @if($course->tosItems && $course->tosItems->count() > 0)
        @php
            $totalPercentage = $course->tosItems->sum('weight_percentage');
            $totalItems = $course->tosItems->sum('num_items');
        @endphp
        <!-- Table Wrapper -->
        <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <!-- Table Head -->
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                Topic / Objective
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                % Distribution
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                No. of Items
                            </th>
                            {{-- <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                Learning Outcomes
                            </th> --}}
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                Cognitive Level
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                Bloom Level
                            </th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @foreach($course->tosItems as $tosItem)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                <td class="px-4 py-3 text-sm">
                                    {{ $tosItem->topic->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    {{ $tosItem->weight_percentage }}%
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    {{ $tosItem->num_items }}
                                </td>
                                {{-- <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $tosItem->learningOutcome->description ?? 'N/A' }}
                                </td> --}}
                                <td class="px-4 py-3 text-sm">
                                    {{ $tosItem->cognitive_level ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $tosItem->bloom_category ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="font-bold bg-slate-100 dark:bg-slate-700">
                            <td class="px-4 py-3 text-sm">Total</td>
                            <td class="px-4 py-3 text-sm">{{ $totalPercentage }}%</td>
                            <td class="px-4 py-3 text-sm">{{ $totalItems }}</td>
                            <td class="px-4 py-3 text-sm">-</td>
                            <td class="px-4 py-3 text-sm">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                No Table of Specifications Available
            </h4>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                The Table of Specifications for this course has not been generated yet or is unavailable.
            </p>
        </div>
    @endif
</div>
