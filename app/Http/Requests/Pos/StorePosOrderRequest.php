<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la création d'une vente POS Connect (Electron).
 *
 * Payload attendu :
 * {
 *   items: [{ product_id, quantity, price? }],
 *   payment_method: 'cash'|'monetbil'|'stripe',
 *   total: number,
 *   currency?: 'XAF',
 *   monetbil_ref?: string,
 *   stripe_ref?: string,
 *   offline_id?: string
 * }
 */
class StorePosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'accès est déjà contrôlé par auth:sanctum + signature.subscription.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.price'      => ['nullable', 'numeric', 'min:0'],
            'payment_method'     => ['required', 'string', 'in:cash,monetbil,stripe'],
            'total'              => ['required', 'numeric', 'min:0'],
            'currency'           => ['nullable', 'string', 'size:3'],
            'monetbil_ref'       => ['nullable', 'string', 'max:191', 'required_if:payment_method,monetbil'],
            'stripe_ref'         => ['nullable', 'string', 'max:191', 'required_if:payment_method,stripe'],
            'offline_id'         => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monetbil_ref.required_if' => 'La référence Monetbil est requise pour un paiement mobile money.',
            'stripe_ref.required_if'   => 'La référence Stripe est requise pour un paiement carte.',
        ];
    }
}
