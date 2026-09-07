<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
    ];

    // Single cache key
    const CACHE_KEY = 'global_system_settings';

    // ════════════════════════════════════════════════════
    // BOOTED — clear cache on change
    // ════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    // ════════════════════════════════════════════════════
    // INTERNAL — central cache loader (NEW IMPROVEMENT)
    // Avoids repeating Cache::rememberForever everywhere
    // ════════════════════════════════════════════════════

    protected static function getCachedCollection()
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()->get()->keyBy('key');
        });
    }

    // ════════════════════════════════════════════════════
    // ACCESSOR — parsed value
    // (unchanged logic)
    // ════════════════════════════════════════════════════

    public function getParsedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $this->value,
            'json', 'array' => json_decode($this->value, true),
            'float', 'double' => (float) $this->value,
            default => $this->value,
        };
    }

    // ════════════════════════════════════════════════════
    // getSetting — unchanged behavior
    // ════════════════════════════════════════════════════

    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = self::getCachedCollection();

        $setting = $settings->get($key);

        return $setting ? $setting->parsed_value : $default;
    }

    // ════════════════════════════════════════════════════
    // setSetting — unchanged behavior
    // ════════════════════════════════════════════════════

    public static function setSetting(
        string $key,
        mixed $value,
        string $group = 'general',
        string $type = 'string'
    ): self {

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        }

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'group' => $group,
                'type' => $type,
            ]
        );
    }

    // ════════════════════════════════════════════════════
    // NEW — isEnabled()
    // ════════════════════════════════════════════════════

    public static function isEnabled(string $key, bool $default = false): bool
    {
        $value = static::getSetting($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    // ════════════════════════════════════════════════════
    // NEW — allCached()
    // ════════════════════════════════════════════════════

    public static function allCached(): array
    {
        return self::getCachedCollection()
            ->mapWithKeys(fn ($s) => [$s->key => $s->value])
            ->toArray();
    }

    // ════════════════════════════════════════════════════
    // NEW — forGroup()
    // ════════════════════════════════════════════════════

    public static function forGroup(string $group): array
    {
        return self::getCachedCollection()
            ->filter(fn ($s) => $s->group === $group)
            ->mapWithKeys(fn ($s) => [$s->key => $s->parsed_value])
            ->toArray();
    }
}
