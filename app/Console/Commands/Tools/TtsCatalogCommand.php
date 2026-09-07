<?php

namespace App\Console\Commands\Tools;

use App\Services\Platform\Tts\TtsLanguageResolver;
use App\Services\Platform\Tts\TtsVoiceCatalog;
use Illuminate\Console\Command;

/**
 * Inspects and refreshes the TTS voice catalog.
 *
 * Exists because voice selection is now dynamic: when audio fails for one
 * language, the first question is always "which voice did it actually try?"
 * and that answer should not require adding temporary logging.
 */
class TtsCatalogCommand extends Command
{
    protected $signature = 'tts:catalog
                            {language? : Language code to inspect, e.g. gu-IN}
                            {--refresh : Discard the cached catalog and refetch}';

    protected $description = 'Inspect or refresh the Google TTS voice catalog';

    public function handle(TtsVoiceCatalog $catalog, TtsLanguageResolver $resolver): int
    {
        if ($this->option('refresh')) {
            $catalog->forget();
            $this->info('Catalog cache cleared.');
        }

        $languages = $catalog->languages();

        if (empty($languages)) {
            $this->error('Catalog is empty. Check GOOGLE_TTS_API_KEY and the logs.');

            return self::FAILURE;
        }

        $this->info(sprintf('Catalog holds %d languages.', count($languages)));
        $this->newLine();

        $requested = $this->argument('language');

        // No language given: show what each configured app language resolves to.
        if (! $requested) {
            $rows = [];

            foreach (array_keys((array) config('tts.language_names', [])) as $code) {
                try {
                    $resolved = $resolver->resolveVoice($code);
                    $rows[]   = [
                        $code,
                        $resolved['language'] === $code ? 'yes' : 'FALLBACK → ' . $resolved['language'],
                        $resolved['tier'],
                        $resolved['voice'],
                    ];
                } catch (\Throwable $e) {
                    $rows[] = [$code, 'ERROR', '-', $e->getMessage()];
                }
            }

            $this->table(['Requested', 'Supported', 'Tier', 'Voice'], $rows);

            return self::SUCCESS;
        }

        // Specific language: list every usable voice Google offers for it.
        $normalised = $resolver->normalise($requested);

        if ($normalised !== $requested) {
            $this->warn("'{$requested}' normalised to '{$normalised}'.");
        }

        $voices = $catalog->forLanguage($normalised);

        if (empty($voices)) {
            $this->error("No permitted voices for {$normalised}.");
            $this->line('Permitted tiers: ' . implode(', ', array_keys((array) config('tts.tier_costs', []))));

            return self::FAILURE;
        }

        $this->table(
            ['Voice', 'Tier', 'Gender'],
            array_map(fn ($v) => [$v['name'], $v['tier'], $v['gender']], $voices)
        );

        $picked = $resolver->resolveVoice($normalised);
        $this->info("Selected: {$picked['voice']} ({$picked['tier']})");

        return self::SUCCESS;
    }
}