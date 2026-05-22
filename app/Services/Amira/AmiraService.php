<?php

namespace App\Services\Amira;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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

        // 3. OpenAI si nlp_provider=openai
        if (config('amira.nlp_provider') === 'openai') {
            try {
                return [
                    'answer' => $this->askOpenAI($question, $context),
                    'source' => 'openai',
                    'validated' => true,
                ];
            } catch (\Exception $e) {
                Log::warning('Amira OpenAI error', ['message' => $e->getMessage()]);
            }
        }

        // 4. Fallback
        return [
            'answer' => config('amira.fallback_message'),
            'source' => 'fallback',
            'validated' => true,
        ];
    }

    public function isEnabled(): bool
    {
        return config('amira.enabled', false);
    }

    protected function askOpenAI(string $question, array $context = []): string
    {
        $systemPromptPath = resource_path('prompts/amira-system.md');
        $systemPrompt = file_exists($systemPromptPath)
            ? file_get_contents($systemPromptPath)
            : 'Tu es Amira, assistante virtuelle de RACINE by Ganda. Réponds uniquement aux questions sur les produits, commandes, livraisons et politiques de la boutique. Sois concise et professionnelle.';

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        if (!empty($context)) {
            $messages[] = ['role' => 'system', 'content' => 'Contexte client : ' . json_encode($context, JSON_UNESCAPED_UNICODE)];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        $result = OpenAI::chat()->create([
            'model' => config('amira.model', 'gpt-4'),
            'messages' => $messages,
            'max_tokens' => config('amira.max_tokens', 150),
            'temperature' => config('amira.temperature', 0.7),
        ]);

        return trim($result->choices[0]->message->content);
    }
}
