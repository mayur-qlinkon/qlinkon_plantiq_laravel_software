<?php

namespace App\Enums\Production;

enum BatchHarvestLogAction: string
{
    case QuantityCorrected = 'quantity_corrected';
    case Cancelled         = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::QuantityCorrected => 'Harvest Quantity Corrected',
            self::Cancelled         => 'Harvest Cancelled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}