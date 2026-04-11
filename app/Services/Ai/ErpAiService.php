<?php

namespace App\Services\Ai;

use Illuminate\Support\Collection;

class ErpAiService
{
  public function detectStockAnomalies(
    Collection $products): array
  {
    $cacheKey = 'ai_stock_anomalies:'
      . now()->format('YmdHi');

    $data = $products->map(fn($p) => [
      'id'        => $p->id,
      'name'      => $p->title,
      'stock'     => $p->stock,
      'threshold' => 5, // Default threshold
      'sales_7d'  => 0, // Placeholder
    ]);

    $prompt = "Analyse ce stock et détecte "
      . "les anomalies. Réponds en JSON avec : "
      . "anomalies (array), alerts (array avec "
      . "priority high/medium), forecast (7 jours).\n"
      . json_encode($data);

    $raw = app(AiService::class)->complete(
      $prompt,
      app(AiService::class)->buildSystemPrompt(
        'gestion stock ERP'),
      [
        'cache_key'  => $cacheKey,
        'cache_ttl'  => config(
          'ai.cache_ttl.stock_anomaly'),
        'feature'    => 'stock_anomaly',
        'max_tokens' => 800,
        'fallback'   => json_encode([
          'anomalies' => [],
          'alerts'    => [],
          'forecast'  => [],
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
}
