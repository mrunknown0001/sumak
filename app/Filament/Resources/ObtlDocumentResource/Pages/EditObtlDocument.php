<?php

namespace App\Filament\Resources\ObtlDocumentResource\Pages;

use App\Filament\Resources\ObtlDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditObtlDocument extends EditRecord
{
    protected static string $resource = ObtlDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
