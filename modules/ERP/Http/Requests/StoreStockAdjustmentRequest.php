<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access-erp');
    }

    /**
     * Règles de validation pour un ajustement de stock.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
        ];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type d\'ajustement est obligatoire.',
            'type.in' => 'Le type doit être "in" ou "out".',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.min' => 'La quantité doit être supérieure à 0.',
            'reason.required' => 'La raison de l\'ajustement est obligatoire.',
        ];
    }
}

