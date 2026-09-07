<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shift_id')->nullable(); // So you know which shift applied that day

            $table->string('title');
            $table->longText('content');

            $table->enum('type', ['general', 'policy', 'event', 'holiday', 'urgent', 'celebration'])
                ->default('general');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])
                ->default('normal');
            $table->enum('status', ['draft', 'scheduled', 'published', 'expired'])
                ->default('draft');

            $table->enum('target_audience', ['all', 'department', 'store', 'designation', 'role', 'users'])
                ->default('all');
            $table->json('target_ids')->nullable(); // array of IDs based on target_audience

            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->boolean('requires_acknowledgement')->default(false);
            $table->boolean('is_pinned')->default(false);

            $table->string('attachment')->nullable();       // stored path
            $table->string('attachment_name')->nullable();  // original filename

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status', 'publish_at'], 'ann_company_status_publish');
            $table->index(['company_id', 'type', 'status'], 'ann_company_type_status');
            $table->index(['company_id', 'priority', 'status'], 'ann_company_priority_status');
            $table->index(['company_id', 'expire_at'], 'ann_company_expire');
            $table->index(['company_id', 'is_pinned', 'status'], 'ann_company_pinned_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
