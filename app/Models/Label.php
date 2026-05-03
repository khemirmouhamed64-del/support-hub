<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $fillable = ['name', 'color'];

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_label');
    }
}
