<?php

namespace App\Services\Ai;

use App\Models\Order;
use App\Models\PosSale;
use App\Models\User;

class AdminAiService
{
  public function generateDailySummary(): string
  {
    $cacheKey = 'ai_admin_summary:'
      . now()->format('Ymd');

    $data = [
      'sales_today'    => Order::whereDate('created_at', today())
        ->where('status', 'paid')
        ->sum('total_amount'),
      'orders_today'   => Order::whereDate('created_at', today())->count(),
      'new_customers'  => User::whereDate('created_at', today())->count(),
      'pos_sales'      => PosSale::whereDate('created_at', today())
        ->where('status', 'finalized')
        ->sum('total_amount'),
    ];

    $prompt = "Génère un résumé exécutif "
      . "du jour en 3-4 phrases pour le "
      . "dashboard admin de RACINE BY GANDA :\n"
      . json_encode($data);

    return app(AiService::class)->complete(
      $prompt,
      app(AiService::class)->buildSystemPrompt(
        'dashboard admin e-commerce'),
      [
        'cache_key'  => $cacheKey,
        'cache_ttl'  => config(
          'ai.cache_ttl.admin_summary'),
        'feature'    => 'admin_summary',
        'max_tokens' => 300,
        'fallback'   => 'Résumé quotidien indisponible.',
      ]
    );
  }
}
