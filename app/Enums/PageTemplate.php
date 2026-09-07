<?php

namespace App\Enums;

/**
 * The page layouts a tenant may choose from.
 *
 * A template is code: a Blade view under resources/views/storefront/pages,
 * plus the list of fields it needs. The tenant picks one and fills in text —
 * they never write markup, and cannot break the layout.
 *
 * This replaced a single longtext column that tenants were expected to fill
 * with raw HTML. Non-technical owners could not use it, and because the value
 * was printed unescaped, anyone who could edit a page could run script on
 * every storefront visitor.
 *
 * Adding a template: one case, one arm in each match below, one Blade file.
 */
enum PageTemplate: string
{
    case About   = 'about';
    case Contact = 'contact';
    case Legal   = 'legal';
    case Faq     = 'faq';
    case Simple  = 'simple';

    /**
     * Pages created before templates existed.
     *
     * Not offered in the picker — it exists so old rows still render while
     * their owners move them across. See PageTemplate::selectable().
     */
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::About   => 'About Us',
            self::Contact => 'Contact Us',
            self::Legal   => 'Legal / Policy',
            self::Faq     => 'FAQ',
            self::Simple  => 'Simple Page',
            self::Custom  => 'Legacy HTML',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::About   => 'Your story, with an intro and a section about how you work',
            self::Contact => 'Address, phone, email and opening hours',
            self::Legal   => 'Privacy policy, terms, refund policy — heading plus body text',
            self::Faq     => 'A list of common questions and answers',
            self::Simple  => 'A heading and one block of text',
            self::Custom  => 'An older page written in HTML',
        };
    }

    /** Lucide icon for the template picker. */
    public function icon(): string
    {
        return match ($this) {
            self::About   => 'building-2',
            self::Contact => 'map-pin',
            self::Legal   => 'scale',
            self::Faq     => 'circle-help',
            self::Simple  => 'file-text',
            self::Custom  => 'code',
        };
    }

    /** Blade view that renders this template on the storefront. */
    public function view(): string
    {
        return 'storefront.pages.'.$this->value;
    }

    /**
     * The pages.type value this template implies.
     *
     * Derived rather than asked. type drives footer grouping only, and a
     * tenant who has already said "Legal / Policy" should not then be made to
     * pick "Legal & Compliance" from a second list.
     */
    public function pageType(): string
    {
        return match ($this) {
            self::Legal            => \App\Models\Page::TYPE_LEGAL,
            self::About, self::Faq,
            self::Contact          => \App\Models\Page::TYPE_ABOUT,
            self::Simple,
            self::Custom           => \App\Models\Page::TYPE_CUSTOM,
        };
    }

    /**
     * Fields the tenant fills in, in the order they appear on the form.
     *
     * Keys become keys in pages.data. Renaming one orphans existing content,
     * so treat them as permanent once shipped.
     *
     * @return array<string, array{label: string, type: PageFieldType, help?: string, required?: bool}>
     */
    public function fields(): array
    {
        return match ($this) {
            self::About => [
                'hero_heading' => [
                    'label'    => 'Main heading',
                    'type'     => PageFieldType::Text,
                    'required' => true,
                ],
                'hero_text' => [
                    'label' => 'Short intro',
                    'type'  => PageFieldType::Textarea,
                    'help'  => 'One or two sentences below the heading.',
                ],
                'story_heading' => [
                    'label' => 'Section heading',
                    'type'  => PageFieldType::Text,
                    'help'  => 'For example: Our Story, How We Work.',
                ],
                'story_body' => [
                    'label' => 'Section text',
                    'type'  => PageFieldType::RichText,
                ],
            ],

            self::Contact => [
                'heading' => [
                    'label'    => 'Heading',
                    'type'     => PageFieldType::Text,
                    'required' => true,
                ],
                'intro' => [
                    'label' => 'Intro text',
                    'type'  => PageFieldType::Textarea,
                ],
                'address' => [
                    'label' => 'Address',
                    'type'  => PageFieldType::Textarea,
                ],
                'phone' => [
                    'label' => 'Phone',
                    'type'  => PageFieldType::Text,
                ],
                'email' => [
                    'label' => 'Email',
                    'type'  => PageFieldType::Text,
                ],
                'hours' => [
                    'label' => 'Opening hours',
                    'type'  => PageFieldType::Textarea,
                    'help'  => 'For example: Mon–Sat, 9 AM to 7 PM.',
                ],
                'map_embed_url' => [
                    'label' => 'Google Maps link',
                    'type'  => PageFieldType::Url,
                    'help'  => 'Optional. Paste the embed URL from Google Maps.',
                ],
            ],

            self::Legal => [
                'heading' => [
                    'label'    => 'Heading',
                    'type'     => PageFieldType::Text,
                    'required' => true,
                ],
                'effective_date' => [
                    'label' => 'Effective from',
                    'type'  => PageFieldType::Text,
                    'help'  => 'For example: 1 January 2026.',
                ],
                'body' => [
                    'label'    => 'Policy text',
                    'type'     => PageFieldType::RichText,
                    'required' => true,
                ],
            ],

            self::Faq => [
                'heading' => [
                    'label'    => 'Heading',
                    'type'     => PageFieldType::Text,
                    'required' => true,
                ],
                'intro' => [
                    'label' => 'Intro text',
                    'type'  => PageFieldType::Textarea,
                ],
                'body' => [
                    'label' => 'Questions and answers',
                    'type'  => PageFieldType::RichText,
                    'help'  => 'Put each question in bold, with its answer below.',
                ],
            ],

            self::Simple => [
                'heading' => [
                    'label'    => 'Heading',
                    'type'     => PageFieldType::Text,
                    'required' => true,
                ],
                'body' => [
                    'label'    => 'Text',
                    'type'     => PageFieldType::RichText,
                    'required' => true,
                ],
            ],

            // Nothing to edit — the old HTML is shown read-only until the
            // tenant moves the page onto a real template.
            self::Custom => [],
        };
    }

    /**
     * Validation rules for the whole data array, keyed data.<field>.
     *
     * Built from the field declarations so a new field cannot be added
     * without also being validated.
     *
     * @return array<string, list<string>>
     */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->fields() as $key => $field) {
            $fieldRules = $field['type']->rules();

            if ($field['required'] ?? false) {
                // Replaces 'nullable' rather than sitting alongside it —
                // the two together let an empty string through.
                $fieldRules = array_values(array_diff($fieldRules, ['nullable']));
                array_unshift($fieldRules, 'required');
            }

            $rules["data.{$key}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Field keys whose values hold HTML and must be sanitised on save.
     *
     * @return list<string>
     */
    public function richTextKeys(): array
    {
        return array_keys(array_filter(
            $this->fields(),
            fn (array $field) => $field['type']->isRich()
        ));
    }

    /**
     * Templates a tenant may pick. Custom is excluded on purpose.
     *
     * @return list<self>
     */
    public static function selectable(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $template) => $template !== self::Custom
        ));
    }
}