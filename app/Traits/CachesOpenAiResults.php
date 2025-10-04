<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CachesOpenAiResults
{
    /**
     * Get cached result or execute and cache
     */
    protected function cacheOpenAiResult(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheKey = $this->getOpenAiCacheKey($key);
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear cached OpenAI results
     */
    public function clearOpenAiCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget($this->getOpenAiCacheKey($key));
        } else {
            // Clear all OpenAI cache for this model
            $prefix = $this->getOpenAiCachePrefix();
            Cache::flush(); // Or implement more specific clearing
        }
    }

    /**
     * Get cache key for OpenAI results
     */
    protected function getOpenAiCacheKey(string $key): string
    {
        return $this->getOpenAiCachePrefix() . ':' . $key;
    }

    /**
     * Get cache prefix for this model
     */
    protected function getOpenAiCachePrefix(): string
    {
        return 'openai:' . class_basename($this) . ':' . $this->id;
    }
}