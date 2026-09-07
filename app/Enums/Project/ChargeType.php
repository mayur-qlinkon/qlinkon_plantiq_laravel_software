<?php

namespace App\Enums\Project;

/**
 * Why a charge exists. Purely descriptive — it never affects how money is
 * allocated. A renewal charge behaves exactly like any other charge.
 */
enum ChargeType: string
{
    case OneTime    = 'one_time';
    case Renewal    = 'renewal';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::OneTime    => 'One Time',
            self::Renewal    => 'Renewal',
            self::Adjustment => 'Adjustment',
        };
    }

    public function color(): array
    {
        return match ($this) {
            self::OneTime    => ['bg' => '#f1f5f9', 'text' => '#475569', 'dot' => '#64748b'],
            self::Renewal    => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#16a34a'],
            self::Adjustment => ['bg' => '#fef3c7', 'text' => '#b45309', 'dot' => '#d97706'],
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases()
        );
    }
}