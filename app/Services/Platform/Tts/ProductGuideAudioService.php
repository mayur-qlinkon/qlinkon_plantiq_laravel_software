<?php

namespace App\Services\Platform\Tts;

use App\Models\Platform\TtsAudio;
use App\Models\Product;
use Exception;

/**
 * Orchestrates audio generation for a single product guide entry.
 *
 * This is the only entry point the controller needs. It owns the decision of
 * WHAT text gets spoken, keeping that logic out of both the translation and
 * synthesis services so either can be reused elsewhere later.
 *
 * The controller passes a product and an index — never raw text. Resolving the
 * text server-side is what prevents the public endpoint from being used as a
 * free synthesis service against the account's billing.
 */
class ProductGuideAudioService
{
    public function __construct(
        protected TtsTranslationService $translator,
        protected GoogleTtsService $tts,
        protected TtsLanguageResolver $resolver,
    ) {
    }

    /**
     * Produce (or fetch from cache) the audio for one product guide entry.
     *
     * @throws Exception
     */
    public function forGuideEntry(
        Product $product,
        int     $index,
        ?string $language
    ): TtsAudio {

        $sourceText = $this->extractGuideText($product, $index);

        if ($sourceText === '') {
            throw new Exception('This section has no content to read.');
        }

        $resolvedLanguage = $this->resolver->normalise($language);

        $spokenText = $this->translator->translate($sourceText, $resolvedLanguage);

        return $this->tts->synthesize(
            text:      $spokenText,
            language:  $resolvedLanguage,
            companyId: $product->company_id,
        );
    }

    /**
     * Build the spoken text for a guide entry.
     *
     * The title is prepended so the listener knows which section is being read
     * without having to look at the screen — this mirrors what the previous
     * browser-based implementation did.
     */
    protected function extractGuideText(Product $product, int $index): string
    {
        $guide = $product->product_guide;

        if (! is_array($guide) || ! isset($guide[$index])) {
            throw new Exception('Guide section not found.');
        }

        $entry = $guide[$index];

        if (! is_array($entry)) {
            return '';
        }

        $title = trim((string) ($entry['title'] ?? ''));

        // 'desc' is an older key still present in some imported records.
        $description = trim((string) ($entry['description'] ?? ($entry['desc'] ?? '')));

        if ($description === '') {
            return '';
        }

        return $title !== ''
            ? $title . '. ' . $description
            : $description;
    }
}