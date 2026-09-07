<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\Admin\AiChatbotController;
use App\Http\Controllers\Admin\OcrScannerController;
use App\Http\Controllers\Admin\LabelController;

// ── BULK IMPORT ──────────────────────────────────────────────────────────────
Route::prefix('bulk-import')->name('bulk-import.')->middleware('module:bulk_import')->controller(BulkImportController::class)->group(function () {
    Route::get('/reference', 'reference')->name('reference');
    Route::get('/', 'index')->name('index');
    Route::get('/sample/{type}', 'downloadSample')->name('sample');
    Route::get('/errors/{import}', 'downloadErrors')->name('errors');
    Route::get('/export/{type}', 'exportExistingData')
    ->name('export');
    Route::get('/export-all', 'exportAllData')
    ->name('export-all');
    // Product Images (ZIP) — MUST stay above the generic {type} routes below,
    // otherwise 'product-images' matches {type} and never reaches these.
    Route::get('/product-images/guide', 'downloadImageGuide')->name('product-images.guide');
    Route::post('/product-images/upload', 'uploadProductImages')->name('product-images.upload');
    Route::post('/product-images/process', 'processProductImages')->name('product-images.process');

    // Every CSV import type registered in ImportTypeRegistry. Adding a type
    // needs no route change — the registry is the only place it is declared.
    Route::post('/{type}/upload', 'upload')->name('upload');
    Route::post('/{type}/process', 'process')->name('process');
});

// ── AI ASSISTANT ──────────────────────────────────────────────────────────────
Route::prefix('ai-chatbot')
    ->name('ai-chatbot.')
    ->middleware(['module:ai_assistant', 'permission:ai_assistant.access'])
    ->controller(AiChatbotController::class)
    ->group(function () {
        Route::post('/chat', 'chat')->name('chat')->middleware('throttle:ai-chat');
        Route::get('/quick-questions', 'quickQuestions')->name('quick-questions');
        Route::get('/usage', 'usage')->name('usage');

        // ── Chat history (per-user, tenant-scoped) ──
        Route::get('/conversations', 'conversations')->name('conversations');
        Route::get('/conversations/{conversation}', 'conversation')->name('conversation');
        Route::delete('/conversations/{conversation}', 'destroyConversation')->name('conversations.destroy');
});

// ── OCR Scanner ──────────────────────────────────────────────────────────────
Route::prefix('ocr-scanner')
    ->middleware(['module:ocr_scanner', 'permission:ocr_scanner.access'])
    ->name('ocr-scanner.')
    ->controller(OcrScannerController::class)
    ->group(function () {

        // Permissions are applied per route, not once on the group. The group
        // previously required only ocr_scanner.view, so a view-only user could
        // still burn Gemini quota through /process and archive other people's
        // scans through DELETE.

        // Main scan page — camera / upload UI
        Route::get('/', 'index')->name('index')->middleware('permission:ocr_scanner.view');

        // AJAX: process uploaded image through OCR engine
        Route::post('/process', 'process')->name('process')->middleware('throttle:ocr-scan');

        // AJAX: save confirmed/edited extracted data
        Route::post('/save', 'save')->name('save');

        // History list page
        Route::get('/history', 'history')->name('history')->middleware('permission:ocr_scanner.history');

        // Authenticated image stream — must sit above /{id}
        Route::get('/{id}/image', 'image')->name('image')->middleware('permission:ocr_scanner.view');

        // AJAX: get single scan detail (for history modal re-use)
        Route::get('/{id}', 'show')->name('show')->middleware('permission:ocr_scanner.view');

        // Archive (soft-delete) a scan
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:ocr_scanner.delete');
});

// ── LABEL PRINTING ──────────────────────────────────────────────────────────────
Route::middleware('module:label_printing')->group(function () {
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');            
    Route::post('/labels/download-pdf', [LabelController::class, 'downloadPdf'])->name('labels.download-pdf');
    Route::get('/labels/render', [LabelController::class, 'renderImage'])->name('labels.render-image');
    Route::get('/api/labels/search', [LabelController::class, 'fetchProducts'])->name('labels.fetch-products');
    Route::post('/api/labels/selected', [LabelController::class, 'fetchSelectedSkus'])->name('api.labels.selected');
});