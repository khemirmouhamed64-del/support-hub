<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPrLink extends Model
{
    public $timestamps = false;

    protected $fillable = ['ticket_id', 'url', 'title'];

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
