<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access-erp');
    }

    /**
     * Règles de validation pour la création d'un achat.
     */
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:erp_suppliers,id',
            'purchase_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:purchase_date',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:erp_raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Le fournisseur est obligatoire.',
            'supplier_id.exists' => 'Le fournisseur sélectionné n\'existe pas.',
            'purchase_date.required' => 'La date d\'achat est obligatoire.',
            'items.required' => 'Au moins un article est requis.',
            'items.min' => 'Au moins un article est requis.',
            'items.*.material_id.required' => 'Chaque article doit avoir une matière première.',
            'items.*.quantity.required' => 'La quantité est obligatoire pour chaque article.',
            'items.*.quantity.min' => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.required' => 'Le prix unitaire est obligatoire pour chaque article.',
        ];
    }
}

