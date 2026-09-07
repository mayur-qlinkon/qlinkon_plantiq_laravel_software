<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Crm\CrmDashboardController;
use App\Http\Controllers\Admin\Crm\CrmImportExportController;
use App\Http\Controllers\Admin\Crm\CrmPerformanceController;
use App\Http\Controllers\Admin\Crm\CrmLeadController;
use App\Http\Controllers\Admin\Crm\CrmLeadSourceController;
use App\Http\Controllers\Admin\Crm\CrmPipelineController;
use App\Http\Controllers\Admin\Crm\CrmStageController;
use App\Http\Controllers\Admin\Crm\CrmTagController;

// ════════════════════════════════════════════════
// CRM & LEAD MANAGEMENT MODULE (module:crm)
// ════════════════════════════════════════════════
Route::prefix('crm')->name('crm.')->middleware(['module:crm'])->group(function () {
    Route::get('/dashboard', [CrmDashboardController::class, 'index'])
        ->middleware('permission:crm_dashboard.view')
        ->name('dashboard');

    // ── Pipelines ──
    Route::prefix('pipelines')->middleware('permission:crm_pipelines.view')->name('pipelines.')->controller(CrmPipelineController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{pipeline}', 'update')->name('update');
        Route::delete('/{pipeline}', 'destroy')->name('destroy');
        Route::post('/{pipeline}/default', 'setDefault')->name('default');
    });

    // ── Stages (nested under pipeline) ──
    Route::prefix('pipelines/{pipeline}/stages')->middleware('permission:crm_stages.view')->name('stages.')->controller(CrmStageController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{stage}', 'update')->name('update');
        Route::delete('/{stage}', 'destroy')->name('destroy');
        Route::post('/reorder', 'reorder')->name('reorder'); // SortableJS
    });

    // ── Lead Sources ──
    Route::prefix('sources')->middleware('permission:crm_sources.view')->name('sources.')->controller(CrmLeadSourceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{source}', 'update')->name('update');
        Route::delete('/{source}', 'destroy')->name('destroy');
    });

    // ── Tags ──
    Route::prefix('tags')->middleware('permission:crm_tags.view')->name('tags.')->controller(CrmTagController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{tag}', 'update')->name('update');
        Route::delete('/{tag}', 'destroy')->name('destroy');
    });

    // ── Performance Report ──
    // Manager-facing. Separately permissioned from crm_dashboard because it
    // reports ON employees rather than TO them.
    Route::get('/performance', [CrmPerformanceController::class, 'index'])
        ->middleware('permission:crm_reports.view')
        ->name('performance.index');

    // ── Import / Export ──
    // Declared before the /leads group on purpose: '/leads/import' must be
    // matched before '/leads/{lead}' can swallow it. Do not move these inside
    // the group below.
    //
    // These carried no permission at all — any user holding the CRM module
    // could pull every lead in the company into a spreadsheet, which is a
    // wider leak than any single screen.
    Route::get('/leads/import', [CrmImportExportController::class, 'importPage'])
        ->middleware(['permission:crm_leads.view', 'permission:crm_leads.import'])
        ->name('leads.import');
    Route::post('/leads/import', [CrmImportExportController::class, 'import'])
        ->middleware(['permission:crm_leads.view', 'permission:crm_leads.import'])
        ->name('leads.import.store');
    Route::get('/leads/import/template', [CrmImportExportController::class, 'template'])
        ->middleware(['permission:crm_leads.view', 'permission:crm_leads.import'])
        ->name('leads.import.template');
    Route::get('/leads/export', [CrmImportExportController::class, 'export'])
        ->middleware(['permission:crm_leads.view', 'permission:crm_leads.export'])
        ->name('leads.export');

    // ── Leads ──
    Route::prefix('leads')->middleware('permission:crm_leads.view')->name('leads.')->controller(CrmLeadController::class)->group(function () {
        // The group's crm_leads.view covers reading. Every route that WRITES
        // states its own permission on top of that.
        //
        // Hiding a button in the blade is not enforcement: the endpoint is
        // still reachable by posting to it directly. A rep with view-only
        // access could create, edit and delete leads until these were added.
        Route::get('/', 'index')->name('index');

        // ── Create ──
        // Declared before '/{lead}' — otherwise this GET matches the {lead}
        // wildcard first and 'create' gets passed to show() as a lead ID.
        Route::get('/create', 'create')
            ->middleware('permission:crm_leads.create')->name('create');
        Route::post('/', 'store')
            ->middleware('permission:crm_leads.create')->name('store');

        Route::get('/{lead}', 'show')->name('show');

        // ── Employee working their own leads: logging contact and setting the
        // next follow-up are part of view-level work, not lead editing. ──
        Route::post('/{lead}/activity', 'logActivity')->name('activity');
        Route::patch('/{lead}/followup', 'updateFollowup')->name('followup.update');

        // ── Update ──
        Route::get('/{lead}/edit', 'edit')
            ->middleware('permission:crm_leads.update')->name('edit');
        Route::put('/{lead}', 'update')
            ->middleware('permission:crm_leads.update')->name('update');
        Route::post('/{lead}/score', 'updateScore')
            ->middleware('permission:crm_leads.update')->name('score');
        // Bulk action covers stage, source, assign, tags, priority and
        // mark-lost — every branch mutates the lead.
        Route::post('/bulk-action', 'bulkAction')
            ->middleware('permission:crm_leads.update')->name('bulk_action');

        // ── Delete ──
        Route::delete('/{lead}', 'destroy')
            ->middleware('permission:crm_leads.delete')->name('destroy');
        Route::post('/bulk-delete', 'bulkDestroy')
            ->middleware('permission:crm_leads.delete')->name('bulk_destroy');

        // ── Stage / conversion ──
        Route::post('/{lead}/stage', 'moveStage')
            ->middleware('permission:crm_leads.change_stage')->name('stage');
        Route::post('/{lead}/convert', 'convert')
            ->middleware('permission:crm_leads.convert')->name('convert');

        // ── Tasks ──
        Route::post('/{lead}/tasks', 'storeTask')
            ->middleware('permission:crm_tasks.create')->name('tasks.store');
        Route::put('/{lead}/tasks/{task}', 'updateTask')
            ->middleware('permission:crm_tasks.create')->name('tasks.update');
        Route::post('/{lead}/tasks/{task}/complete', 'completeTask')
            ->middleware('permission:crm_tasks.complete')->name('tasks.complete');
        Route::delete('/{lead}/tasks/{task}', 'destroyTask')
            ->middleware('permission:crm_tasks.create')->name('tasks.destroy');
    });
});


