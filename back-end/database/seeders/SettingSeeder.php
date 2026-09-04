<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed the application's initial admin settings.
     */
    public function run(): void
    {
        $values = [];

        foreach (Setting::definitions() as $key => $definition) {
            $values[$key] = $definition['default'];
        }

        foreach ($values as $key => $value) {
            $definition = Setting::definitions()[$key];

            Setting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => match ($definition['type']) {
                        'boolean' => $value ? '1' : '0',
                        'decimal' => number_format((float) $value, 2, '.', ''),
                        default => (string) $value,
                    },
                    'type' => $definition['type'],
                ],
            );
        }
    }
}
