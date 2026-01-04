<?php

namespace App\Services\Amira;

class ToneValidator
{
    /**
     * Mots/expressions interdits (ton inapproprié)
     */
    private array $forbiddenPhrases = [
        // Familiarité
        'salut', 'coucou', 'hey', 'yo', 'mon pote', 'mon ami',
        
        // Enthousiasme artificiel
        'super !', 'génial !', 'incroyable !', 'fantastique !',
        '!!!', '😊', '🎉', '✨',
        
        // Mentions IA/système
        'algorithme', 'ia', 'intelligence artificielle', 'système automatique',
        'machine learning', 'neural', 'bot', 'robot',
        
        // Trop commercial
        'offre exceptionnelle', 'ne ratez pas', 'dernière chance',
    ];

    /**
     * Mots/expressions requis (ton professionnel)
     */
    private array $professionalIndicators = [
        'vous', 'votre', 'nous', 'notre', 'pouvez', 'disponible',
    ];

    /**
     * Valider le ton de la réponse
     */
    public function validate(string $response): array
    {
        $response = strtolower(trim($response));

        // Vérifier les phrases interdites
        foreach ($this->forbiddenPhrases as $phrase) {
            if (str_contains($response, strtolower($phrase))) {
                return [
                    'valid' => false,
                    'reason' => 'inappropriate_tone',
                    'issue' => "Phrase interdite détectée : {$phrase}",
                ];
            }
        }

        // Vérifier la longueur (pas trop long, pas trop court)
        $wordCount = str_word_count($response);
        if ($wordCount < 5) {
            return [
                'valid' => false,
                'reason' => 'too_short',
                'issue' => 'Réponse trop courte',
            ];
        }

        if ($wordCount > 100) {
            return [
                'valid' => false,
                'reason' => 'too_long',
                'issue' => 'Réponse trop longue',
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
            'issue' => null,
        ];
    }
}
