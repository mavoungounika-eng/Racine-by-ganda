<?php

namespace App\Services\Ai;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiService
{
  public function complete(
    string $prompt,
    string $systemPrompt = '',
    array $options = []
  ): string {
    // Vérifier cache si cache_key fourni
    if (!empty($options['cache_key'])) {
      $ttl = $options['cache_ttl'] ?? 3600;
      return Cache::remember(
        $options['cache_key'], $ttl,
        fn() => $this->callOpenAi(
          $prompt, $systemPrompt, $options));
    }
    return $this->callOpenAi(
      $prompt, $systemPrompt, $options);
  }

  private function callOpenAi(
    string $prompt,
    string $systemPrompt,
    array $options
  ): string {
    try {
      $messages = [];
      if ($systemPrompt) {
        $messages[] = [
          'role'    => 'system',
          'content' => $systemPrompt,
        ];
      }
      $messages[] = [
        'role'    => 'user',
        'content' => $prompt,
      ];

      $response = OpenAI::chat()->create([
        'model'       => config('ai.model', 'gpt-4o'),
        'messages'    => $messages,
        'max_tokens'  => $options['max_tokens'] ?? 1000,
        'temperature' => $options['temperature'] ?? 0.7,
      ]);

      $content = $response->choices[0]->message->content;

      // Logger l'usage
      $this->logUsage(
        $options['feature'] ?? 'general',
        $options['user_id'] ?? null,
        $response->usage->promptTokens,
        $response->usage->completionTokens,
        $options['reference_type'] ?? null,
        $options['reference_id'] ?? null,
      );

      return $content;

    } catch (\Throwable $e) {
      Log::error('OpenAI API error', [
        'message' => $e->getMessage(),
        'feature' => $options['feature'] ?? 'unknown',
      ]);
      // Fallback gracieux
      return $options['fallback']
        ?? 'Service IA temporairement indisponible.';
    }
  }

  public function checkQuota(
    User $user, string $feature): bool
  {
    $quotaKey = $feature . '_per_day';
    $limit = config("ai.quotas.{$quotaKey}", 10);

    $count = AiUsageLog::where('user_id', $user->id)
      ->where('feature', $feature)
      ->whereDate('created_at', today())
      ->count();

    return $count < $limit;
  }

  public function estimateCost(
    int $inputTokens, int $outputTokens): float
  {
    $costs = config('ai.costs.gpt-4o');
    return ($inputTokens / 1000 * $costs['input_per_1k'])
         + ($outputTokens / 1000 * $costs['output_per_1k']);
  }

  private function logUsage(
    string $feature,
    ?int $userId,
    int $promptTokens,
    int $completionTokens,
    ?string $refType = null,
    ?int $refId = null
  ): void {
    try {
      AiUsageLog::create([
        'user_id'           => $userId,
        'feature'           => $feature,
        'model'             => config('ai.model'),
        'prompt_tokens'     => $promptTokens,
        'completion_tokens' => $completionTokens,
        'total_tokens'      => $promptTokens
                              + $completionTokens,
        'cost_usd'          => $this->estimateCost(
          $promptTokens, $completionTokens),
        'response_cached'   => false,
        'reference_type'    => $refType,
        'reference_id'      => $refId,
      ]);
    } catch (\Throwable $e) {
      Log::warning('AI usage log failed', [
        'error' => $e->getMessage()
      ]);
    }
  }

  public function buildSystemPrompt(
    string $context): string
  {
    $brand = config('ai.prompts.brand');
    $tone  = config('ai.prompts.tone');
    return "Tu es l'assistant IA de {$brand}. "
         . "Ton : {$tone}. "
         . "Réponds toujours en français. "
         . "Contexte : {$context}";
  }
}
