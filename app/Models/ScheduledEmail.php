<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledEmail extends Model
{
    protected $fillable = [
        'user_id',
        'to_email',
        'to_name',
        'subject',
        'html_body',
        'plain_body',
        'status',
        'send_at',
        'sent_at',
        'error_message',
        'job_id',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')->where('send_at', '<=', now());
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'sent'       => 'green',
            'processing' => 'blue',
            'pending'    => 'gold',
            'failed'     => 'red',
            'cancelled'  => 'muted',
            default      => 'muted',
        };
    }
}
