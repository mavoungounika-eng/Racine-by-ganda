<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;

class CreatorChatService
{
  public function chat(
    User $creator,
    string $message,
    ?int $conversationId = null
  ): array {
    if (!app(AiService::class)->checkQuota(
      $creator, 'chat')) {
      return [
        'message' => 'Quota journalier atteint. '
          . 'Revenez demain.',
        'conversation_id' => $conversationId,
        'tokens_used' => 0,
      ];
    }

    // Charger ou créer la conversation
    $conversation = $conversationId
      ? AiConversation::where('user_id', $creator->id)
          ->findOrFail($conversationId)
      : AiConversation::create([
          'user_id' => $creator->id,
          'context' => 'general',
          'messages'=> [],
        ]);

    // Contexte créateur
    $context = "Créateur : {$creator->name}. "
      . "Boutique sur RACINE BY GANDA. "
      . "Aide-le à développer ses ventes.";

    $system = app(AiService::class)
      ->buildSystemPrompt($context);

    // Construire historique (max 10 derniers messages)
    $history = collect($conversation->messages)
      ->slice(-10)
      ->values()
      ->map(fn($m) => [
        'role'    => $m['role'],
        'content' => $m['content'],
      ])->toArray();

    $history[] = [
      'role'    => 'user',
      'content' => $message,
    ];

    // Appel OpenAI avec historique
    try {
      $response = OpenAI::chat()->create([
        'model'    => config('ai.model', 'gpt-4o'),
        'messages' => array_merge(
          [['role' => 'system', 'content' => $system]],
          $history
        ),
        'max_tokens' => 500,
      ]);

      $aiMessage = $response->choices[0]
        ->message->content;
      $tokens = $response->usage->totalTokens;

      // Sauvegarder messages
      $conversation->addMessage('user', $message);
      $conversation->addMessage('assistant', $aiMessage);
      $conversation->increment('tokens_used', $tokens);

      return [
        'message'         => $aiMessage,
        'conversation_id' => $conversation->id,
        'tokens_used'     => $tokens,
      ];

    } catch (\Throwable $e) {
      return [
        'message' => 'Désolé, je suis temporairement '
          . 'indisponible. Réessayez dans quelques '
          . 'instants.',
        'conversation_id' => $conversation->id,
        'tokens_used'     => 0,
      ];
    }
  }
}
