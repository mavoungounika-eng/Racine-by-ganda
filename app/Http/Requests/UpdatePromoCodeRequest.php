<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin handles auth
    }

    public function rules(): array
    {
        $promoCodeId = $this->route('promo_code')?->id ?? $this->route('promo_code');

        return [
            'code'              => ['required', 'string', 'max:50', Rule::unique('promo_codes', 'code')->ignore($promoCodeId)],
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'type'              => ['required', Rule::in(['percentage', 'fixed', 'free_shipping'])],
            'value'             => ['nullable', 'numeric', 'min:0', 'required_unless:type,free_shipping'],
            'min_amount'        => ['nullable', 'numeric', 'min:0'],
            'max_uses'          => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at'         => ['nullable', 'date'],
            'expires_at'        => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required_unless'     => 'La valeur est requise pour les codes de type pourcentage ou montant fixe.',
            'expires_at.after_or_equal' => 'La date d expiration doit être postérieure à la date de début.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code'      => $this->code ? strtoupper(trim($this->code)) : null,
            'is_active' => $this->boolean('is_active'),
        ]);

        if ($this->input('type') === 'free_shipping' && $this->input('value') === null) {
            $this->merge(['value' => 0]);
        }

        if ($this->input('type') === 'percentage' && is_numeric($this->input('value'))) {
            $v = (float) $this->input('value');
            if ($v > 100) {
                $this->merge(['value' => 100]);
            }
        }
    }
}
