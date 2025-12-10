<?php

namespace Modules\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRawMaterialRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access-erp');
    }

    /**
     * Règles de validation pour la mise à jour d'une matière première.
     */
    public function rules(): array
    {
        $materialId = $this->route('matiere')->id ?? null;
        
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:erp_raw_materials,sku,' . $materialId,
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

