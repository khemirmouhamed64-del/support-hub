<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCommit extends Model
{
    public $timestamps = false;

    protected $fillable = ['ticket_id', 'hash', 'message', 'url'];

    protected $dates = ['created_at'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
