<?php

namespace App\Enums\Client;

enum RegistrationType: string
{
    case REGISTERED = 'registered';
    case UNREGISTERED = 'unregistered';
    case COMPOSITION = 'composition';
    case OVERSEAS = 'overseas';
    case SEZ = 'sez';

    /**
     * Get the human-readable label for the UI dropdowns.
     */
    public function label(): string
    {
        return match($this) {
            self::REGISTERED => 'Regular (Registered)',
            self::UNREGISTERED => 'Unregistered',
            self::COMPOSITION => 'Composition',
            self::OVERSEAS => 'Overseas',
            self::SEZ => 'SEZ',
        };
    }

    /**
     * Get an array of all string values for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Get an associative array of values and labels for easy select populating.
     */
    public static function options(): array
    {
        $options = [];
        
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        
        return $options;
    }
}