<?php

namespace App\Filament\Resources\TableOfSpecificationResource\Pages;

use App\Filament\Resources\TableOfSpecificationResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewTableOfSpecification extends ViewRecord
{
    protected static string $resource = TableOfSpecificationResource::class;

    protected static string $view = 'filament.resources.table-of-specification-resource.pages.view-table-of-specification';

    protected static ?string $title = 'Table of Specification (ToS)';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $record = $this->getRecord()->loadMissing([
            'document.course',
            'document.topics',
            'tosItems.topic',
            'tosItems.learningOutcome',
        ]);

        $items = $record->tosItems
            ->sortBy(function ($item) {
                $topicOrder = $item->topic?->order_index ?? PHP_INT_MAX;
                // $subtopicOrder = $item->subtopic?->order_index ?? PHP_INT_MAX;

                return [
                    $topicOrder,
                    // $subtopicOrder,
                    $item->id,
                ];
            })
            ->values();

        $runningCount = 1;

        $rows = $items->map(function ($item) use (&$runningCount) {
            $length = max(0, (int) $item->num_items);

            $start = $length > 0 ? $runningCount : null;
            $end = $length > 0 ? $runningCount + $length - 1 : null;

            if ($length > 0) {
                $runningCount += $length;
            }

            $sampleRange = match (true) {
                $length <= 0 => null,
                $length === 1 => (string) $start,
                default => "{$start}–{$end}",
            };

            return [
                'model' => $item,
                'start_item' => $start,
                'end_item' => $end,
                'sample_range' => $sampleRange,
            ];
        });

        $itemsTotal = (int) $items->sum('num_items');
        $safeTotal = max($itemsTotal, 1);

        $distribution = $items
            ->groupBy(fn ($item) => strtolower($item->cognitive_level ?? 'unspecified'))
            ->map(function ($group, string $key) use ($safeTotal) {
                $count = (int) $group->sum('num_items');
                $percentage = $safeTotal > 0 ? round(($count / $safeTotal) * 100) : 0;

                return [
                    'key' => $key,
                    'label' => Str::headline($key),
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            })
            ->sortByDesc('percentage')
            ->values();

        $course = $record->document?->course;

        return [
            'record' => $record,
            'course' => $course,
            'rows' => $rows,
            'totals' => [
                'items' => $itemsTotal,
                'weight' => round((float) $items->sum(fn ($item) => (float) $item->weight_percentage), 2),
                'distribution' => $distribution,
            ],
        ];
    }
}