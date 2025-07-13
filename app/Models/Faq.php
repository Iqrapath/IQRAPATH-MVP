<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'status',
        'order_index',
    ];

    /**
     * Get published FAQs ordered by their order_index.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPublished()
    {
        return static::where('status', 'published')
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Get draft FAQs ordered by their order_index.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDrafts()
    {
        return static::where('status', 'draft')
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Publish a FAQ.
     *
     * @return bool
     */
    public function publish(): bool
    {
        $this->status = 'published';
        return $this->save();
    }

    /**
     * Move a FAQ to draft.
     *
     * @return bool
     */
    public function unpublish(): bool
    {
        $this->status = 'draft';
        return $this->save();
    }

    /**
     * Update the order of FAQs.
     *
     * @param array $orderedIds
     * @return bool
     */
    public static function updateOrder(array $orderedIds): bool
    {
        $success = true;
        
        foreach ($orderedIds as $index => $id) {
            $success = $success && static::where('id', $id)->update(['order_index' => $index + 1]);
        }
        
        return $success;
    }
}
