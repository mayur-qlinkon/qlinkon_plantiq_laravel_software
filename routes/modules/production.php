<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Production\LayoutWorkspaceController;
use App\Http\Controllers\Admin\Production\ProductionSiteController;
use App\Http\Controllers\Admin\Production\ProductionZoneController;
use App\Http\Controllers\Admin\Production\GrowingSpaceTypeController;
use App\Http\Controllers\Admin\Production\GrowingSpaceController;
use App\Http\Controllers\Admin\Production\GrowingSpaceBulkGeneratorController;
use App\Http\Controllers\Admin\Production\ProductionPlanController;
use App\Http\Controllers\Admin\Production\PlantBatchController;
use App\Http\Controllers\Admin\Production\BatchPlacementController;
use App\Http\Controllers\Admin\Production\OccupancyController;
use App\Http\Controllers\Admin\Production\ZoneAssignmentController;
use App\Http\Controllers\Admin\Production\ActivityTemplateController;
use App\Http\Controllers\Admin\Production\MyTasksController;
use App\Http\Controllers\Admin\Production\HarvestReceivingController;
use App\Http\Controllers\Admin\Production\ProductionDashboardController;
use App\Http\Controllers\Admin\Production\ProductionQuickActionController;
use App\Http\Controllers\Admin\Production\ProductionMapController;

// ════════════════════════════════════════════════
// PRODUCTION & MANUFACTURING MODULE (module:production)
//
// Holding the module seat is NOT authorisation. Every route below carries the
// same permission its sidebar entry checks in layouts/admin.blade.php, so a
// visible link and a reachable URL always agree. Before this, 65 of 88 routes
// had no permission at all and any user with the Production seat could delete
// the site layout or fabricate plants into sellable inventory.
// ════════════════════════════════════════════════
Route::middleware('module:production')->prefix('production')->name('production.')->group(function () {

    // ── Dashboard (owner snapshot) ──
    // Gated with the same any-of list the sidebar uses to decide whether to
    // show the Production section at all.
    Route::middleware('permission:production_layout.view,production_plans.view,production_plant_batches.view,production_activity_templates.view,production_zone_assignments.view,production_harvest_lots.view')->group(function () {
        Route::get('dashboard', [ProductionDashboardController::class, 'index'])->name('dashboard');
    });

    // ── Dashboard Quick Actions ──
    // Company-wide twins of the My Tasks worker endpoints, deliberately WITHOUT
    // the zone-assignment restriction. That makes them the bypass for every
    // zone check My Tasks applies, so each one is gated on the permission for
    // the record it actually writes.
    Route::prefix('quick-actions')->name('quick-actions.')->group(function () {
        Route::get('batches', [ProductionQuickActionController::class, 'batches'])
            ->name('batches')->middleware('permission:production_plant_batches.view');
        Route::get('spaces', [ProductionQuickActionController::class, 'spaces'])
            ->name('spaces')->middleware('permission:production_plant_batches.view');

        Route::post('harvest', [ProductionQuickActionController::class, 'harvest'])
            ->name('harvest')->middleware('permission:production_harvest_lots.create');
        Route::post('loss', [ProductionQuickActionController::class, 'loss'])
            ->name('loss')->middleware('permission:production_plant_batches.update');
        Route::post('adjustment', [ProductionQuickActionController::class, 'adjustment'])
            ->name('adjustment')->middleware('permission:production_plant_batches.update');
    });

    // ── My Tasks (employee self-service) ──
    // employee.profile, not a production permission: these screens belong to
    // the person, and every method here reads Auth::user()->employee->id with
    // no null guard. The middleware supplies both the 403 for a user with no
    // employee record and the re-check that a terminated worker is stopped
    // mid-session.
    Route::middleware('employee.profile')->group(function () {
        Route::get('my-tasks/not-checked-in', [MyTasksController::class, 'notCheckedIn'])->name('my-tasks.not-checked-in');

        // Route::middleware('checked_in')->group(function () {
        Route::get('my-tasks', [MyTasksController::class, 'index'])->name('my-tasks.index');
        Route::post('my-tasks/{dailyTask}/toggle', [MyTasksController::class, 'toggle'])->name('my-tasks.toggle');
        Route::post('my-tasks/losses', [MyTasksController::class, 'recordLoss'])->name('my-tasks.losses.store');
        Route::post('my-tasks/harvests', [MyTasksController::class, 'recordHarvest'])->name('my-tasks.harvests.store');
        Route::get('my-tasks/batches', [MyTasksController::class, 'batchesForDropdown'])->name('my-tasks.batches');
        Route::get('my-tasks/spaces', [MyTasksController::class, 'spacesForDropdown'])->name('my-tasks.spaces');
        Route::post('my-tasks/move', [MyTasksController::class, 'moveBatch'])->name('my-tasks.move');
        // });
    });

    // ── Harvest Receiving (admin/warehouse — approve pending lots into inventory) ──
    Route::prefix('harvest-lots')->name('harvest-lots.')->group(function () {
        Route::middleware('permission:production_harvest_lots.view')->group(function () {
            Route::get('/', [HarvestReceivingController::class, 'index'])->name('index');
            Route::get('/data', [HarvestReceivingController::class, 'data'])->name('data');
        });

        // Manual lot entry (create/store/edit/update) is gone. Harvest lots are
        // produced by workers through My Tasks; the manual path was half-built —
        // its Blade views never existed, so create/edit were permanent 500s, and
        // nothing in the UI ever posted to store/update.
        Route::middleware('permission:production_harvest_lots.update')->group(function () {
            Route::patch('{harvest}/quantity', [HarvestReceivingController::class, 'correctQuantity'])->name('correct-quantity');
        });

        Route::post('{harvest}/receive', [HarvestReceivingController::class, 'receive'])
            ->name('receive')->middleware('permission:production_harvest_lots.receive');

        Route::post('{harvest}/cancel', [HarvestReceivingController::class, 'cancel'])
            ->name('cancel')->middleware('permission:production_harvest_lots.cancel');

        Route::get('{harvest}/pdf', [HarvestReceivingController::class, 'downloadPdf'])
            ->name('pdf')->middleware('permission:production_harvest_lots.view');

        // Registered last: a literal segment above would otherwise be captured
        // by {harvest}.
        Route::get('{harvest}', [HarvestReceivingController::class, 'show'])
            ->name('show')->middleware('permission:production_harvest_lots.view');
    });

    // ── Layout Workspace (single-page, AJAX tree + inspector) ──
    Route::middleware('permission:production_layout.view')->group(function () {
        Route::get('layout', [LayoutWorkspaceController::class, 'index'])->name('layout.index');

        // Read-only spatial view — same permission as the layout it reflects.
        Route::get('map', [ProductionMapController::class, 'index'])->name('map');
        Route::get('layout/sites', [LayoutWorkspaceController::class, 'sites'])->name('layout.sites');
        Route::get('layout/sites/{site}/zones', [LayoutWorkspaceController::class, 'siteZones'])->name('layout.site_zones');
        Route::get('layout/sites/{site}/direct-spaces', [LayoutWorkspaceController::class, 'siteDirectSpaces'])->name('layout.site_direct_spaces');
        Route::get('layout/zones/{zone}/children', [LayoutWorkspaceController::class, 'zoneChildren'])->name('layout.zone_children');

        // ── Occupancy (Canvas rollups + bulk badges) ──
        // Registered ahead of growing-spaces/{growing_space} below —
        // same GET verb, so ordering keeps "occupancy-map" from being
        // swallowed by the {growing_space} route-model binding.
        Route::get('sites/{site}/occupancy', [OccupancyController::class, 'forSite'])->name('sites.occupancy');
        Route::get('zones/{zone}/occupancy', [OccupancyController::class, 'forZone'])->name('zones.occupancy');
        Route::get('growing-spaces/occupancy-map', [OccupancyController::class, 'map'])->name('growing-spaces.occupancy_map');
    });

    // ── Production Sites ──
    Route::post('sites', [ProductionSiteController::class, 'store'])
        ->name('sites.store')->middleware('permission:production_sites.create');
    Route::put('sites/{site}', [ProductionSiteController::class, 'update'])
        ->name('sites.update')->middleware('permission:production_sites.update');
    Route::delete('sites/{site}', [ProductionSiteController::class, 'destroy'])
        ->name('sites.destroy')->middleware('permission:production_sites.delete');

    // ── Zones ──
    Route::post('zones', [ProductionZoneController::class, 'store'])
        ->name('zones.store')->middleware('permission:production_zones.create');
    Route::put('zones/{zone}', [ProductionZoneController::class, 'update'])
        ->name('zones.update')->middleware('permission:production_zones.update');
    Route::delete('zones/{zone}', [ProductionZoneController::class, 'destroy'])
        ->name('zones.destroy')->middleware('permission:production_zones.delete');

    // ── Growing Space Types ──
    // The permission matrix has no production_growing_space_types feature —
    // types are configuration for growing spaces, so they share that gate.
    Route::get('growing-space-types', [GrowingSpaceTypeController::class, 'index'])
        ->name('growing_space_types.index')->middleware('permission:production_growing_spaces.view');
    Route::post('growing-space-types', [GrowingSpaceTypeController::class, 'store'])
        ->name('growing_space_types.store')->middleware('permission:production_growing_spaces.create');
    Route::put('growing-space-types/{growing_space_type}', [GrowingSpaceTypeController::class, 'update'])
        ->name('growing_space_types.update')->middleware('permission:production_growing_spaces.update');
    Route::delete('growing-space-types/{growing_space_type}', [GrowingSpaceTypeController::class, 'destroy'])
        ->name('growing_space_types.destroy')->middleware('permission:production_growing_spaces.delete');

    // ── Growing Spaces ──
    Route::post('growing-spaces', [GrowingSpaceController::class, 'store'])
        ->name('growing_spaces.store')->middleware('permission:production_growing_spaces.create');
    Route::put('growing-spaces/{growing_space}', [GrowingSpaceController::class, 'update'])
        ->name('growing_spaces.update')->middleware('permission:production_growing_spaces.update');
    Route::delete('growing-spaces/{growing_space}', [GrowingSpaceController::class, 'destroy'])
        ->name('growing_spaces.destroy')->middleware('permission:production_growing_spaces.delete');

    // Bulk Generator — a tool over Growing Space, not its own resource
    Route::post('growing-spaces/bulk/preview', [GrowingSpaceBulkGeneratorController::class, 'preview'])
        ->name('growing_spaces.bulk_preview')->middleware('permission:production_growing_spaces.create');
    Route::post('growing-spaces/bulk/generate', [GrowingSpaceBulkGeneratorController::class, 'generate'])
        ->name('growing_spaces.bulk_generate')->middleware('permission:production_growing_spaces.create');

    // Registered after the literal bulk/* paths above so they are not
    // swallowed by {growing_space}.
    Route::get('growing-spaces/{growing_space}', [GrowingSpaceController::class, 'show'])
        ->name('growing_spaces.show')->middleware('permission:production_growing_spaces.view');

    // ── Production Plans ──
    // Route::resource() used to register plans/create and plans/{plan}/edit,
    // which mapped to create()/edit() methods this controller does not have.
    // The screen is a single-page Alpine UI — create, edit and view all happen
    // in one modal — so those two routes were permanent 500s that nothing
    // linked to. Listed explicitly here instead.
    Route::middleware('permission:production_plans.view')->group(function () {
        Route::get('plans', [ProductionPlanController::class, 'index'])->name('plans.index');
        Route::get('plans/{plan}', [ProductionPlanController::class, 'show'])->name('plans.show');
    });

    Route::middleware('permission:production_plans.create')->group(function () {
        Route::post('plans', [ProductionPlanController::class, 'store'])->name('plans.store');
    });

    Route::middleware('permission:production_plans.edit')->group(function () {
        Route::put('plans/{plan}', [ProductionPlanController::class, 'update'])->name('plans.update');
        Route::post('plans/{plan}/confirm', [ProductionPlanController::class, 'confirm'])->name('plans.confirm');

        // Was POST plans/{plan}/complete -> 'complete'. The controller method is
        // close(), and the plans screen posts to /close — so the button hit a
        // 404 and the declared URI would have hit a missing method. Both ends
        // now agree on close.
        Route::post('plans/{plan}/close', [ProductionPlanController::class, 'close'])->name('plans.close');

        Route::post('plans/{plan}/cancel', [ProductionPlanController::class, 'cancel'])->name('plans.cancel');
    });

    Route::middleware('permission:production_plans.delete')->group(function () {
        Route::delete('plans/{plan}', [ProductionPlanController::class, 'destroy'])->name('plans.destroy');
    });

    // ── Plant Batches ──
    // Route::resource() is expanded here so each verb carries its own
    // permission. It also registered PUT plant-batches/{plant_batch} -> update(),
    // a method PlantBatchController does not have and no view links to — that
    // dead 500 route is gone.
    // Literal segments stay ahead of {plant_batch}.
    Route::middleware('permission:production_plant_batches.view')->group(function () {
        Route::get('plant-batches/prefill', [PlantBatchController::class, 'prefill'])->name('plant-batches.prefill');
        Route::get('plant-batches/source-options', [PlantBatchController::class, 'sourceOptions'])->name('plant-batches.source-options');
        Route::get('plant-batches/plant-options', [PlantBatchController::class, 'plantOptions'])->name('plant-batches.plant-options');
        Route::get('plant-batches/active', [PlantBatchController::class, 'activeBatches'])->name('plant-batches.active');
    });

    Route::middleware('permission:production_plant_batches.create')->group(function () {
        Route::get('plant-batches/create', [PlantBatchController::class, 'create'])->name('plant-batches.create');
        Route::post('plant-batches', [PlantBatchController::class, 'store'])->name('plant-batches.store');
    });

    Route::middleware('permission:production_plant_batches.view')->group(function () {
        Route::get('plant-batches', [PlantBatchController::class, 'index'])->name('plant-batches.index');

        // Literal segment must stay ahead of {plant_batch} in the same group.
        Route::get('plant-batches/{plantBatch}/pdf', [PlantBatchController::class, 'downloadPdf'])
            ->name('plant-batches.pdf');

        Route::get('plant-batches/{plant_batch}', [PlantBatchController::class, 'show'])->name('plant-batches.show');
    });

    Route::middleware('permission:production_plant_batches.update')->group(function () {
        Route::get('plant-batches/{plant_batch}/edit', [PlantBatchController::class, 'edit'])->name('plant-batches.edit');

        // Opening Stock batches only — manual current_quantity correction with ledger.
        Route::patch('plant-batches/{plantBatch}/adjust-quantity', [PlantBatchController::class, 'adjustQuantity'])
            ->name('plant-batches.adjust-quantity');
    });

    Route::delete('plant-batches/{plant_batch}', [PlantBatchController::class, 'destroy'])
        ->name('plant-batches.destroy')->middleware('permission:production_plant_batches.delete');

    Route::patch('plant-batches/{plantBatch}/status', [PlantBatchController::class, 'updateStatus'])
        ->name('plant-batches.update-status')->middleware('permission:production_plant_batches.change_status');

    // ── Placement & Occupancy ─────────────────────────────────────

    Route::post('plant-batches/{plantBatch}/place', [BatchPlacementController::class, 'place'])
        ->name('plant-batches.place')->middleware('permission:production_plant_batches.place');

    Route::delete('plant-batches/{plantBatch}/release', [BatchPlacementController::class, 'release'])
        ->name('plant-batches.release')->middleware('permission:production_plant_batches.release');

    Route::middleware('permission:production_plant_batches.view')->group(function () {
        Route::get('plant-batches/{plantBatch}/placement/current', [BatchPlacementController::class, 'current'])
            ->name('plant-batches.placement.current');

        Route::get('plant-batches/{plantBatch}/placements', [BatchPlacementController::class, 'history'])
            ->name('plant-batches.placements.history');

        Route::get('growing-spaces/{growingSpace}/occupancy', [BatchPlacementController::class, 'spaceOccupancy'])
            ->name('growing-spaces.occupancy');
    });

    // ── Zone Assignments (Employee ↔ Zone) ──────────────────────
    Route::get('zone-assignments', [ZoneAssignmentController::class, 'index'])
        ->name('zone-assignments.index')->middleware('permission:production_zone_assignments.view');
    Route::post('zone-assignments', [ZoneAssignmentController::class, 'store'])
        ->name('zone-assignments.store')->middleware('permission:production_zone_assignments.create');
    Route::delete('zone-assignments/{zoneAssignment}', [ZoneAssignmentController::class, 'destroy'])
        ->name('zone-assignments.destroy')->middleware('permission:production_zone_assignments.delete');

    // ── Activity Templates (Species → care schedule) ────────────
    Route::get('activity-templates', [ActivityTemplateController::class, 'index'])
        ->name('activity-templates.index')->middleware('permission:production_activity_templates.view');
    Route::post('activity-templates', [ActivityTemplateController::class, 'store'])
        ->name('activity-templates.store')->middleware('permission:production_activity_templates.create');
    Route::put('activity-templates/{activityTemplate}', [ActivityTemplateController::class, 'update'])
        ->name('activity-templates.update')->middleware('permission:production_activity_templates.update');
    Route::delete('activity-templates/{activityTemplate}', [ActivityTemplateController::class, 'destroy'])
        ->name('activity-templates.destroy')->middleware('permission:production_activity_templates.delete');

});