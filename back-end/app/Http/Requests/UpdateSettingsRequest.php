<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array', 'min:1'],
        ];

        foreach ($this->input('settings', []) as $key => $value) {
            $definition = Setting::definitions()[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            $keyRules = match ($definition['type']) {
                'boolean' => ['required', 'boolean'],
                'decimal' => array_merge(
                    ['required', 'numeric', 'min:'.$definition['min']],
                    isset($definition['max']) ? ['max:'.$definition['max']] : [],
                ),
                default => ['required', 'integer'],
            };

            $rules['settings.'.$key] = $keyRules;
        }

        return $rules;
    }

    /**
     * Add a validation error for each unsupported setting key.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator): void {
            foreach (array_keys($this->input('settings', [])) as $key) {
                if (! array_key_exists($key, Setting::definitions())) {
                    $validator->errors()->add('settings.'.$key, 'This setting is not supported.');
                }
            }
        });
    }
}
