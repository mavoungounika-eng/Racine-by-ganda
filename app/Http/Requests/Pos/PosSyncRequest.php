<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du sync d'un lot de ventes offline POS Connect (Electron).
 *
 * Payload attendu : { orders: [{ offline_id, items[], payment_method, total, ... }] }
 * L'offline_id est OBLIGATOIRE pour chaque vente : il porte l'idempotence.
 */
class PosSyncRequest extends FormRequest
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
            'orders'                      => ['required', 'array', 'min:1'],
            'orders.*.offline_id'         => ['required', 'string', 'max:64'],
            'orders.*.items'              => ['required', 'array', 'min:1'],
            'orders.*.items.*.product_id' => ['required', 'integer', 'min:1'],
            'orders.*.items.*.quantity'   => ['required', 'integer', 'min:1'],
            'orders.*.items.*.price'      => ['nullable', 'numeric', 'min:0'],
            'orders.*.payment_method'     => ['required', 'string', 'in:cash,monetbil,stripe'],
            'orders.*.total'              => ['required', 'numeric', 'min:0'],
            'orders.*.currency'           => ['nullable', 'string', 'size:3'],
            'orders.*.monetbil_ref'       => ['nullable', 'string', 'max:191'],
            'orders.*.stripe_ref'         => ['nullable', 'string', 'max:191'],
        ];
    }
}
