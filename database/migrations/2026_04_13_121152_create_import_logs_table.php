<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('row_data')->nullable();
            $table->text('error_message');
            $table->timestamps();
            // Date:25-4-26 9:45am
        });        
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
