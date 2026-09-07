<?php

namespace App\Enums\Project;

/**
 * Lifecycle of WORK only. Never touched by payment activity — a fully paid
 * project can still be active, and a completed project can still be unpaid.
 */
enum ProjectStatus: string
{
    case Draft     = 'draft';
    case Active    = 'active';
    case OnHold    = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Active    => 'Active',
            self::OnHold    => 'On Hold',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft     => 'file-pen-line',
            self::Active    => 'play-circle',
            self::OnHold    => 'pause-circle',
            self::Completed => 'check-circle-2',
            self::Cancelled => 'x-circle',
        };
    }

    public function color(): array
    {
        return match ($this) {
            self::Draft     => ['bg' => '#f1f5f9', 'text' => '#475569', 'dot' => '#64748b'],
            self::Active    => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'dot' => '#2563eb'],
            self::OnHold    => ['bg' => '#fef3c7', 'text' => '#b45309', 'dot' => '#d97706'],
            self::Completed => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#16a34a'],
            self::Cancelled => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'dot' => '#dc2626'],
        };
    }

    /** Statuses that still represent live work. */
    public static function openValues(): array
    {
        return [self::Draft->value, self::Active->value, self::OnHold->value];
    }

    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases()
        );
    }
}