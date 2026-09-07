<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseCategoryController;

// ── EXPENSES ──────────────────────────────────────────────────────────────
Route::middleware('module:expenses')->group(function () {

    Route::prefix('expenses')
        ->name('expenses.')
        ->group(function () {

            Route::get('/', [ExpenseController::class, 'index'])
                ->middleware('permission:expenses.view')
                ->name('index');

            Route::get('/create', [ExpenseController::class, 'create'])
                ->middleware('permission:expenses.create')
                ->name('create');

            Route::post('/', [ExpenseController::class, 'store'])
                ->middleware('permission:expenses.create')
                ->name('store');

            Route::get('/{expense}', [ExpenseController::class, 'show'])
                ->middleware('permission:expenses.view')
                ->name('show');

            Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])
                ->middleware('permission:expenses.update')
                ->name('edit');

            Route::put('/{expense}', [ExpenseController::class, 'update'])
                ->middleware('permission:expenses.update')
                ->name('update');

            Route::delete('/{expense}', [ExpenseController::class, 'destroy'])
                ->middleware('permission:expenses.delete')
                ->name('destroy');

            Route::patch('/{expense}/status', [ExpenseController::class, 'updateStatus'])
                ->middleware('permission:expenses.approve')
                ->name('status.update');

            Route::post('/{expenseId}/add-payment', [ExpenseController::class, 'addPayment'])
                ->middleware('permission:expenses.reimburse')
                ->name('add.payment');

            Route::get('/{expense}/receipt', [ExpenseController::class, 'receipt'])
                ->middleware('permission:expenses.view')
                ->name('receipt');
        });


    Route::patch('expense-categories/{expense_category}/toggle-status',
        [ExpenseCategoryController::class, 'toggleStatus'])
        ->middleware('permission:expense_categories.update')
        ->name('expense-categories.toggle-status');


    // Every verb carries its own permission. The group used to gate on
    // module:expenses alone, so anyone who could merely view expenses could
    // also create, rename and delete categories — while toggle-status right
    // above did check a permission.
    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->except(['create', 'show', 'edit'])
        ->parameters([
            'expense-categories' => 'expense_category',
        ])
        ->only(['index'])
        ->middleware('permission:expense_categories.view');

    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->only(['store'])
        ->parameters(['expense-categories' => 'expense_category'])
        ->middleware('permission:expense_categories.create');

    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->only(['update'])
        ->parameters(['expense-categories' => 'expense_category'])
        ->middleware('permission:expense_categories.update');

    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->only(['destroy'])
        ->parameters(['expense-categories' => 'expense_category'])
        ->middleware('permission:expense_categories.delete');
});