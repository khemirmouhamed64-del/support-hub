<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'recipient_id',
        'ticket_id',
        'type',
        'title',
        'message',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function recipient()
    {
        return $this->belongsTo(TeamMember::class, 'recipient_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // --- Scopes ---

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // --- Helpers ---

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }
}
