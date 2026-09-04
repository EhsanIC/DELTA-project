<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'value',
    'type',
])]
class Setting extends Model
{
    /**
     * Define the settings supported by the admin API.
     *
     * @return array<string, array{type: string, default: int|float|bool, min?: int|float, max?: int|float}>
     */
    public static function definitions(): array
    {
        return [
            'target_margin' => ['type' => 'decimal', 'default' => 20.00, 'min' => 0, 'max' => 100],
            'minimum_operating_cash' => ['type' => 'decimal', 'default' => 10000.00, 'min' => 0],
            'fixed_shipping_cost' => ['type' => 'decimal', 'default' => 0.00, 'min' => 0],
            'per_unit_shipping_cost' => ['type' => 'decimal', 'default' => 0.00, 'min' => 0],
            'available_capacity_hours' => ['type' => 'decimal', 'default' => 40.00, 'min' => 0],
            'capacity_info_threshold_percent' => ['type' => 'decimal', 'default' => 70.00, 'min' => 0, 'max' => 100],
            'capacity_risk_threshold_percent' => ['type' => 'decimal', 'default' => 85.00, 'min' => 0, 'max' => 100],
            'capacity_critical_threshold_percent' => ['type' => 'decimal', 'default' => 100.00, 'min' => 0, 'max' => 100],
            'alerts_inventory_enabled' => ['type' => 'boolean', 'default' => true],
            'alerts_cash_enabled' => ['type' => 'boolean', 'default' => true],
            'alerts_margin_enabled' => ['type' => 'boolean', 'default' => true],
            'alerts_capacity_enabled' => ['type' => 'boolean', 'default' => true],
        ];
    }

    /**
     * Return all settings with their configured types applied.
     *
     * @return array<string, string|bool>
     */
    public static function values(): array
    {
        $stored = static::query()->get()->keyBy('key');
        $values = [];

        foreach (static::definitions() as $key => $definition) {
            $values[$key] = $stored->has($key)
                ? static::castValue($stored->get($key)->value, $definition['type'])
                : $definition['default'];
        }

        return $values;
    }

    /**
     * Persist the supplied settings while leaving unsupported keys untouched.
     *
     * @param array<string, mixed> $values
     */
    public static function setValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $definition = static::definitions()[$key];
            $storedValue = match ($definition['type']) {
                'boolean' => $value ? '1' : '0',
                'decimal' => number_format((float) $value, 2, '.', ''),
                default => (string) $value,
            };

            static::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $storedValue,
                    'type' => $definition['type'],
                ],
            );
        }
    }

    /**
     * Cast a stored value according to its definition.
     */
    private static function castValue(string $value, string $type): string|bool
    {
        return match ($type) {
            'boolean' => $value === '1',
            'decimal' => number_format((float) $value, 2, '.', ''),
            default => $value,
        };
    }
}
