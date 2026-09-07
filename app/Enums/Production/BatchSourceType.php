<?php

namespace App\Enums\Production;

enum BatchSourceType: string
{
    case ProductionPlan   = 'production_plan';
    case Purchase         = 'purchase';
    case OpeningStock     = 'opening_stock';
    case BranchTransfer   = 'branch_transfer';
    case Other            = 'other';

    // ── Labels ────────────────────────────────────────────────────

    public function label(): string
    {
        return match($this) {
            self::ProductionPlan => 'Production Plan',
            self::Purchase       => 'Purchase',
            self::OpeningStock   => 'Opening Stock',
            self::BranchTransfer => 'Branch Transfer',
            self::Other          => 'Other',
        };
    }

    // ── Source behavior ───────────────────────────────────────────

    // Whether this source type carries a source_reference_id.
    public function hasReference(): bool
    {
        return match($this) {
            self::ProductionPlan, self::Purchase => true,
            default                              => false,
        };
    }

    // Whether the batch create form should be prefilled from the source.
    public function isPrefilled(): bool
    {
        return $this->hasReference();
    }

    // ── Utilities ─────────────────────────────────────────────────

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Returns [value => label] — for select dropdowns in Blade.
    public static function options(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}