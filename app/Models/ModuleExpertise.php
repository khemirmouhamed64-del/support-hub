<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleExpertise extends Model
{
    public $timestamps = false;

    protected $table = 'module_expertise';

    protected $fillable = [
        'team_member_id',
        'module_name',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // --- Relationships ---

    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }
}
