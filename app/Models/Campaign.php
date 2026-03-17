<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'email_template_id',
        'contact_list_id',
        'name',
        'subject',
        'html_content',
        'plain_content',
        'from_name',
        'from_email',
        'type',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'opened_count',
        'clicked_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function contactList(): BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->sent_count === 0) return 0;
        return round(($this->opened_count / $this->sent_count) * 100, 1);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->sent_count === 0) return 0;
        return round(($this->clicked_count / $this->sent_count) * 100, 1);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'sent'      => 'green',
            'sending'   => 'blue',
            'scheduled' => 'gold',
            'draft'     => 'muted',
            'failed'    => 'red',
            'paused'    => 'orange',
            default     => 'muted',
        };
    }

    public static function types(): array
    {
        return [
            'marketing'     => ['label' => 'Marketing',     'icon' => '📣'],
            'cold-email'    => ['label' => 'Cold Email',    'icon' => '🥶'],
            'notification'  => ['label' => 'Notification',  'icon' => '🔔'],
            'newsletter'    => ['label' => 'Newsletter',    'icon' => '📰'],
            'transactional' => ['label' => 'Transactional', 'icon' => '⚡'],
        ];
    }
}
