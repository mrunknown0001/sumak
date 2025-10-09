<?php

namespace App\Filament\Resources\ItemBankResource\Pages;

use App\Filament\Resources\ItemBankResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItemBank extends EditRecord
{
    protected static string $resource = ItemBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
