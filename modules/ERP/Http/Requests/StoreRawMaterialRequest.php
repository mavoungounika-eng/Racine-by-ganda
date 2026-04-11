<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access-erp');
    }

    /**
     * Règles de validation pour la création d'une matière première.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:erp_raw_materials,sku',
            'supplier_id' => 'nullable|exists:erp_suppliers,id',
            'unit' => 'nullable|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Messages de validation personnalisés.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la matière première est obligatoire.',
            'sku.unique' => 'Ce code SKU est déjà utilisé.',
            'supplier_id.exists' => 'Le fournisseur sélectionné n\'existe pas.',
        ];
    }
}

