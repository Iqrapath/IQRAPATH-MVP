<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_key',
        'title',
        'content',
        'last_updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who last updated this content page.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Get content page by key.
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('page_key', $key)->first();
    }

    /**
     * Get content by key with fallback.
     */
    public static function getContent(string $key, string $fallback = ''): string
    {
        $page = static::getByKey($key);
        return $page?->content ?? $fallback;
    }
}