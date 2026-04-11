<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest pour le tag d'un produit dans une conversation
 * 
 * Valide les données lors du tag d'un produit dans une conversation
 */
class TagProductRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');
        if (!$conversation) {
            return false;
        }
        
        // Vérifier que l'utilisateur est participant de la conversation
        return $conversation->participants()
            ->where('user_id', $this->user()->id)
            ->exists();
    }

    /**
     * Règles de validation pour le tag de produit.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $conversation = $this->route('conversation');
        
        return [
            'product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) use ($conversation) {
                    // Vérifier si le produit n'est pas déjà tagué
                    if ($conversation && $conversation->taggedProducts()->where('product_id', $value)->exists()) {
                        $fail('Ce produit est déjà tagué dans cette conversation.');
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:500'],
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
            'product_id.required' => 'Le produit est obligatoire.',
            'product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'note.max' => 'La note ne peut pas dépasser 500 caractères.',
        ];
    }
}

