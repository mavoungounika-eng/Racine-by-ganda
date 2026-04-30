<?php

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Collection;

class CrmAiService
{
  public function analyzeCustomerBehavior(
    User $customer): array
  {
    $cacheKey = "ai_crm:{$customer->id}:"
      . now()->format('YmdH');

    $orders = $customer->orders()
      ->latest()->limit(10)
      ->get(['total_amount', 'status', 'created_at']);

    $prompt = "Analyse ce profil client et "
      . "retourne un JSON avec : profile_summary, "
      . "churn_risk (low/medium/high), "
      . "next_purchase (prédiction), "
      . "recommendations (array).\n"
      . "Données : " . json_encode([
        'orders_count'   => $customer->orders()->count(),
        'total_spent'    => $customer->orders()
          ->where('status', 'paid')
          ->sum('total_amount'),
        'last_orders'    => $orders,
        'loyalty_points' => 0, // Fallback if loyalty service not available
      ]);

    $raw = app(AiService::class)->complete(
      $prompt,
      app(AiService::class)->buildSystemPrompt(
        'CRM analyse client'),
      [
        'cache_key'  => $cacheKey,
        'cache_ttl'  => config('ai.cache_ttl.crm_insight'),
        'feature'    => 'crm_insight',
        'user_id'    => null,
        'max_tokens' => 500,
        'fallback'   => json_encode([
          'profile_summary'  => 'Analyse indisponible.',
          'churn_risk'       => 'medium',
          'next_purchase'    => 'Inconnue',
          'recommendations'  => [],
        ]),
      ]
    );

    try {
      $clean = preg_replace('/```json|```/', '', $raw);
      return json_decode(trim($clean), true) ?? [];
    } catch (\Throwable $e) {
      return [];
    }
  }

  public function generateCrmInsight(
    array $metrics): string
  {
    $cacheKey = 'ai_crm_insight:'
      . now()->format('YmdH');

    $prompt = "Génère un résumé exécutif de "
      . "2-3 phrases sur ces métriques CRM :\n"
      . json_encode($metrics);

    return app(AiService::class)->complete(
      $prompt,
      app(AiService::class)->buildSystemPrompt(
        'analytics CRM admin'),
      [
        'cache_key'  => $cacheKey,
        'cache_ttl'  => config('ai.cache_ttl.crm_insight'),
        'feature'    => 'crm_insight',
        'max_tokens' => 200,
        'fallback'   => 'Résumé CRM indisponible.',
      ]
    );
  }

  public function suggestSegments(
    Collection $customers): array
  {
    $sample = $customers->take(50)->map(fn($c) => [
      'orders'  => $c->orders()->count(),
      'spent'   => $c->orders()->sum('total_amount'),
      'channel' => 'web',
    ]);

    $prompt = "Analyse ces données clients et "
      . "suggère 3-5 nouveaux segments "
      . "avec leurs règles. Réponds en JSON "
      . "avec un array de segments, chacun ayant : "
      . "name, description, rules (conditions).\n"
      . json_encode($sample);

    $raw = app(AiService::class)->complete(
      $prompt,
      app(AiService::class)->buildSystemPrompt(
        'segmentation CRM'),
      [
        'feature'    => 'segmentation',
        'max_tokens' => 800,
        'fallback'   => json_encode([]),
      ]
    );

    try {
      $clean = preg_replace('/```json|```/', '', $raw);
      return json_decode(trim($clean), true) ?? [];
    } catch (\Throwable $e) {
      return [];
    }
  }
}
