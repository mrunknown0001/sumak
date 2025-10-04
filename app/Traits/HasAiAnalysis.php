<?php

namespace App\Traits;

trait HasAiAnalysis
{
    /**
     * Check if content has been analyzed
     */
    public function hasBeenAnalyzed(): bool
    {
        return $this->aiAnalysis()->exists();
    }

    /**
     * Get analysis results
     */
    public function getAnalysisResults(): ?array
    {
        if (!$this->hasBeenAnalyzed()) {
            return null;
        }

        $analysis = $this->aiAnalysis;
        
        return [
            'key_concepts' => json_decode($analysis->key_concepts, true),
            'extracted_content' => json_decode($analysis->extracted_content, true),
            'difficulty_assessment' => $analysis->difficulty_assessment,
        ];
    }

    /**
     * Get key concepts
     */
    public function getKeyConcepts(): array
    {
        $analysis = $this->aiAnalysis;
        
        return $analysis 
            ? json_decode($analysis->key_concepts, true) 
            : [];
    }

    /**
     * Check if material is being processed
     */
    public function isProcessing(): bool
    {
        return $this->processing_status === 'processing';
    }

    /**
     * Check if processing failed
     */
    public function hasFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    /**
     * Check if processing is complete
     */
    public function isProcessed(): bool
    {
        return $this->processing_status === 'analyzed';
    }
}