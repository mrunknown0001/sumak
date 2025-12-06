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
        $request->validate([
            'midterm_topics' => 'required|array',
            'final_topics'   => 'required|array',
            'midterm_topics.*' => 'integer|exists:topics,id',
            'final_topics.*'   => 'integer|exists:topics,id',
        ]);

        // Dispatch the continuation job
        ProcessDocumentJob::dispatch($document->id, [
            'midterm_topics' => $request->midterm_topics,
            'final_topics'   => $request->final_topics,
        ]);

        $document->update([
            'processing_status' => 'processing_topics_assigned',
        ]);

        return response()->json([
            'message' => 'Topic assignment received. Generating Midterm/Final ToS and Quiz Items...',
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
        $topics = $document->topics()
            ->orderBy('order_index')
            ->get(['id', 'name', 'order_index', 'metadata']);

        return response()->json([
            'document_id' => $document->id,
            'topics' => $topics,
        ]);
    }


    /**
     * Step 4:
     * Retrieve generated ToS (Midterm + Final)
     */
    public function tos(Document $document)
    {
        $tos = $document->tableOfSpecifications()
            ->with(['tosItems.topic', 'tosItems.learningOutcome'])
            ->orderBy('term')
            ->get();

        return response()->json([
            'document_id' => $document->id,
            'tos' => $tos
        ]);
    }


    /**
     * Step 5:
     * Retrieve all quiz items generated for the document
     */
    public function itemBank(Document $document)
    {
        $items = $document->quizQuestions()
            ->orderBy('topic_id')
            ->get();

        return response()->json([
            'document_id' => $document->id,
            'items' => $items
        ]);
    }
}
