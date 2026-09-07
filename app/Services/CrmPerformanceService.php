<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmLead;
use App\Models\CrmLeadAssignment;
use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-employee CRM performance over a date window.
 *
 * Two kinds of number live here and they are deliberately kept apart:
 *
 *   EFFORT   — calls, WhatsApp, notes, meetings. These are self-reported: the
 *              employee typed them in. Forty fake call entries take thirty
 *              seconds, so effort on its own proves nothing.
 *   OUTCOME  — stage moves, conversions, task punctuality. These are written by
 *              the system as a side effect of real work and are far harder to
 *              inflate.
 *
 * The report shows both because the interesting signal is the gap between them:
 * high effort with zero movement is the pattern worth a conversation.
 *
 * The window is activity-based, not cohort-based — "what work happened this
 * week", regardless of when the lead arrived.
 */
class CrmPerformanceService
{
    /** Manually logged contact attempts. Auto rows are excluded from effort counts. */
    private const EFFORT_TYPES = ['call', 'whatsapp', 'email', 'meeting', 'note'];

    /** A lead nobody has touched in this many days is treated as going cold. */
    public const STALE_DAYS = 7;

    /**
     * One row per user, already merged across every metric.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function teamSummary(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $activity    = $this->activityMetrics($companyId, $from, $to);
        $assignments = $this->assignmentMetrics($companyId, $from, $to);
        $tasks       = $this->taskMetrics($companyId, $from, $to);
        $openTasks   = $this->openTaskMetrics($companyId);
        $stale       = $this->staleLeadMetrics($companyId);
        $firstTouch  = $this->firstTouchMetrics($companyId, $from, $to);

        // Anyone who appears in any metric belongs in the report. Building the
        // user list from the metrics rather than from "all employees" keeps
        // people who never touched the CRM out of it.
        $userIds = collect()
            ->merge($activity->keys())
            ->merge($assignments->keys())
            ->merge($tasks->keys())
            ->merge($openTasks->keys())
            ->merge($stale->keys())
            ->unique()
            ->values();

        $users = User::whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

        return $userIds
            ->map(function (int $userId) use ($users, $activity, $assignments, $tasks, $openTasks, $stale, $firstTouch) {
                $a = $activity->get($userId);
                $s = $assignments->get($userId);
                $t = $tasks->get($userId);

                $tasksCompleted = (int) ($t->completed ?? 0);
                $onTime         = (int) ($t->on_time ?? 0);

                return [
                    'user_id'   => $userId,
                    'user_name' => $users[$userId]->name ?? 'Deleted user',

                    // ── Effort (self-reported) ──
                    'calls'     => (int) ($a->calls ?? 0),
                    'whatsapp'  => (int) ($a->whatsapp ?? 0),
                    'emails'    => (int) ($a->emails ?? 0),
                    'meetings'  => (int) ($a->meetings ?? 0),
                    'notes'     => (int) ($a->notes ?? 0),
                    'effort'    => (int) ($a->effort_total ?? 0),

                    // ── Outcome (system-derived) ──
                    'stage_moves'  => (int) ($a->stage_moves ?? 0),
                    'conversions'  => (int) ($a->conversions ?? 0),

                    // ── Coverage ──
                    'leads_touched'  => (int) ($a->leads_touched ?? 0),
                    'active_days'    => (int) ($a->active_days ?? 0),
                    'leads_held'     => (int) ($s->leads_held ?? 0),
                    'leads_received' => (int) ($s->leads_received ?? 0),
                    'leads_handed'   => (int) ($s->leads_handed ?? 0),

                    // ── Discipline ──
                    'tasks_completed' => $tasksCompleted,
                    'tasks_on_time'   => $onTime,
                    'on_time_rate'    => $tasksCompleted > 0
                        ? round(($onTime / $tasksCompleted) * 100)
                        : null,
                    'tasks_overdue'   => (int) ($openTasks->get($userId)->overdue ?? 0),

                    // ── Neglect ──
                    'stale_leads' => (int) ($stale->get($userId)->stale ?? 0),

                    // Null means the user was assigned nothing in the window, which
                    // is different from "responded instantly". The view must not
                    // render null as zero.
                    'avg_first_touch_hours' => isset($firstTouch[$userId])
                        ? round($firstTouch[$userId]->avg_minutes / 60, 1)
                        : null,
                ];
            })
            ->sortByDesc('effort')
            ->values();
    }

    // ─────────────────────────────────────────────────────────
    //  METRIC QUERIES — one grouped aggregate each
    // ─────────────────────────────────────────────────────────

    /**
     * Effort, outcome and coverage in a single pass over crm_activities.
     *
     * is_auto separates the two halves: manual rows are what the employee
     * claims they did, auto rows are what the system observed them doing.
     */
    private function activityMetrics(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return CrmActivity::query()
            ->where('company_id', $companyId)
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->toBase()
            ->selectRaw("
                user_id,
                SUM(CASE WHEN is_auto = 0 AND type = 'call'     THEN 1 ELSE 0 END) AS calls,
                SUM(CASE WHEN is_auto = 0 AND type = 'whatsapp' THEN 1 ELSE 0 END) AS whatsapp,
                SUM(CASE WHEN is_auto = 0 AND type = 'email'    THEN 1 ELSE 0 END) AS emails,
                SUM(CASE WHEN is_auto = 0 AND type = 'meeting'  THEN 1 ELSE 0 END) AS meetings,
                SUM(CASE WHEN is_auto = 0 AND type = 'note'     THEN 1 ELSE 0 END) AS notes,
                SUM(CASE WHEN is_auto = 0 AND type IN ('".implode("','", self::EFFORT_TYPES)."') THEN 1 ELSE 0 END) AS effort_total,
                SUM(CASE WHEN type = 'stage_change' THEN 1 ELSE 0 END) AS stage_moves,
                SUM(CASE WHEN type = 'converted'    THEN 1 ELSE 0 END) AS conversions,
                COUNT(DISTINCT crm_lead_id) AS leads_touched,
                COUNT(DISTINCT DATE(created_at)) AS active_days
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Workload from the assignment history.
     *
     * leads_held uses an overlap test rather than assigned_at BETWEEN: a lead
     * handed over last month and worked all of this month is this month's
     * workload, and a start-date filter would drop it entirely.
     */
    private function assignmentMetrics(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return CrmLeadAssignment::query()
            ->where('company_id', $companyId)
            ->toBase()
            ->selectRaw('
                user_id,
                COUNT(DISTINCT CASE
                    WHEN assigned_at <= ? AND (unassigned_at IS NULL OR unassigned_at >= ?)
                    THEN crm_lead_id END) AS leads_held,
                COUNT(DISTINCT CASE
                    WHEN assigned_at BETWEEN ? AND ?
                    THEN crm_lead_id END) AS leads_received,
                COUNT(DISTINCT CASE
                    WHEN unassigned_at BETWEEN ? AND ?
                    THEN crm_lead_id END) AS leads_handed
            ', [$to, $from, $from, $to, $from, $to])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Task punctuality, keyed on completed_at so it follows the activity window.
     */
    private function taskMetrics(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return CrmTask::query()
            ->where('company_id', $companyId)
            ->whereNotNull('assigned_to')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->toBase()
            ->selectRaw('
                assigned_to AS user_id,
                COUNT(*) AS completed,
                SUM(CASE WHEN completed_at <= due_at THEN 1 ELSE 0 END) AS on_time
            ')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Overdue tasks sitting open right now.
     *
     * Point-in-time on purpose — the window does not apply. A manager wants to
     * know what is rotting today, not what was overdue three weeks ago.
     */
    private function openTaskMetrics(int $companyId): Collection
    {
        return CrmTask::query()
            ->where('company_id', $companyId)
            ->whereNotNull('assigned_to')
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_at', '<', now())
            ->toBase()
            ->selectRaw('assigned_to AS user_id, COUNT(*) AS overdue')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Leads currently held that nobody has touched in STALE_DAYS.
     *
     * Also point-in-time. This is usually the single most actionable column on
     * the whole report: it names leads that are quietly dying.
     */
    private function staleLeadMetrics(int $companyId): Collection
    {
        $cutoff = now()->subDays(self::STALE_DAYS);

        $lastActivity = DB::table('crm_activities')
            ->select('crm_lead_id', DB::raw('MAX(created_at) AS last_at'))
            ->where('company_id', $companyId)
            ->groupBy('crm_lead_id');

        return DB::table('crm_lead_assignments as a')
            ->join('crm_leads as l', 'l.id', '=', 'a.crm_lead_id')
            ->leftJoinSub($lastActivity, 'act', 'act.crm_lead_id', '=', 'a.crm_lead_id')
            ->where('a.company_id', $companyId)
            ->whereNull('a.unassigned_at')
            ->whereNull('l.deleted_at')
            ->where('l.is_converted', false)
            // No activity at all also counts as stale — an untouched lead is the
            // worst case, not an exempt one.
            ->where(fn ($q) => $q->whereNull('act.last_at')->orWhere('act.last_at', '<', $cutoff))
            ->selectRaw('a.user_id, COUNT(*) AS stale')
            ->groupBy('a.user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Average hours between receiving a lead and first manually touching it.
     *
     * Correlated subquery — fine at CRM volumes, but it is the most expensive
     * query here. If the report ever slows down, this is the one to cache.
     */
    private function firstTouchMetrics(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('crm_lead_assignments as a')
            ->where('a.company_id', $companyId)
            ->whereBetween('a.assigned_at', [$from, $to])
            ->selectRaw('
                a.user_id,
                AVG(TIMESTAMPDIFF(MINUTE, a.assigned_at, (
                    SELECT MIN(act.created_at)
                    FROM crm_activities act
                    WHERE act.crm_lead_id = a.crm_lead_id
                      AND act.user_id = a.user_id
                      AND act.is_auto = 0
                      AND act.created_at >= a.assigned_at
                ))) AS avg_minutes
            ')
            ->groupBy('a.user_id')
            ->having('avg_minutes', '>', 0)
            ->get()
            ->keyBy('user_id');
    }
}