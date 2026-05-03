<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TeamMember extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'expertise',
        'avatar_url',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'expertise' => 'array',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    // --- Relationships ---

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'author_id');
    }

    public function moduleExpertise()
    {
        return $this->hasMany(ModuleExpertise::class);
    }

    public function notifications()
    {
        return $this->hasMany(HubNotification::class, 'recipient_id');
    }

    public function mentions()
    {
        return $this->hasMany(TicketMention::class, 'mentioned_member_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Helpers ---

    public function unreadNotificationsCount()
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function hasExpertiseIn($module)
    {
        return $this->moduleExpertise()->where('module_name', $module)->exists();
    }
}
