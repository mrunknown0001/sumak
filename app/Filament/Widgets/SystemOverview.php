<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Course;
use App\Models\Document;

class SystemOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Courses', Course::whereNull('deleted_at')->count())
                ->description('Active courses')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success')
                ->url(route('filament.admin.resources.courses.index')),

            Stat::make('Students', User::where('role', 'student')->count())
                ->description('Active courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success')
                ->url(route('filament.admin.resources.students.index')),

            Stat::make('Learning Materials', Document::where('processing_status', 'completed')->count())
                ->description('Active courses')
                ->descriptionIcon('heroicon-m-document')
                ->color('success')
                ->url(route('filament.admin.resources.documents.index')),
        ];
    }
}
