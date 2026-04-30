<?php

namespace App\Services\Amira;

class AmiraService
{
    protected AmiraKnowledgeBase $knowledgeBase;
    protected ScopeValidator $scopeValidator;
    protected ToneValidator $toneValidator;

    public function __construct(
        AmiraKnowledgeBase $knowledgeBase,
        ScopeValidator $scopeValidator,
        ToneValidator $toneValidator
    ) {
        $this->knowledgeBase = $knowledgeBase;
        $this->scopeValidator = $scopeValidator;
        $this->toneValidator = $toneValidator;
    }

    /**
     * Traiter une question client
     */
    public function ask(string $question, array $context = []): array
    {
        // 1. Valider le scope
        $scopeValidation = $this->scopeValidator->validate($question);
        if (!$scopeValidation['valid']) {
            return [
                'answer' => $scopeValidation['message'],
                'source' => 'fallback',
                'validated' => false,
            ];
        }

        // 2. Chercher dans la base de connaissances
        $knowledgeResult = $this->knowledgeBase->search($question);
        
        if ($knowledgeResult) {
            // Valider le ton de la réponse
            $toneValidation = $this->toneValidator->validate($knowledgeResult['answer']);
            
            if ($toneValidation['valid']) {
                return [
                    'answer' => $knowledgeResult['answer'],
                    'source' => 'knowledge_base',
                    'category' => $knowledgeResult['category'],
                    'validated' => true,
                ];
            }
        }

        // 3. Si pas de réponse dans la KB, utiliser le fallback
        return [
            'answer' => config('amira.fallback_message'),
            'source' => 'fallback',
            'validated' => true,
        ];
    }

    /**
     * Vérifier si Amira est activée
     */
    public function isEnabled(): bool
    {
        return config('amira.enabled', false);
    }
}
