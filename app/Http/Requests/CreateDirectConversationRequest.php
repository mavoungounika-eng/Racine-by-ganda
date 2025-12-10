<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest pour la création d'une conversation directe
 * 
 * Valide les données lors de la création d'une conversation entre deux utilisateurs
 */
class CreateDirectConversationRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles de validation pour la création de conversation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id', 'different:user_id'],
            'subject' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Préparer les données pour la validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user()->id,
        ]);
    }

    /**
     * Messages de validation personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'Le destinataire est obligatoire.',
            'recipient_id.exists' => 'Le destinataire sélectionné n\'existe pas.',
            'recipient_id.different' => 'Vous ne pouvez pas créer une conversation avec vous-même.',
            'subject.max' => 'Le sujet ne peut pas dépasser 255 caractères.',
        ];
    }
}

