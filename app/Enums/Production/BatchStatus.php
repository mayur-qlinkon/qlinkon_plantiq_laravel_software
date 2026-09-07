<?php

namespace App\Enums\Production;

enum BatchStatus: string
{
    case Active    = 'active';
    case Closed    = 'closed';
    case Cancelled = 'cancelled';

    // ── Labels ────────────────────────────────────────────────────

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Active',
            self::Closed    => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    // ── Colors — matches existing STATUS_COLORS array pattern ─────

    public function color(): array
    {
        return match($this) {
            self::Active    => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
            self::Closed    => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
            self::Cancelled => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
        };
    }

    // ── Transitions ───────────────────────────────────────────────

    /**
     * Closed -> Active is a correction path, not a normal lifecycle step.
     *
     * A batch closed on purpose has been fully harvested, so its quantity is
     * zero and PlantBatchService::updateStatus() refuses to reopen it. One
     * closed by a mistaken dropdown still physically holds plants, and before
     * this it could never be placed, harvested or moved again.
     *
     * Cancelled stays terminal: it means the batch never really existed.
     */
    public function transitions(): array
    {
        return match($this) {
            self::Active    => [self::Closed, self::Cancelled],
            self::Closed    => [self::Active],
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