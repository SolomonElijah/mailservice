<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'contact_list_id',
        'email',
        'name',
        'company',
        'status',
    ];

    public function contactList(): BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('status', 'subscribed');
    }
}
