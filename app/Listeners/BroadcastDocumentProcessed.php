<?php

namespace App\Listeners;

use App\Events\DocumentProcessed;
use Livewire\Livewire;

class BroadcastDocumentProcessed
{
    public function handle(DocumentProcessed $event)
    {
        // Broadcast to all Livewire components
        Livewire::dispatch('documentProcessed', documentId: $event->documentId);
    }
}
