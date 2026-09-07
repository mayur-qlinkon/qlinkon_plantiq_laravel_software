<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CrmActivity extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id',        
        'crm_lead_id',
        'user_id',
        'type',
        'description',
        'meta',
        'is_auto',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_auto' => 'boolean',
    ];

    // Activity types with labels and icons for UI
    public const TYPES = [
        'note' => ['label' => 'Note',           'icon' => 'file-text'],
        'call' => ['label' => 'Call',            'icon' => 'phone'],
        'whatsapp' => ['label' => 'WhatsApp',        'icon' => 'message-circle'],
        'email' => ['label' => 'Email',           'icon' => 'mail'],
        'meeting' => ['label' => 'Meeting',         'icon' => 'users'],
        'stage_change' => ['label' => 'Stage Changed',   'icon' => 'arrow-right'],
        'lead_created' => ['label' => 'Lead Created',    'icon' => 'plus-circle'],
        'converted' => ['label' => 'Converted',       'icon' => 'check-circle'],
        'task_completed' => ['label' => 'Task Completed',  'icon' => 'check-square'],
        'score_changed' => ['label' => 'Score Updated',   'icon' => 'trending-up'],
        'assigned' => ['label' => 'Assigned',        'icon' => 'user-check'],
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    /**
     * An activity is visible exactly when its lead is. The activity feed
     * otherwise narrates other reps' work — who they called, what they
     * quoted — which is the same leak as showing the lead itself.
     */
    public function scopeVisibleToCurrentUser(Builder $q): Builder
    {
        if (CrmLead::currentUserSeesAll()) {
            return $q;
        }

        $userId = Auth::id();

        if (! $userId) {
            return $q->whereRaw('1 = 0');
        }

        return $q->whereHas('lead', fn (Builder $lead) => $lead->assigned((int) $userId));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeManual(Builder $q): Builder
    {
        return $q->where('is_auto', false);
    }

    public function scopeAuto(Builder $q): Builder
    {
        return $q->where('is_auto', true);
    }

    public function scopeByType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    public function getTypeInfoAttribute(): array
    {
        return self::TYPES[$this->type] ?? ['label' => ucfirst($this->type), 'icon' => 'activity'];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? ucfirst($this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPES[$this->type]['icon'] ?? 'activity';
    }

    // ════════════════════════════════════════════════════
    //  STATIC FACTORIES — clean API for logging
    // ════════════════════════════════════════════════════

    /**
     * Log a manual activity (note, call, whatsapp etc.)
     */
    public static function log(
        int $leadId,
        string $type,
        string $description,
        ?array $meta = null,
        ?int $userId = null,
        int $companyId = 0
    ): self {
        CrmLead::find($leadId);
        return static::create([
            'company_id' => $companyId ?: Auth::user()?->company_id,            
            'crm_lead_id' => $leadId,
            'user_id' => $userId ?? Auth::id(),
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
            'is_auto' => false,
        ]);
    }

    /**
     * Log an automatic system activity (Observer-driven)
     */
    /**
     * Log an automatic system activity.
     *
     * user_id used to be hardcoded null here, which meant stage changes,
     * conversions and task completions — the events that actually prove someone
     * moved a lead forward — carried no attribution at all. is_auto still marks
     * these as system-written rather than typed by hand; the two facts are
     * separate and both matter.
     *
     * Auth::id() is null under the scheduler and during imports, which is the
     * correct answer there: nobody did it.
     */
    public static function logAuto(
        int $leadId,
        string $type,
        string $description,
        ?array $meta = null,
        int $companyId = 0,
        ?int $userId = null
    ): self {
        CrmLead::find($leadId);
        return static::create([
            'company_id' => $companyId ?: Auth::user()?->company_id,
            'crm_lead_id' => $leadId,
            'user_id' => $userId ?? Auth::id(),
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
            'is_auto' => true,
        ]);
    }
}
