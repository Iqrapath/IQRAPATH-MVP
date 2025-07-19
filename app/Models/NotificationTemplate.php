<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'level',
        'action_text',
        'action_url',
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
     * Scope a query to only include active templates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the notification triggers that use this template.
     */
    public function triggers()
    {
        return $this->hasMany(NotificationTrigger::class, 'template_name', 'name');
    }

    /**
     * Replace placeholders in the template with actual values.
     *
     * @param  array  $data
     * @return array
     */
    public function replacePlaceholders(array $data): array
    {
        $title = $this->title;
        $body = $this->body;
        $actionText = $this->action_text;
        $actionUrl = $this->action_url;

        // Replace placeholders in title and body
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $title = str_replace($placeholder, $value, $title);
            $body = str_replace($placeholder, $value, $body);
            
            if ($actionText) {
                $actionText = str_replace($placeholder, $value, $actionText);
            }
            
            if ($actionUrl) {
                $actionUrl = str_replace($placeholder, $value, $actionUrl);
            }
        }

        return [
            'title' => $title,
            'body' => $body,
            'action_text' => $actionText,
            'action_url' => $actionUrl,
            'level' => $this->level,
        ];
    }
}
