<?php

namespace App\Services\Ai;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProductAiService
{
  public function generateDescription(
    Product $product): string
  {
    if (!app(AiService::class)->checkQuota(
      $product->creator, 'description')) {
      throw new \Exception(
        'Quota journalier de descriptions atteint.');
    }

    $cacheKey = "ai_desc:{$product->id}";
    $ttl = config('ai.cache_ttl.description');

    $prompt = "Génère une description produit "
      . "attrayante de 150-200 mots en français pour :\n"
      . "Produit : {$product->title}\n"
      . "Catégorie : {$product->category?->name}\n"
      . "Prix : " . number_format($product->price, 0,
        ',', ' ') . " FCFA\n"
      . "Créateur : {$product->creator?->name}\n"
      . "Met en valeur l'authenticité africaine "
      . "et la qualité artisanale.";

    $system = app(AiService::class)
      ->buildSystemPrompt('e-commerce africain');

    $description = app(AiService::class)->complete(
      $prompt, $system, [
        'cache_key'      => $cacheKey,
        'cache_ttl'      => $ttl,
        'feature'        => 'description',
        'user_id'        => $product->user_id,
        'reference_type' => 'product',
        'reference_id'   => $product->id,
        'max_tokens'     => 400,
        'fallback'       => $product->description
          ?? 'Description en cours de génération...',
      ]
    );

    $product->update([
      'ai_description'     => $description,
      'ai_last_analyzed_at'=> now(),
    ]);

    return $description;
  }

  public function suggestPrice(
    Product $product): array
  {
    $cacheKey = "ai_price:{$product->id}:"
      . now()->format('Ymd');

    $prompt = "Analyse ce produit et suggère "
      . "un prix optimal en FCFA :\n"
      . "Nom : {$product->title}\n"
      . "Prix actuel : {$product->price} FCFA\n"
      . "Catégorie : {$product->category?->name}\n"
      . "Ventes totales : " . $product->orderItems()->count() . "\n"
      . "Réponds en JSON avec les clés : "
      . "suggested_price, reasoning, confidence "
      . "(high/medium/low), range (min/max).";

    $system = app(AiService::class)
      ->buildSystemPrompt('pricing e-commerce');

    $raw = app(AiService::class)->complete(
      $prompt, $system, [
        'cache_key'  => $cacheKey,
        'cache_ttl'  => config(
          'ai.cache_ttl.price_suggestion'),
        'feature'    => 'price_suggestion',
        'user_id'    => $product->user_id,
        'max_tokens' => 300,
        'fallback'   => json_encode([
          'suggested_price' => $product->price,
          'reasoning'       => 'Indisponible',
          'confidence'      => 'low',
          'range'           => [
            'min' => $product->price * 0.9,
            'max' => $product->price * 1.1,
          ],
        ]),
      ]
    );

    try {
      $clean = preg_replace(
        '/```json|```/', '', $raw);
      return json_decode(trim($clean), true)
        ?? ['suggested_price' => $product->price];
    } catch (\Throwable $e) {
      return ['suggested_price' => $product->price];
    }
  }

  public function analyzeSales(
    User $creator, array $salesData): array
  {
    $cacheKey = "ai_sales:{$creator->id}:"
      . now()->format('Ymd');

    $salesJson = json_encode($salesData);
    $prompt = "Analyse ces données de ventes "
      . "et donne des recommandations :\n"
      . "{$salesJson}\n"
      . "Réponds en JSON avec : summary, "
      . "top_products, recommendations (array), "
      . "trend (up/down/stable), next_actions.";

    $system = app(AiService::class)
      ->buildSystemPrompt('analyse ventes créateur');

    $raw = app(AiService::class)->complete(
      $prompt, $system, [
        'cache_key'  => $cacheKey,
        'cache_ttl'  => config('ai.cache_ttl.analysis'),
        'feature'    => 'sales_analysis',
        'user_id'    => $creator->id,
        'max_tokens' => 800,
        'fallback'   => json_encode([
          'summary'         => 'Analyse indisponible.',
          'recommendations' => [],
          'trend'           => 'stable',
          'next_actions'    => [],
        ]),
      ]
    );

    try {
      $clean = preg_replace(
        '/```json|```/', '', $raw);
      return json_decode(trim($clean), true) ?? [];
    } catch (\Throwable $e) {
      return [];
    }
  }
}
