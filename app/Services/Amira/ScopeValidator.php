<?php

namespace App\Services\Amira;

class ScopeValidator
{
    /**
     * Mots-clés interdits (hors scope)
     */
    private array $forbiddenKeywords = [
        // Business advice
        'stratégie', 'business', 'vendre plus', 'augmenter ventes', 'marketing',
        'optimiser', 'améliorer performance', 'conseil business',
        
        // Internal data
        'algorithme', 'ia', 'intelligence artificielle', 'système', 'backend',
        'base de données', 'admin', 'dashboard admin',
        
        // Creator comparison
        'meilleur créateur', 'comparer créateur', 'quel créateur choisir',
        'créateur vs créateur', 'classement créateur',
    ];

    /**
     * Catégories autorisées
     */
    private array $allowedCategories = [
        'products', 'orders', 'shipping', 'support', 'returns', 'account',
    ];

    /**
     * Valider si la question est dans le scope autorisé
     */
    public function validate(string $question): array
    {
        $question = strtolower(trim($question));

        // Vérifier les mots-clés interdits
        foreach ($this->forbiddenKeywords as $keyword) {
            if (str_contains($question, strtolower($keyword))) {
                return [
                    'valid' => false,
                    'reason' => 'out_of_scope',
                    'message' => config('amira.fallback_message'),
                ];
            }
        }

        return [
            'valid' => true,
            'reason' => null,
            'message' => null,
        ];
    }
}
