<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    protected $fillable = [
        'client_identifier',
        'business_name',
        'api_callback_url',
        'api_key',
        'priority_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- Relationships ---

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Helpers ---

    public static function generateApiKey()
    {
        return 'hub_' . Str::random(48);
    }
}
