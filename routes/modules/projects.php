<?php

use App\Http\Controllers\Admin\Project\ProjectChargeController;
use App\Http\Controllers\Admin\Project\ProjectClientServiceController;
use App\Http\Controllers\Admin\Project\ProjectController;

use App\Http\Controllers\Admin\Project\ProjectPaymentController;
use App\Http\Controllers\Admin\Project\ProjectRenewalController;
use App\Http\Controllers\Admin\Project\ProjectServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Projects Module
|--------------------------------------------------------------------------
| Standalone module. It holds no foreign keys into Invoicing or Quotations,
| so a tenant can license it on its own; quotation_id and invoice_id on a
| project are tracking references only.
|
| Money never moves through the routes in this file. Charges, payments and
| renewals get their own controllers so that raising a bill, taking money and
| forgiving a debt each stay separately permissioned.
*/

Route::middleware(['module:projects'])->group(function () {

    // ── Service Catalog ──────────────────────────────────────────────────────
    // Company-level price list. Not store scoped: a catalog is a template,
    // not a transaction.
    Route::prefix('project-services')
        ->middleware('permission:project_services.view')
        ->name('services.')
        ->controller(ProjectServiceController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');

            // The group only gates reading. Every write below carries its own
            // permission — the UI already hides these buttons by the same
            // slugs, and a hidden button is not an access control.
            Route::post('/', 'store')
                ->middleware('permission:project_services.create')
                ->name('store');

            Route::put('/{projectService}', 'update')
                ->middleware('permission:project_services.update')
                ->name('update');

            Route::delete('/{projectService}', 'destroy')
                ->middleware('permission:project_services.delete')
                ->name('destroy');
        });

    // ── Projects ─────────────────────────────────────────────────────────────
    Route::prefix('projects')
        ->middleware('permission:projects.view')
        ->name('projects.')
        ->controller(ProjectController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{project}', 'show')->name('show');

            Route::post('/', 'store')
                ->middleware('permission:projects.create')
                ->name('store');

            Route::put('/{project}', 'update')
                ->middleware('permission:projects.update')
                ->name('update');

            // Delivery status only. Money never moves through this route, but
            // it still changes the project, so it rides on the same permission
            // as any other edit.
            Route::patch('/{project}/status', 'updateStatus')
                ->middleware('permission:projects.update')
                ->name('status');

            Route::delete('/{project}', 'destroy')
                ->middleware('permission:projects.delete')
                ->name('destroy');
        });




    // ── Charges ──────────────────────────────────────────────────────────────
    // The primary path: most billable work is a one-off job, not a project.
    Route::prefix('project-charges')
        ->middleware('permission:project_charges.view')
        ->name('project_charges.')
        ->controller(ProjectChargeController::class)
        ->group(function () {
            Route::post('/', 'store')
                ->middleware('permission:project_charges.create')
                ->name('store');

            Route::put('/{charge}', 'update')
                ->middleware('permission:project_charges.update')
                ->name('update');

            // Cancelling kills a live debt. Separate from raising one.
            Route::post('/{charge}/cancel', 'cancel')
                ->middleware('permission:project_charges.cancel')
                ->name('cancel');

            // Forgiving a debt is a higher level of trust than raising one.
            Route::post('/{charge}/write-off', 'writeOff')
                ->middleware('permission:project_charges.write_off')
                ->name('write_off');
        });

    // ── Renewal Board ────────────────────────────────────────────────────────
    // Read-only view over client services. Reuses the client-services
    // permission rather than adding a new slug — it exposes nothing extra,
    // just a different lens on the same rows.
    Route::get('project-renewals', [ProjectRenewalController::class, 'index'])
        ->middleware('permission:project_client_services.view')
        ->name('project_renewals.index');

    // ── Client Services ──────────────────────────────────────────────────────
    Route::prefix('project-client-services')
        ->middleware('permission:project_client_services.view')
        ->name('project_client_services.')
        ->controller(ProjectClientServiceController::class)
        ->group(function () {
            Route::post('/', 'store')
                ->middleware('permission:project_client_services.create')
                ->name('store');

            Route::put('/{clientService}', 'update')
                ->middleware('permission:project_client_services.update')
                ->name('update');

            // Renewing raises a real charge, so it is a billing action rather
            // than an edit — hence its own slug.
            Route::post('/{clientService}/renew', 'renew')
                ->middleware('permission:project_client_services.renew')
                ->name('renew');

            Route::post('/{clientService}/cancel', 'cancel')
                ->middleware('permission:project_client_services.cancel')
                ->name('cancel');
        });

    // ── Payments ─────────────────────────────────────────────────────────────
    Route::prefix('project-payments')
        ->middleware('permission:project_payments.view')
        ->name('project_payments.')
        ->controller(ProjectPaymentController::class)
        ->group(function () {
            // Feeds the payment screen before the user types anything.
            Route::get('/context', 'context')->name('context');

            // Recording money received is a write, not a read. This was the
            // worst of the gaps: view-only staff could book a client's payment.
            Route::post('/', 'store')
                ->middleware('permission:project_payments.create')
                ->name('store');

            // Advanced allocation and undo actions are separately permissioned:
            // recording money and moving it around are different levels of trust.
            Route::post('/{payment}/allocate', 'allocate')
                ->middleware('permission:project_payments.allocate')
                ->name('allocate');

            Route::post('/allocations/{allocation}/reverse', 'reverseAllocation')
                ->middleware('permission:project_payments.reverse')
                ->name('allocations.reverse');

            Route::post('/{payment}/reverse', 'reverse')
                ->middleware('permission:project_payments.reverse')
                ->name('reverse');

            Route::post('/refund', 'refund')
                ->middleware('permission:project_payments.reverse')
                ->name('refund');
        });

});