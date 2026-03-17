<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'category',
        'subject',
        'html_content',
        'plain_content',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public static function categories(): array
    {
        return [
            'general'       => ['label' => 'General',       'icon' => '✉️',  'color' => '#7a7570'],
            'marketing'     => ['label' => 'Marketing',     'icon' => '📣',  'color' => '#d4a843'],
            'cold-email'    => ['label' => 'Cold Email',    'icon' => '🥶',  'color' => '#2563eb'],
            'notification'  => ['label' => 'Notification',  'icon' => '🔔',  'color' => '#1e7e52'],
            'newsletter'    => ['label' => 'Newsletter',    'icon' => '📰',  'color' => '#7c3aed'],
            'transactional' => ['label' => 'Transactional', 'icon' => '⚡',  'color' => '#c0392b'],
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category]['label'] ?? ucfirst($this->category);
    }

    public function getCategoryIconAttribute(): string
    {
        return self::categories()[$this->category]['icon'] ?? '✉️';
    }
}
