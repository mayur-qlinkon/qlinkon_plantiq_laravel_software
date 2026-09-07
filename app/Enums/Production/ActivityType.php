<?php

namespace App\Enums\Production;

enum ActivityType: string
{
    case Watering    = 'watering';
    case Fertilizer  = 'fertilizer';
    case Inspection  = 'inspection';
    case DeadCheck   = 'dead_check';
    case Pruning     = 'pruning';
    case Sorting     = 'sorting';
    case Spraying    = 'spraying';
    case PestControl = 'pest_control';
    case Other       = 'other';

    // ── Labels ────────────────────────────────────────────────────

    public function label(): string
    {
        return match($this) {
            self::Watering    => 'Watering',
            self::Fertilizer  => 'Fertilizer',
            self::Inspection  => 'Inspection',
            self::DeadCheck   => 'Dead Check',
            self::Pruning     => 'Pruning',
            self::Sorting     => 'Sorting',
            self::Spraying    => 'Spraying',
            self::PestControl => 'Pest Control',
            self::Other       => 'Other',
        };
    }

    // Lucide icon name — used in Blade views and JS
    public function icon(): string
    {
        return match($this) {
            self::Watering    => 'droplets',
            self::Fertilizer  => 'flask-conical',
            self::Inspection  => 'scan-eye',
            self::DeadCheck   => 'file-x',
            self::Pruning     => 'scissors',
            self::Sorting     => 'arrow-up-down',
            self::Spraying    => 'spray-can',
            self::PestControl => 'bug',
            self::Other       => 'more-horizontal',
        };
    }

    // Tailwind Badge Colors (BG, Text, Dot) for UI representation
    public function color(): array
    {
        return match($this) {
            self::Watering    => ['bg' => '#e0f2fe', 'text' => '#0369a1', 'dot' => '#0284c7'],
            self::Fertilizer  => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#16a34a'],
            self::Inspection  => ['bg' => '#fef3c7', 'text' => '#b45309', 'dot' => '#d97706'],
            self::DeadCheck   => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'dot' => '#dc2626'],
            self::Pruning     => ['bg' => '#faf5ff', 'text' => '#6b21a8', 'dot' => '#9333ea'],
            self::Sorting     => ['bg' => '#f3e8ff', 'text' => '#7e22ce', 'dot' => '#a855f7'],
            self::Spraying    => ['bg' => '#ecfeff', 'text' => '#0e7490', 'dot' => '#06b6d4'],
            self::PestControl => ['bg' => '#fff7ed', 'text' => '#c2410c', 'dot' => '#ea580c'],
            self::Other       => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#6b7280'],
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