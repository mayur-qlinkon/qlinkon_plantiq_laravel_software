<?php

namespace App\Console\Commands\Tools;

use App\Services\Platform\Tts\GoogleTtsService;
use App\Services\Platform\Tts\TtsLanguageResolver;
use App\Services\Platform\Tts\TtsTranslationService;
use Illuminate\Console\Command;

/**
 * End-to-end test of the translate-then-synthesise pipeline for one language.
 *
 * Surfaces the raw exception instead of the generic message the public
 * endpoint returns, which is what makes a single-language failure diagnosable.
 */
class TtsTestCommand extends Command
{
    protected $signature = 'tts:test
                            {language=gu-IN : Language code to test}
                            {--text= : Custom text to speak}';

    protected $description = 'Run a full TTS pipeline test for one language';

    public function handle(
        TtsLanguageResolver $resolver,
        TtsTranslationService $translator,
        GoogleTtsService $tts
    ): int {
        $requested = $this->argument('language');
        $text      = $this->option('text')
            ?: 'Watering. Water this plant once or twice a week. Allow the top layer of soil to dry between waterings.';

        // ── 1. Resolve ──
        $this->line('<comment>1. Resolving language and voice</comment>');

        try {
            $resolved = $resolver->resolveVoice($requested);
        } catch (\Throwable $e) {
            $this->error('Resolution failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line("   requested : {$requested}");
        $this->line("   language  : {$resolved['language']}");
        $this->line("   voice     : {$resolved['voice']}");
        $this->line("   tier      : {$resolved['tier']}");

        if ($resolved['language'] !== $resolver->normalise($requested)) {
            $this->warn('   Language fell back — no permitted voice for the requested one.');
        }

        // ── 2. Translate ──
        $this->newLine();
        $this->line('<comment>2. Translating</comment>');
        $this->line('   source (' . mb_strlen($text) . " chars): {$text}");

        $spoken = $translator->translate($text, $resolved['language']);

        $this->line('   spoken (' . mb_strlen($spoken) . " chars): {$spoken}");

        if ($spoken === $text && ! $resolver->isSourceLanguage($resolved['language'])) {
            $this->warn('   Translation returned the source text unchanged — the model call');
            $this->warn('   likely failed and was caught. Check the log for the reason.');
        }

        // ── 3. Synthesise ──
        $this->newLine();
        $this->line('<comment>3. Synthesising</comment>');

        try {
            $audio = $tts->synthesize($spoken, $resolved['language']);
        } catch (\Throwable $e) {
            $this->error('   Synthesis failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('   OK');
        $this->line('   voice      : ' . $audio->voice_name);
        $this->line('   characters : ' . $audio->characters);
        $this->line('   size       : ' . number_format($audio->bytes / 1024, 1) . ' KB');
        $this->line('   path       : ' . $audio->path);
        $this->line('   url        : ' . $audio->url);

        return self::SUCCESS;
    }
}