<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\InvoiceReturnController;
use App\Http\Controllers\Admin\InvoiceWriteOffController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\ChallanController;
use App\Http\Controllers\Admin\ChallanReturnController;

/*
| Every route states the permission it needs.
|
| Route::resource() applies one middleware list to all seven verbs, so a
| resource guarded by `.view` let anyone who could read an invoice also create,
| edit and delete one. The create/update/delete permissions were seeded and
| assignable in the UI the whole time — switching them off simply had no
| effect. Several routes that move money (recording a payment, writing an
| invoice off, converting a quotation) carried no permission at all.
|
| Resources are written out verb by verb rather than with ->middleware() so the
| requirement for each is visible at the point it applies.
*/

Route::middleware('module:invoicing')->group(function () {
    // ── QUOTATIONS ──────────────────────────────────────────────────────────
    Route::controller(QuotationController::class)->group(function () {
        Route::get('quotations', 'index')->name('quotations.index')->middleware('permission:quotations.view');
        Route::get('quotations/create', 'create')->name('quotations.create')->middleware('permission:quotations.create');
        Route::post('quotations', 'store')->name('quotations.store')->middleware('permission:quotations.create');
        Route::get('quotations/{quotation}', 'show')->name('quotations.show')->middleware('permission:quotations.view');
        Route::get('quotations/{quotation}/edit', 'edit')->name('quotations.edit')->middleware('permission:quotations.update');
        Route::put('quotations/{quotation}', 'update')->name('quotations.update')->middleware('permission:quotations.update');
        Route::delete('quotations/{quotation}', 'destroy')->name('quotations.destroy')->middleware('permission:quotations.delete');

        // Creates an invoice, so it needs the invoice-creation right too.
        Route::post('quotations/{quotation}/convert', 'convertToInvoice')
            ->name('quotations.convert')
            ->middleware(['permission:quotations.convert', 'permission:invoices.create']);

        Route::post('quotations/{quotation}/mark-sent', 'markAsSent')->name('quotations.mark_sent')->middleware('permission:quotations.mark_sent');
        Route::get('quotations/{quotation}/pdf', 'downloadPdf')->name('quotations.pdf')->middleware('permission:quotations.download_pdf');
        Route::get('api/quotations/search-skus', 'searchSkus')->name('api.quotations.search-skus')->middleware('permission:quotations.view');
    });

    // ── CHALLANS ────────────────────────────────────────────────────────────
    Route::controller(ChallanController::class)->group(function () {
        Route::get('challans', 'index')->name('challans.index')->middleware('permission:challans.view');
        Route::get('challans/create', 'create')->name('challans.create')->middleware('permission:challans.create');
        Route::post('challans', 'store')->name('challans.store')->middleware('permission:challans.create');
        Route::get('challans/{challan}', 'show')->name('challans.show')->middleware('permission:challans.view');
        Route::get('challans/{challan}/edit', 'edit')->name('challans.edit')->middleware('permission:challans.update');
        Route::put('challans/{challan}', 'update')->name('challans.update')->middleware('permission:challans.update');
        Route::delete('challans/{challan}', 'destroy')->name('challans.destroy')->middleware('permission:challans.delete');

        Route::patch('challans/{challan}/status', 'updateStatus')->name('challans.status.update')->middleware('permission:challans.change_status');
        Route::get('challans/{challan}/pdf', 'downloadPdf')->name('challans.pdf')->middleware('permission:challans.download_pdf');
        Route::get('api/challans/search-skus', 'searchSkus')->name('api.challans.search-skus')->middleware('permission:challans.view');
    });

    // ── CHALLAN RETURNS ─────────────────────────────────────────────────────
    Route::controller(ChallanReturnController::class)->group(function () {
        Route::get('challan-returns', 'index')->name('challan-returns.index')->middleware('permission:challan_returns.view');
        Route::get('challan-returns/create', 'create')->name('challan-returns.create')->middleware('permission:challan_returns.create');
        Route::post('challan-returns', 'store')->name('challan-returns.store')->middleware('permission:challan_returns.create');
        Route::get('challan-returns/{challanReturn}', 'show')->name('challan-returns.show')->middleware('permission:challan_returns.view');
        Route::get('challan-returns/{challanReturn}/edit', 'edit')->name('challan-returns.edit')->middleware('permission:challan_returns.update');
        Route::put('challan-returns/{challanReturn}', 'update')->name('challan-returns.update')->middleware('permission:challan_returns.update');

        Route::get('challan-returns/{challanReturn}/pdf', 'downloadPdf')->name('challan-returns.pdf')->middleware('permission:challan_returns.download_pdf');
    });

    // ── INVOICES ────────────────────────────────────────────────────────────
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'index')->name('invoices.index')->middleware('permission:invoices.view');
        Route::get('invoices/create', 'create')->name('invoices.create')->middleware('permission:invoices.create');
        Route::post('invoices', 'store')->name('invoices.store')->middleware('permission:invoices.create');
        Route::get('invoices/{invoice}', 'show')->name('invoices.show')->middleware('permission:invoices.view');
        Route::get('invoices/{invoice}/edit', 'edit')->name('invoices.edit')->middleware('permission:invoices.update');
        Route::put('invoices/{invoice}', 'update')->name('invoices.update')->middleware('permission:invoices.update');
        Route::delete('invoices/{invoice}', 'destroy')->name('invoices.destroy')->middleware('permission:invoices.delete');

        Route::get('invoices/{invoice}/pdf', 'downloadPdf')->name('invoices.pdf')->middleware('permission:invoices.download_pdf');

        // Records money received. This carried no permission at all.
        Route::post('invoices/{invoice}/pay', 'addPayment')->name('invoices.pay')->middleware('permission:invoices.add_payment');

        Route::get('api/invoices/search-skus', 'searchSkus')->name('api.invoices.search-skus')->middleware('permission:invoices.view');
    });

    // ── INVOICE RETURNS ─────────────────────────────────────────────────────
    Route::controller(InvoiceReturnController::class)->group(function () {
        Route::get('invoice-returns', 'index')->name('invoice-returns.index')->middleware('permission:invoice_returns.view');
        Route::get('invoice-returns/{invoiceReturn}', 'show')->name('invoice-returns.show')->middleware('permission:invoice_returns.view');
        Route::get('invoice-returns/{invoiceReturn}/edit', 'edit')->name('invoice-returns.edit')->middleware('permission:invoice_returns.update');
        Route::put('invoice-returns/{invoiceReturn}', 'update')->name('invoice-returns.update')->middleware('permission:invoice_returns.update');
        Route::delete('invoice-returns/{invoiceReturn}', 'destroy')->name('invoice-returns.destroy')->middleware('permission:invoice_returns.delete');

        // Returns move stock and money back, so they are gated on creation
        // rather than on the ability to view the invoice they came from.
        Route::get('invoices/{invoice}/returns/create', 'create')->name('invoice-returns.create')->middleware('permission:invoice_returns.create');
        Route::post('invoices/{invoice}/returns', 'store')->name('invoice-returns.store')->middleware('permission:invoice_returns.create');
        Route::post('invoice-returns/{invoiceReturn}/confirm', 'confirm')->name('invoice-returns.confirm')->middleware('permission:invoice_returns.confirm');
        Route::get('invoice-returns/{invoiceReturn}/download-pdf', 'downloadPdf')->name('invoice-returns.download-pdf')->middleware('permission:invoice_returns.view');
    });

    // ── KASAR (write-offs) ──────────────────────────────────────────────────
    //
    // Writing an invoice off forgives money owed. There is no separate
    // write-off permission, so it is held to the same right as recording a
    // payment — the closest equivalent in what it does to a balance.
    Route::post('invoices/{invoice}/write-off', [InvoiceWriteOffController::class, 'store'])
        ->name('invoices.write-off.store')
        ->middleware('permission:invoices.add_payment');

    Route::post('invoice-write-offs/{invoiceWriteOff}/cancel', [InvoiceWriteOffController::class, 'cancel'])
        ->name('invoice-write-offs.cancel')
        ->middleware('permission:invoices.add_payment');

    // ── SALES REPORTS ───────────────────────────────────────────────────────
    Route::get('sales/reports', [SalesReportController::class, 'index'])->name('reports.index')->middleware('permission:sales_reports.view');
    Route::get('sales/reports/export', [SalesReportController::class, 'export'])->name('reports.export')->middleware('permission:sales_reports.export');

    // ── CUSTOMER INVOICE LEDGER ─────────────────────────────────────────────
    Route::prefix('ledger')
        ->name('ledger.')
        ->controller(LedgerController::class)
        ->middleware('permission:invoice_ledger.view')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{client}', 'show')->name('show');
        });
});