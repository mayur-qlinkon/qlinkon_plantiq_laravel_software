<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_daily_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('plant_batch_id')->constrained('production_plant_batches')->cascadeOnDelete();
            $table->foreignId('batch_placement_id')->nullable()->constrained('production_batch_placements')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('production_zones')->nullOnDelete();
            $table->foreignId('batch_activity_id')->nullable()
                ->constrained('production_batch_activities')->nullOnDelete();

            // Template historically snapshot ho jaata hai — agar template baad me delete/change ho
            // jaaye to bhi ye row batata hai ki us din kaunsi activity thi.
            $table->foreignId('activity_template_id')->nullable()->constrained('production_activity_templates')->nullOnDelete();
            $table->string('activity_type', 30);
            $table->boolean('is_required')->default(true);

            $table->date('due_date');
            $table->string('status', 20)->default('pending'); // pending | done | skipped

            $table->foreignId('completed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Ek din, ek batch, ek activity template — sirf ek hi task instance
            // (command dobara chale to bhi duplicate nahi banega)
            $table->unique(['plant_batch_id', 'activity_template_id', 'due_date'], 'pdt_unique_task');

            $table->index(['company_id', 'due_date', 'status'], 'pdt_company_date_status');
            $table->index(['zone_id', 'due_date', 'status'], 'pdt_zone_date_status');
            $table->index(['plant_batch_id', 'due_date'], 'pdt_batch_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_daily_tasks');
    }
};