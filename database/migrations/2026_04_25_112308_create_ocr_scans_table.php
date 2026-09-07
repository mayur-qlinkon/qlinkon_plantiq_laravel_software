<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: ocr_scans
     * Purpose: Stores every OCR scan attempted by a tenant user.
     *          The `extracted_data` JSON column holds the raw parsed fields
     *          so we never lose OCR output, even if the user edits before saving.
     */
    public function up(): void
    {
        Schema::create('ocr_scans', function (Blueprint $table) {
            $table->id();

            // ── Tenant scoping ──────────────────────────────────────────────
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();            
            
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // ── Scan metadata ───────────────────────────────────────────────
            $table->string('scan_type')->default('business_card');
            // future values: 'invoice', 'expense_receipt', 'id_card', etc.

            $table->string('original_filename')->nullable();
            $table->string('image_path')->nullable(); // stored in storage/app/public/ocr/

            // ── OCR output ──────────────────────────────────────────────────
            $table->text('raw_ocr_text')->nullable();   // full text returned by OCR.space
            $table->json('extracted_data')->nullable();  // structured fields (name, phone, email …)
            $table->json('edited_data')->nullable();     // what the user confirmed / edited before save

            // ── Status ──────────────────────────────────────────────────────
            $table->string('status')->default('pending');
            // pending | completed | failed | saved

            $table->string('ocr_engine')->default('OCRSpace'); // future-proof
            $table->boolean('is_archived')->default(false);
            

            $table->text('notes')->nullable(); // optional user notes

            $table->timestamps();

            // ── Indexes ─────────────────────────────────────────────────────
            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'scan_type']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_scans');
    }
};