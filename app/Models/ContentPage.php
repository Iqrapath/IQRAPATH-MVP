<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ContentPage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_key',
        'title',
        'content',
        'last_updated_by',
    ];

    /**
     * Get the user who last updated this content page.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Get a content page by key.
     *
     * @param string $key
     * @return self|null
     */
    public static function getByKey(string $key): ?self
    {
        $cacheKey = "content_page_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key) {
            return static::where('page_key', $key)->first();
        });
    }

    /**
     * Get content by key.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getContent(string $key, string $default = ''): string
    {
        $page = static::getByKey($key);
        return $page ? $page->content : $default;
    }

    /**
     * Update a content page.
     *
     * @param string $key
     * @param array $data
     * @param int $userId
     * @return self|null
     */
    public static function updateContent(string $key, array $data, int $userId): ?self
    {
        $page = static::where('page_key', $key)->first();
        
        if (!$page) {
            return null;
        }
        
        $page->fill($data);
        $page->last_updated_by = $userId;
        $page->save();
        
        // Clear cache
        Cache::forget("content_page_{$key}");
        
        return $page;
    }

    /**
     * Clear the content page cache.
     *
     * @param string|null $key
     * @return void
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("content_page_{$key}");
        } else {
            // Clear all content page caches
            foreach (static::pluck('page_key') as $pageKey) {
                Cache::forget("content_page_{$pageKey}");
            }
        }
    }
}
