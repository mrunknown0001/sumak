<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentProcessingController extends Controller
{
    /**
     * Step 1:
     * Assign midterm/final topic IDs and resume processing.
     */
    public function assignTopics(Request $request, Document $document)
    {
        // Since topics are predefined, assignment is not needed
        // But for backward compatibility, return success
        return response()->json([
            'message' => 'Document processing is already in progress.',
            'document_id' => $document->id
        ]);
    }


    /**
     * Step 2:
     * Document status checker (frontend polls this until processing is completed)
     */
    public function status(Document $document)
    {
        return response()->json([
            'document_id' => $document->id,
            'processing_status' => $document->processing_status,
            'processing_error'   => $document->processing_error,
            'processed_at'       => $document->processed_at,
        ]);
    }


    /**
     * Step 3:
     * Get topics to display in the assignment modal
     */
    public function topics(Document $document)
    {
        $topic = $document->topic;

        return response()->json([
            'document_id' => $document->id,
            'topics' => $topic ? [$topic->only(['id', 'name', 'metadata'])] : [],
        ]);
    }


    /**
     * Step 4:
     * Retrieve generated ToS (Midterm + Final)
     */
    public function tos(Document $document)
    {
        $tos = $document->topic->tableOfSpecification()
            ->with(['tosItems.topic', 'tosItems.learningOutcome'])
            ->first();

        return response()->json([
            'document_id' => $document->id,
            'tos' => $tos ? [$tos] : []
        ]);
    }


    /**
     * Step 5:
     * Retrieve all quiz items generated for the document
     */
    public function itemBank(Document $document)
    {
        $items = $document->topic->items()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'document_id' => $document->id,
            'items' => $items
        ]);
    }
}
