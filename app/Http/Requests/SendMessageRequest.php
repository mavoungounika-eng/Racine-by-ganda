<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest pour l'envoi de message
 * 
 * Valide les données lors de l'envoi d'un message dans une conversation
 */
class SendMessageRequest extends FormRequest
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
     * Règles de validation pour l'envoi de message.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt', 'max:10240'], // 10MB max
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
            'content.required' => 'Le contenu du message est obligatoire.',
            'content.min' => 'Le message ne peut pas être vide.',
            'content.max' => 'Le message ne peut pas dépasser 5000 caractères.',
            'attachments.array' => 'Les pièces jointes doivent être un tableau.',
            'attachments.max' => 'Vous ne pouvez pas envoyer plus de 5 pièces jointes.',
            'attachments.*.file' => 'Chaque pièce jointe doit être un fichier valide.',
            'attachments.*.mimes' => 'Les pièces jointes doivent être des images, PDF ou documents.',
            'attachments.*.max' => 'Chaque pièce jointe ne peut pas dépasser 10 Mo.',
        ];
    }
}

