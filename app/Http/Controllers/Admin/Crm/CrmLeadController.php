<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreActivityRequest;
use App\Http\Requests\Crm\StoreCrmLeadRequest;
use App\Http\Requests\Crm\StoreTaskRequest;
use App\Models\CrmActivity;
use App\Models\CrmLead;
use App\Models\CrmLeadSource;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\CrmTag;
use App\Models\CrmTask;
use App\Models\User;
use App\Services\CrmLeadService;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;
use Carbon\Carbon;


class CrmLeadController extends Controller
{
    public function __construct(protected CrmLeadService $service) {}

    // ════════════════════════════════════════════════════
    //  INDEX
    //  GET /admin/crm/leads
    // ════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $filters = $request->only([
            'q', 'pipeline_id', 'stage_id', 'priority',
            'source_id', 'tag_id', 'mine', 'assigned_to',
            'unassigned', 'overdue', 'converted',
            'from', 'to', 'sort', 'dir',
        ]);

        $leads = $this->service->getLeads($filters, perPage: 100);

        // getStats() takes no arguments — it reads the same visibility rule the
        // list does, so the cards and the rows can never disagree.
        $stats = $this->service->getStats();

        $canSeeAll = $this->service->canSeeAllLeads();
        $pipelines = CrmPipeline::where('company_id', $companyId)->active()->ordered()->get();
        $stages = CrmStage::where('company_id', $companyId)
            ->when($request->pipeline_id, fn ($q) => $q->where('crm_pipeline_id', $request->pipeline_id))
            ->active()->ordered()->get();
        $sources = CrmLeadSource::where('company_id', $companyId)->active()->ordered()->get();
        $tags = CrmTag::where('company_id', $companyId)->ordered()->get();
        $users = $this->getAssignableUsers($companyId);

        return view('admin.crm.leads.index', compact(
            'leads', 'stats', 'pipelines', 'stages',
            'sources', 'tags', 'users', 'filters', 'canSeeAll'
        ));
    }

    // ── Public wrapper for blade use ──
    public function formatTaskPublic(CrmTask $task): array
    {
        return $this->formatTask($task);
    }

    // ════════════════════════════════════════════════════
    //  CREATE
    //  GET /admin/crm/leads/create
    // ════════════════════════════════════════════════════

    public function create()
    {
        abort_unless(has_permission('crm_leads.create'), 403);
        $companyId = Auth::user()->company_id;

        $pipelines = CrmPipeline::where('company_id', $companyId)
            ->active()
            ->ordered()
            ->with(['stages' => fn ($q) => $q->active()->ordered()])
            ->get();
        $sources = CrmLeadSource::where('company_id', $companyId)->active()->ordered()->get();
        $tags = CrmTag::where('company_id', $companyId)->ordered()->get();
        $users = $this->getAssignableUsers($companyId);

        return view('admin.crm.leads.create', compact('pipelines', 'sources', 'tags', 'users'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/crm/leads
    // ════════════════════════════════════════════════════

    public function store(StoreCrmLeadRequest $request): JsonResponse
    {                
        $data = $request->validated();        
        try {
            $lead = $this->service->createLead($data);

            Log::info('[AdminCrmLead] Lead created', [
                'lead_id' => $lead->id,
                'by' => Auth::id(),
            ]);

            // A lead assigned to someone else on creation is no longer the
            // creator's to open. Sending them to the detail page would hand
            // them a 404 for a record they just created, so fall back to the
            // list, where the flash message still confirms the save.
            return response()->json([
                'success' => true,
                'message' => "Lead \"{$lead->name}\" created.",
                'redirect' => $lead->isVisibleToCurrentUser()
                    ? route('admin.crm.leads.show', $lead->id)
                    : route('admin.crm.leads.index'),
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create lead.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  SHOW
    //  GET /admin/crm/leads/{lead}
    // ════════════════════════════════════════════════════

    public function show(CrmLead $lead)
    {
        $this->authorizeLead($lead);

        $lead->load([
            'stage',
            'pipeline.stages' => fn ($q) => $q->active()->ordered(),
            'source',
            'tags',
            'assignees',
            'activities' => fn ($q) => $q->with('user:id,name')->latest()->limit(50),
            'tasks' => fn ($q) => $q->with('assignedUser:id,name')->orderBy('due_at'),
            'client',
            'order',
        ]);

        $companyId = Auth::user()->company_id;

        $activityTypes = collect(CrmActivity::TYPES)
            ->filter(fn ($v, $k) => ! in_array($k, ['stage_change', 'lead_created', 'converted', 'score_changed']))
            ->map(fn ($v, $k) => ['key' => $k, 'label' => $v['label'], 'icon' => $v['icon']])
            ->values();

        $taskTypes = collect(CrmTask::TYPES)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values();

        $stages = $lead->pipeline?->stages ?? collect();
        $tags = CrmTag::where('company_id', $companyId)->ordered()->get();
        $sources = CrmLeadSource::where('company_id', $companyId)->active()->ordered()->get();
        $users = $this->getAssignableUsers($companyId, $lead->assigned_user_id);

        return view('admin.crm.leads.show', compact(
            'lead', 'stages', 'activityTypes', 'taskTypes',
            'tags', 'sources', 'users'
        ));
    }

    // ════════════════════════════════════════════════════
    //  EDIT
    //  GET /admin/crm/leads/{lead}/edit
    // ════════════════════════════════════════════════════

    public function edit(CrmLead $lead)
    {
        abort_unless(has_permission('crm_leads.update'), 403);
        $this->authorizeLead($lead);
        $lead->load(['tags', 'assignees', 'stage.pipeline', 'source']);

        $companyId = Auth::user()->company_id;
        $currentStageId = $lead->crm_stage_id;

        $pipelines = CrmPipeline::where('company_id', $companyId)
            ->active()
            ->ordered()
            ->with(['stages' => function ($query) use ($currentStageId) {
                $query->where(function ($stageQuery) use ($currentStageId) {
                    $stageQuery->where('is_active', true);

                    if ($currentStageId) {
                        $stageQuery->orWhere('id', $currentStageId);
                    }
                })->ordered();
            }])
            ->get();
        $sources = CrmLeadSource::where('company_id', $companyId)->active()->ordered()->get();
        $tags = CrmTag::where('company_id', $companyId)->ordered()->get();
        $users = $this->getAssignableUsers($companyId, $lead->assigned_user_id);

        return view('admin.crm.leads.edit', compact('lead', 'pipelines', 'sources', 'tags', 'users'));
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/crm/leads/{lead}
    // ════════════════════════════════════════════════════

    public function update(StoreCrmLeadRequest $request, CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);        
        $data = $request->validated();                

        try {
            $updated = $this->service->updateLead($lead, $data);

            return response()->json([
                'success' => true,
                'message' => "Lead \"{$updated->name}\" updated.",
                'lead' => $updated->only([
                    'id', 'name', 'phone', 'email', 'priority', 'score',
                    'is_converted', 'next_followup_at', 'last_contacted_at',
                ]),
            ]);

        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update lead.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/crm/leads/{lead}
    // ════════════════════════════════════════════════════

    public function destroy(CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        try {
            $name = $lead->name;
            $this->service->deleteLead($lead);

            return response()->json([
                'success' => true,
                'message' => "Lead \"{$name}\" deleted.",
            ]);

        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] Destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete lead.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  BULK DESTROY
    //  POST /admin/crm/leads/bulk-delete
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  POST /admin/crm/leads/bulk-action
    // ════════════════════════════════════════════════════

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'string', 'in:stage,source,assign,tags,priority,mark_lost'],
            'value'  => ['nullable'],
        ]);

        $companyId = Auth::user()->company_id;
        $ids       = $request->ids;
        $action    = $request->action;
        $value     = $request->value;

        $canSeeAll = $this->service->canSeeAllLeads();

        // Handing a lead to someone else is a manager's call. Hiding the button
        // was not enough — the ids arrive in the request body, so anyone could
        // post this action for leads they cannot even see.
        if ($action === 'assign' && ! $canSeeAll) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reassign leads.',
            ], 403);
        }

        $leads = CrmLead::whereIn('id', $ids)
            ->where('company_id', $companyId)
            // Same visibility rule the list uses. Without it a rep could bulk
            // edit the whole company's pipeline by posting ids directly.
            ->when(! $canSeeAll, fn ($q) => $q->assigned(Auth::id()))
            ->get();

        if ($leads->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No valid leads found.'], 422);
        }

        // Some ids were filtered out — say so rather than reporting success on
        // a partial operation.
        if ($leads->count() !== count(array_unique($ids))) {
            return response()->json([
                'success' => false,
                'message' => 'Some of the selected leads are not yours to change.',
            ], 403);
        }

        $count = $leads->count();

        try {
            switch ($action) {

                case 'stage':
                    $stage = CrmStage::where('id', $value)
                        ->where('company_id', $companyId)->firstOrFail();
                    $leads->each(fn ($l) => $l->update([
                        'crm_stage_id'    => $stage->id,
                        'crm_pipeline_id' => $stage->crm_pipeline_id,
                    ]));
                    $msg = "{$count} lead(s) moved to \"{$stage->name}\".";
                    break;

                case 'source':
                    CrmLeadSource::where('id', $value)
                        ->where('company_id', $companyId)->firstOrFail();
                    $leads->each(fn ($l) => $l->update(['crm_lead_source_id' => $value]));
                    $msg = "Source updated for {$count} lead(s).";
                    break;

                case 'assign':
                    $assignUser = User::where('id', $value)
                        ->where('company_id', $companyId)->firstOrFail();
                    $leads->each(fn ($l) => $l->assignTo($assignUser->id));
                    $msg = "{$count} lead(s) assigned to {$assignUser->name}.";
                    break;

                case 'tags':
                    $tagIds = is_array($value) ? array_filter($value) : [];
                    if (! empty($tagIds)) {
                        $leads->each(fn ($l) => $l->tags()->syncWithoutDetaching($tagIds));
                    }
                    $msg = "Tags applied to {$count} lead(s).";
                    break;

                case 'priority':
                    if (! in_array($value, ['low', 'medium', 'high', 'hot'])) {
                        return response()->json(['success' => false, 'message' => 'Invalid priority.'], 422);
                    }
                    $leads->each(fn ($l) => $l->update(['priority' => $value]));
                    $msg = "Priority set to \"{$value}\" for {$count} lead(s).";
                    break;

                case 'mark_lost':
                    $markedCount = 0;
                    $skippedPipelineIds = [];

                    // Different pipelines have different Lost stages, so leads
                    // must go through the same path a single-lead drag-to-Lost
                    // uses — otherwise pending tasks, the followup date and the
                    // activity log silently fall out of sync.
                    $leads->groupBy('crm_pipeline_id')->each(function ($pipelineLeads, $pipelineId) use (&$markedCount, &$skippedPipelineIds, $companyId) {
                        $lostStage = CrmStage::where('crm_pipeline_id', $pipelineId)
                            ->where('company_id', $companyId)
                            ->where('is_lost', true)
                            ->first();

                        if (! $lostStage) {
                            $skippedPipelineIds[] = $pipelineId;
                            return;
                        }

                        foreach ($pipelineLeads as $lead) {
                            $this->service->moveToStage($lead, $lostStage->id);
                            $markedCount++;
                        }
                    });

                    $msg = "{$markedCount} lead(s) marked as lost.";
                    if (! empty($skippedPipelineIds)) {
                        $msg .= ' '.count($skippedPipelineIds).' pipeline(s) have no Lost stage configured — those leads were skipped.';
                    }
                    break;

                default:
                    return response()->json(['success' => false, 'message' => 'Unknown action.'], 422);
            }

            Log::info('[CrmBulkAction] Bulk action performed', [
                'action' => $action,
                'count'  => $count,
                'by'     => Auth::id(),
            ]);

            return response()->json(['success' => true, 'message' => $msg]);

        } catch (Throwable $e) {
            Log::error('[CrmBulkAction] Failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Action failed. Please try again.'], 500);
        }
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:crm_leads,id'],
        ]);

        $companyId = Auth::user()->company_id;

        try {
            // Company alone is not enough — a rep must not be able to delete
            // leads that were never theirs by posting the ids.
            $leadsToDelete = CrmLead::whereIn('id', $request->ids)
                ->where('company_id', $companyId)
                ->when(! $this->service->canSeeAllLeads(), fn ($q) => $q->assigned(Auth::id()))
                ->get();

            $deletedCount = 0;
            foreach ($leadsToDelete as $lead) {
                // Ensure the user has permission to delete this specific lead (if needed)
                $this->authorizeLead($lead);
                if ($this->service->deleteLead($lead)) {
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} lead(s).",
            ]);

        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] Bulk Destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete selected leads.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  MOVE STAGE — AJAX
    //  POST /admin/crm/leads/{lead}/stage
    // ════════════════════════════════════════════════════

    public function moveStage(Request $request, CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        $request->validate([
            'stage_id' => ['required', 'integer', 'exists:crm_stages,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $updated = $this->service->moveToStage(
                $lead,
                $request->stage_id,
                $request->note
            );

            return response()->json([
                'success' => true,
                'message' => "Moved to \"{$updated->stage->name}\".",
                'stage' => [
                    'id' => $updated->stage->id,
                    'name' => $updated->stage->name,
                    'color' => $updated->stage->color,
                    'is_won' => $updated->stage->is_won,
                    'is_lost' => $updated->stage->is_lost,
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] MoveStage failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to move stage.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  LOG ACTIVITY — AJAX
    //  POST /admin/crm/leads/{lead}/activity
    // ════════════════════════════════════════════════════

    public function logActivity(StoreActivityRequest $request, CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        try {
            $activity = $this->service->logActivity(
                $lead,
                $request->type,
                $request->description,
                $request->meta
            );

            $activity->load('user:id,name');

            return response()->json([
                'success' => true,
                'message' => 'Activity logged.',
                'activity' => [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'type_label' => $activity->type_label,
                    'type_icon' => $activity->type_icon,
                    'description' => $activity->description,
                    'is_auto' => $activity->is_auto,
                    'user_name' => $activity->user?->name ?? 'System',
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('d M Y, h:i A'),
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] LogActivity failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to log activity.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  STORE TASK — AJAX
    //  POST /admin/crm/leads/{lead}/tasks
    // ════════════════════════════════════════════════════

    public function storeTask(StoreTaskRequest $request, CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        try {
            $task = $this->service->createTask($lead, $request->validated());
            $task->load('assignedUser:id,name');

            return response()->json([
                'success' => true,
                'message' => "Task \"{$task->title}\" created.",
                'task' => $this->formatTask($task),
            ]);

        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] StoreTask failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create task.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE TASK — AJAX
    //  PUT /admin/crm/leads/{lead}/tasks/{task}
    // ════════════════════════════════════════════════════

    public function updateTask(StoreTaskRequest $request, CrmLead $lead, CrmTask $task): JsonResponse
    {
        $this->authorizeLead($lead);
        $this->authorizeTask($task, $lead);

        try {
            $oldAssignee = $task->assigned_to;

            $data = $request->validated();            
            $task->update($data);
            
            $task->load('assignedUser:id,name');

            // 🌟 Notify if the task was assigned to a NEW person (and not self)
            if ($task->assigned_to && $task->assigned_to !== $oldAssignee && $task->assigned_to !== Auth::id()) {
                if ($task->assignedUser) {
                    $task->assignedUser->notify(new \App\Notifications\Crm\CrmTaskAssignedNotification($task));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Task updated.',
                'task' => $this->formatTask($task->fresh()),
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update task.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  COMPLETE TASK — AJAX
    //  POST /admin/crm/leads/{lead}/tasks/{task}/complete
    // ════════════════════════════════════════════════════

    public function completeTask(Request $request, CrmLead $lead, CrmTask $task): JsonResponse
    {
        $this->authorizeLead($lead);
        $this->authorizeTask($task, $lead);

        $request->validate([
            'completion_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $completed = $this->service->completeTask($task, $request->completion_note ?? '');

            return response()->json([
                'success' => true,
                'message' => 'Task marked as completed.',
                'task' => $this->formatTask($completed),
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to complete task.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY TASK — AJAX
    //  DELETE /admin/crm/leads/{lead}/tasks/{task}
    // ════════════════════════════════════════════════════

    public function destroyTask(CrmLead $lead, CrmTask $task): JsonResponse
    {
        $this->authorizeLead($lead);
        $this->authorizeTask($task, $lead);

        try {
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted.',
                'task_id' => $task->id,
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete task.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CONVERT LEAD → CLIENT — AJAX
    //  POST /admin/crm/leads/{lead}/convert
    // ════════════════════════════════════════════════════

    public function convert(CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        try {
            $converted = $this->service->convertLead($lead);

            return response()->json([
                'success' => true,
                'message' => 'Lead converted to client successfully.',
                'client_id' => $converted->client_id,
                // 🌟 FIX: Pass the name as a query string parameter
                'client_url' => $converted->client_id
                    ? route('admin.clients.index', ['search' => $lead->name])
                    : null,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] Convert failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Conversion failed.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE SCORE — AJAX
    //  POST /admin/crm/leads/{lead}/score
    // ════════════════════════════════════════════════════

    public function updateScore(Request $request, CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        $request->validate([
            'points' => ['required', 'integer'],
            'operation' => ['required', Rule::in(['add', 'subtract'])],
        ]);

        try {
            $request->operation === 'add'
                ? $lead->addScore($request->points)
                : $lead->subtractScore($request->points);

            return response()->json([
                'success' => true,
                'score' => $lead->fresh()->score,
                'score_label' => $lead->fresh()->score_label,
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update score.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE FOLLOW-UP DATE
    //  PATCH /admin/crm/leads/{lead}/followup
    // ════════════════════════════════════════════════════
    public function updateFollowup(Request $request, CrmLead $lead): JsonResponse
    {
        $this->authorizeLead($lead);

        $request->validate([
            'next_followup_at' => ['nullable', 'date'],
            'followup_label'   => ['nullable', 'string', 'max:100'],
            'followup_note'    => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $clearing = ! $request->next_followup_at;

            $lead->update([
                'next_followup_at'    => $clearing ? null : Carbon::parse($request->next_followup_at),
                'followup_label'      => $clearing ? null : ($request->followup_label ?: null),
                'followup_note'       => $clearing ? null : ($request->followup_note  ?: null),
                'followup_reminded_at' => null, // reset so notification fires again on new date
            ]);

            $fresh = $lead->fresh();

            return response()->json([
                'success'    => true,
                'message'    => $clearing ? 'Follow-up cleared.' : 'Follow-up saved.',
                'display'    => $fresh->next_followup_at
                                    ? $fresh->next_followup_at->format('d M Y, h:i A')
                                    : '',
                'is_overdue' => $fresh->is_overdue,
            ]);

        } catch (Throwable $e) {
            Log::error('[AdminCrmLead] UpdateFollowup failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update follow-up.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Two separate boundaries, in order.
     *
     * company_id is the tenant boundary. Failing it is a 403 — the record
     * belongs to a different business entirely.
     *
     * Assignment is the ownership boundary inside one tenant. Failing it is a
     * 404, not a 403: a 403 confirms the id exists, which lets a rep walk the
     * id range and learn how many leads the team is working even without
     * opening any. A 404 says nothing.
     *
     * This guard protects every lead action — show, edit, update, destroy,
     * moveStage, convert, activities, tasks, score and follow-up. Company
     * scoping alone left all of them reachable by typing an id.
     */
    private function authorizeLead(CrmLead $lead): void
    {
        if ($lead->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }

        if (! $lead->isVisibleToCurrentUser()) {
            abort(404);
        }
    }

    private function authorizeTask(CrmTask $task, CrmLead $lead): void
    {
        if ($task->crm_lead_id !== $lead->id || $task->company_id !== Auth::user()->company_id) {
            abort(403, 'Task does not belong to this lead.');
        }
    }

    /**
     * Anyone internal can own a lead.
     *
     * There is deliberately no employee/staff split here. Whether a user has an
     * HRM profile says nothing about whether they work leads — access is granted
     * by the CRM module seat, so grouping by it only made the picker longer and
     * asked the user a question that has no bearing on the choice.
     */
    /**
     * Anyone internal and still active can own a lead.
     *
     * There is deliberately no employee/staff split. Whether a user has an HRM
     * profile says nothing about whether they work leads — access comes from
     * the CRM module seat.
     *
     * $keepUserId re-admits one person who would otherwise be filtered out.
     * Offboarding sets a user inactive rather than deleting them, so without
     * this the edit screen would show an empty assignee box for a lead that is
     * assigned, and saving would quietly drop the assignment.
     */
    private function getAssignableUsers(int $companyId, ?int $keepUserId = null): Collection
    {
        return User::query()
            ->internal()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($keepUserId) {
                $q->where('status', 'active');

                if ($keepUserId) {
                    $q->orWhere('id', $keepUserId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status'])
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'name'  => $u->name.($u->status !== 'active' ? ' (inactive)' : ''),
                'email' => $u->email,
            ]);
    }

    private function formatTask(CrmTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'type_label' => $task->type_label,
            'status' => $task->status,
            'status_label' => $task->status_label,
            'status_color' => $task->status_color,
            'priority' => $task->priority,
            'due_at' => $task->due_at?->format('d M Y, h:i A'),
            'due_at_iso' => $task->due_at?->toISOString(),
            'is_overdue' => $task->is_overdue,
            'completed_at' => $task->completed_at?->format('d M Y, h:i A'),
            'completion_note' => $task->completion_note,
            'assigned_to' => $task->assigned_to,
            'assignee_name' => $task->assignedUser?->name,
        ];
    }
}
