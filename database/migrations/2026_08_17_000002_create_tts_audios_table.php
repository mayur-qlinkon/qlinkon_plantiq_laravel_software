<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tts_audios', function (Blueprint $table) {
            $table->id();

            // sha256 over text + language + voice + encoding + rate + pitch.
            // Because every synthesis parameter feeds the key, changing any
            // audio setting in config produces new entries instead of serving
            // stale audio generated under the old settings.
            $table->char('cache_key', 64)->unique();

            // Denormalised columns — not used for lookup, but make reporting
            // and cleanup queries possible without rehashing everything.
            $table->char('text_hash', 64);
            $table->string('language_code', 15);
            $table->string('voice_name', 60);
            $table->string('voice_tier', 20);

            // First ~180 chars of the spoken text, for admin/debug visibility.
            $table->string('text_preview', 255)->nullable();

            // Disk is stored per row so existing audio keeps resolving if the
            // default disk is later switched (e.g. public -> s3).
            $table->string('disk', 30)->default('public');
            $table->string('path', 255);
            $table->string('format', 10)->default('mp3');

            // Billable characters sent to Google for this specific clip.
            $table->unsignedInteger('characters')->default(0);
            $table->unsignedInteger('bytes')->default(0);

            // Attribution for the tenant that triggered the first generation.
            // Nullable and not a foreign key: the cache row must survive the
            // company being deleted, since other tenants may share this audio.
            $table->unsignedBigInteger('generated_for_company_id')->nullable();

            // Usage counters — the basis for a future LRU cleanup command that
            // prunes clips nobody has played in months.
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['language_code', 'voice_tier']);
            $table->index('last_used_at');
            $table->index('generated_for_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tts_audios');
    }
};