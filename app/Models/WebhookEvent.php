<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'event_type',
        'resend_message_id',
        'recipient_email',
        'payload',
        'processed',
    ];

    protected $casts = [
        'payload'   => 'array',
        'processed' => 'boolean',
    ];
}
