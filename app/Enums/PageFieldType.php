<?php

namespace App\Enums;

/**
 * The kinds of input a page template may declare.
 *
 * Deliberately few. Every type added here is one more thing the admin form,
 * the validator and the sanitiser each have to handle, so a type earns its
 * place only when no existing one will do.
 */
enum PageFieldType: string
{
    /** Single-line plain text. Escaped on output. */
    case Text = 'text';

    /** Multi-line plain text. Escaped on output, newlines preserved. */
    case Textarea = 'textarea';

    /**
     * Limited HTML — bold, italic, lists, links.
     *
     * The only type whose value is ever printed unescaped, and therefore the
     * only one that must pass through PageContentSanitizer first.
     */
    case RichText = 'richtext';

    /** A URL. Validated as such and used only in href/src positions. */
    case Url = 'url';

    public function isRich(): bool
    {
        return $this === self::RichText;
    }

    /**
     * Laravel validation rules for a value of this type.
     *
     * @return list<string>
     */
    public function rules(): array
    {
        return match ($this) {
            self::Text     => ['nullable', 'string', 'max:255'],
            self::Textarea => ['nullable', 'string', 'max:2000'],
            self::RichText => ['nullable', 'string', 'max:20000'],
            self::Url      => ['nullable', 'url', 'max:500'],
        };
    }
}