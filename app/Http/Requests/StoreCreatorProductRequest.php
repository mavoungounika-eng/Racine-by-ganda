<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest pour la création de produit par un créateur
 * 
 * Valide les données lors de la création d'un produit dans le marketplace
 */
class StoreCreatorProductRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isCreator();
    }

    /**
     * Règles de validation pour la création de produit.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'min:3'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * Messages de validation personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du produit est obligatoire.',
            'title.min' => 'Le titre doit contenir au moins 3 caractères.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',
            'stock.required' => 'Le stock est obligatoire.',
            'stock.integer' => 'Le stock doit être un nombre entier.',
            'stock.min' => 'Le stock ne peut pas être négatif.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'main_image.image' => 'Le fichier doit être une image.',
            'main_image.mimes' => 'L\'image doit être au format JPG, PNG ou WEBP.',
            'main_image.max' => 'L\'image ne peut pas dépasser 4 Mo.',
        ];
    }
}

