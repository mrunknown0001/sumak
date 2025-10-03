<?php

namespace App\Filament\Resources\ChatGptApiLogResource\Pages;

use App\Filament\Resources\ChatGptApiLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChatGptApiLog extends EditRecord
{
    protected static string $resource = ChatGptApiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
