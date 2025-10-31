<?php

namespace App\Filament\Resources\TableOfSpecificationResource\Pages;

use App\Filament\Resources\TableOfSpecificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTableOfSpecifications extends ListRecords
{
    protected static string $resource = TableOfSpecificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
