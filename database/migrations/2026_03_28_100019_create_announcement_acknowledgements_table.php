<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamp('read_at')->nullable();          // opened/viewed
            $table->timestamp('acknowledged_at')->nullable();  // clicked "I Accept"
            $table->timestamp('dismissed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
            $table->index(['user_id', 'acknowledged_at'], 'ack_user_acked');
            $table->index(['announcement_id', 'acknowledged_at'], 'ack_ann_acked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_acknowledgements');
    }
};
