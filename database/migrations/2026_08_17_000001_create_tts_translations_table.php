<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tts_translations', function (Blueprint $table) {
            $table->id();

            // sha256 of the normalised source text. Content-addressed, so the
            // same English string shared across many products and tenants is
            // translated exactly once.
            $table->char('source_hash', 64);

            // BCP-47 target code, already normalised through config aliases.
            $table->string('target_language', 15);

            // Retained for debugging and for regenerating if a provider or
            // prompt changes and cached output needs to be reviewed.
            $table->text('source_text');
            $table->text('translated_text');

            // Provider identifier — allows swapping translation backends later
            // without losing the ability to tell old rows from new ones.
            $table->string('provider', 30)->default('gemini');
            $table->string('model', 60)->nullable();

            // Character count of the translated output. This is what will be
            // billed by TTS, so it is the number worth tracking.
            $table->unsignedInteger('characters')->default(0);

            $table->timestamps();

            $table->unique(['source_hash', 'target_language'], 'tts_translations_unique');
            $table->index('target_language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tts_translations');
    }
};