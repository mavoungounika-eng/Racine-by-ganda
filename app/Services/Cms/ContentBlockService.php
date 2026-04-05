<?php

namespace App\Services\Cms;

use App\Models\ContentBlock;
use Illuminate\Support\Facades\Cache;

class ContentBlockService
{
    /**
     * Get a content block by its key with 1h caching.
     */
    public function getBlock(string $key): ?ContentBlock
    {
        return Cache::tags(['cms', 'blocks'])->remember("cms_block:{$key}", 3600, function () use ($key) {
            return ContentBlock::active()->where('key', $key)->first();
        });
    }

    /**
     * Get multiple blocks.
     */
    public function getBlocks(array $keys): array
    {
        $blocks = [];
        foreach ($keys as $key) {
            $blocks[$key] = $this->getBlock($key);
        }
        return $blocks;
    }

    /**
     * Update a block and invalidate cache.
     */
    public function updateBlock(string $key, $content, int $userId): void
    {
        ContentBlock::where('key', $key)->update([
            'content' => is_array($content) ? json_encode($content) : $content,
            'updated_by' => $userId
        ]);
        
        Cache::forget("cms_block:{$key}");
        
        try {
            Cache::tags(['cms', 'blocks'])->flush();
        } catch (\BadMethodCallException $e) {
        }
    }
}
