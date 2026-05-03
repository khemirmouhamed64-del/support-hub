<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'ticket_status_history';

    protected $fillable = [
        'ticket_id',
        'old_column',
        'new_column',
        'changed_by',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changedByMember()
    {
        return $this->belongsTo(TeamMember::class, 'changed_by');
    }
}
