<?php

namespace App\Enums\Production;

enum LossReason: string
{
    case Mortality  = 'mortality';
    case Disease    = 'disease';
    case Pest       = 'pest';
    case Weather    = 'weather';
    case Mechanical = 'mechanical';
    case Theft      = 'theft';
    case Other      = 'other';

    // ── Labels ────────────────────────────────────────────────────

    public function label(): string
    {
        return match($this) {
            self::Mortality  => 'Natural Mortality',
            self::Disease    => 'Disease',
            self::Pest       => 'Pest Damage',
            self::Weather    => 'Weather / Climate',
            self::Mechanical => 'Mechanical Damage',
            self::Theft      => 'Theft',
            self::Other      => 'Other',
        };
    }

    // ── Utilities ─────────────────────────────────────────────────

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // [value => label] — for select dropdowns in Blade
    public static function options(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}