<?php

namespace App\Enums\Production;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Done    = 'done';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Done    => 'Done',
            self::Skipped => 'Skipped',
        };
    }

    public function color(): array
    {
        return match ($this) {
            self::Pending => ['bg' => '#fffbeb', 'text' => '#92400e', 'dot' => '#f59e0b'],
            self::Done    => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
            self::Skipped => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}