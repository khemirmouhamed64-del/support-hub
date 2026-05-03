<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source',
        'client_id',
        'external_ticket_id',
        'ticket_number',
        'reporter_name',
        'reporter_email',
        'ticket_type',
        'module',
        'sub_module',
        'issue_priority',
        'client_priority',
        'effective_priority',
        'incident_type',
        'incident_weight',
        'ai_status',
        'ai_suggestion',
        'ai_justification',
        'subject',
        'description',
        'steps_to_reproduce',
        'expected_behavior',
        'browser_url',
        'board_column',
        'assigned_to',
        'pr_link',
        'commit_info',
        'sp_notes',
        'deploy_notes',
        'resolution_message',
        'due_date',
        'resolved_at',
        'archived_at',
    ];

    protected $casts = [
        'resolved_at'        => 'datetime',
        'archived_at'        => 'datetime',
        'due_date'           => 'date',
        'effective_priority' => 'integer',
        'incident_weight'    => 'integer',
    ];

    const BOARD_COLUMNS = [
        'to_do', 'in_progress', 'blocked', 'code_review',
        'qa_testing', 'ready_for_release', 'done',
    ];

    // --- Relationships ---

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee()
    {
        return $this->belongsTo(TeamMember::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'ticket_label');
    }

    public function prLinks()
    {
        return $this->hasMany(TicketPrLink::class);
    }

    public function commits()
    {
        return $this->hasMany(TicketCommit::class);
    }

    // --- Scopes ---

    public function scopeInColumn($query, $column)
    {
        return $query->where('board_column', $column);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('effective_priority', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('board_column', '!=', 'done');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeInternal($query)
    {
        return $query->where('source', 'internal');
    }

    public function scopeFromClient($query)
    {
        return $query->where('source', 'client');
    }

    // --- Helpers ---

    public function calculateEffectivePriority()
    {
        $clientWeights = config('support.client_priority_weights');
        $clientWeight  = $clientWeights[$this->client_priority] ?? 3;
        $incidentWeight = $this->incident_weight ?: config('support.ai_fallback_weight', 3);

        $this->effective_priority = $clientWeight * $incidentWeight;

        return $this;
    }

    public function incidentLabel(): string
    {
        if (!$this->incident_type) return '—';
        return __('reports.incident_' . $this->incident_type);
    }

    public function incidentBadge(): string
    {
        $types = config('support.incident_types');
        return $types[$this->incident_type]['badge'] ?? 'default';
    }

    public static function generateTicketNumber()
    {
        $last = static::withTrashed()->orderBy('id', 'desc')->first();
        $next = $last ? ((int) substr($last->ticket_number, 2)) + 1 : 1;

        return 'T-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function suggestAssignee()
    {
        return TeamMember::active()
            ->whereHas('moduleExpertise', function ($q) {
                $q->where('module_name', $this->module);
            })
            ->orderByRaw('(SELECT is_primary FROM module_expertise WHERE team_member_id = team_members.id AND module_name = ? LIMIT 1) DESC', [$this->module])
            ->first();
    }

    public function boardColumnLabel()
    {
        $labels = [
            'to_do'             => 'To Do',
            'in_progress'       => 'In Progress',
            'blocked'           => 'Blocked',
            'code_review'       => 'Code Review',
            'qa_testing'        => 'QA/Testing',
            'ready_for_release' => 'Ready for Release',
            'done'              => 'Done',
        ];

        return $labels[$this->board_column] ?? $this->board_column;
    }

    public function isInternal()
    {
        return $this->source === 'internal';
    }

    public function isDueSoon()
    {
        if (!$this->due_date) {
            return false;
        }
        $diff = now()->startOfDay()->diffInDays($this->due_date, false);
        return $diff >= 0 && $diff <= 2;
    }

    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date->lt(now()->startOfDay());
    }

    public function isInAdvancedState()
    {
        return in_array($this->board_column, ['code_review', 'qa_testing', 'ready_for_release', 'done']);
    }
}
