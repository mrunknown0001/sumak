<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Listeners\BroadcastDocumentProcessed;

#[\Illuminate\Foundation\Attributes\Dispatchable]
#[\Illuminate\Foundation\Attributes\Broadcast]
#[\Illuminate\Foundation\Attributes\Listener(BroadcastDocumentProcessed::class)]
class DocumentProcessed
{
    use Dispatchable, SerializesModels;

    public int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }
}
