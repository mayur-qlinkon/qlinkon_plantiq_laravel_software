<?php

namespace App\Enums\Production;

enum HarvestStatus: string
{
    case Pending   = 'pending';
    case Received  = 'received';
    case Cancelled = 'cancelled';

    // ── Labels ────────────────────────────────────────────────────

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending Receiving',
            self::Received  => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    // ── Colors — matches existing STATUS_COLORS array pattern ─────

    public function color(): array
    {
        return match($this) {
            self::Pending   => ['bg' => '#fffbeb', 'text' => '#b45309', 'dot' => '#f59e0b'],
            self::Received  => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
            self::Cancelled => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
        };
    }

    // ── Transitions ───────────────────────────────────────────────

    public function transitions(): array
    {
        return match($this) {
            self::Pending   => [self::Received, self::Cancelled],
            self::Received  => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $new): bool
    {
        return in_array($new, $this->transitions());
    }

    // ── Utilities ─────────────────────────────────────────────────

    // Returns flat array of string values — used in Rule::in() validation.
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}