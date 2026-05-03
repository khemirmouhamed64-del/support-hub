<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMention extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'mentioned_member_id',
        'is_read',
        'notified_by_email',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'notified_by_email' => 'boolean',
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function comment()
    {
        return $this->belongsTo(TicketComment::class, 'comment_id');
    }

    public function mentionedMember()
    {
        return $this->belongsTo(TeamMember::class, 'mentioned_member_id');
    }
}
