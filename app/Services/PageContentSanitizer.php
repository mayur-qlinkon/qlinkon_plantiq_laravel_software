<?php

namespace App\Services;

use App\Enums\PageTemplate;
use Mews\Purifier\Facades\Purifier;

/**
 * Strips everything from tenant-supplied rich text except the few tags the
 * editor offers.
 *
 * Runs on save, on the server, always. The editor's own toolbar is a
 * convenience for the person typing — it is not a control, because the form
 * can be posted to directly with any markup at all.
 *
 * A whitelist, never a blacklist: it is not possible to enumerate every
 * dangerous construct, but it is entirely possible to enumerate the eight
 * tags a policy page needs.
 */
class PageContentSanitizer
{
    /** Purifier config key — see config/purifier.php. */
    private const PROFILE = 'page_content';

    /**
     * Clean every rich-text field on a template's data array.
     *
     * Plain text and textarea values are left untouched: they are printed
     * with {{ }} and escaped by Blade, so passing them through an HTML
     * cleaner would only mangle legitimate characters like < and &.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function clean(PageTemplate $template, array $data): array
    {
        foreach ($template->richTextKeys() as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            $data[$key] = is_string($value) && $value !== ''
                ? Purifier::clean($value, self::PROFILE)
                : null;
        }

        return $data;
    }
}