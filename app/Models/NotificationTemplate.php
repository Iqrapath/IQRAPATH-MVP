<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'title',
        'body',
        'type',
        'placeholders',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'placeholders' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the triggers that use this template.
     */
    public function triggers(): HasMany
    {
        return $this->hasMany(NotificationTrigger::class, 'template_id');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Create a notification from this template with the given data.
     */
    public function createNotification(array $data = []): Notification
    {
        $title = $this->title;
        $body = $this->body;

        // Replace placeholders in title and body
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $placeholder = '[' . $key . ']';
                $title = str_replace($placeholder, $value, $title);
                $body = str_replace($placeholder, $value, $body);
            }
        }

        return Notification::create([
            'title' => $title,
            'body' => $body,
            'type' => $this->type,
            'status' => 'draft',
            'sender_type' => 'system',
            'metadata' => $data,
        ]);
    }
} 