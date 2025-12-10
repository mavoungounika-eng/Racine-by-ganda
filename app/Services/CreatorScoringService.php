<?php

namespace App\Services;

use App\Models\CreatorProfile;
use App\Models\CreatorDocument;
use App\Models\CreatorValidationChecklist;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class CreatorScoringService
{
    /**
     * Calculer le score de qualité d'un créateur.
     */
    public function calculateQualityScore(CreatorProfile $creator): float
    {
        $score = 0;
        $maxScore = 0;

        // Complétude du profil (30 points)
        $maxScore += 30;
        $profileCompleteness = $this->calculateProfileCompleteness($creator);
        $score += ($profileCompleteness / 100) * 30;

        // Documents vérifiés (25 points)
        $maxScore += 25;
        $documentsScore = $this->calculateDocumentsScore($creator);
        $score += $documentsScore;

        // Checklist complétée (20 points)
        $maxScore += 20;
        $checklistScore = $this->calculateChecklistScore($creator);
        $score += $checklistScore;

        // Qualité des produits (15 points)
        $maxScore += 15;
        $productsScore = $this->calculateProductsQualityScore($creator);
        $score += $productsScore;

        // Performance des ventes (10 points)
        $maxScore += 10;
        $salesScore = $this->calculateSalesScore($creator);
        $score += $salesScore;

        return round(($score / $maxScore) * 100, 2);
    }

    /**
     * Calculer le score de complétude.
     */
    public function calculateCompletenessScore(CreatorProfile $creator): float
    {
        $completeness = $this->calculateProfileCompleteness($creator);
        $checklistPercentage = CreatorValidationChecklist::getCompletionPercentage($creator->id);
        
        return round(($completeness + $checklistPercentage) / 2, 2);
    }

    /**
     * Calculer le score de performance.
     */
    public function calculatePerformanceScore(CreatorProfile $creator): float
    {
        $score = 0;
        $maxScore = 0;

        // Nombre de produits actifs (30 points)
        $maxScore += 30;
        $productsCount = Product::where('user_id', $creator->user_id)
            ->where('is_active', true)
            ->count();
        $score += min(($productsCount / 10) * 30, 30); // Max 10 produits = 30 points

        // Ventes totales (40 points)
        $maxScore += 40;
        $totalSales = OrderItem::whereHas('product', function ($query) use ($creator) {
            $query->where('user_id', $creator->user_id);
        })
        ->whereHas('order', function ($query) {
            $query->where('status', 'paid');
        })
        ->sum(DB::raw('price * quantity'));
        
        // Échelle : 0-100k FCFA = 0-20 points, 100k-500k = 20-35 points, 500k+ = 35-40 points
        if ($totalSales >= 500000) {
            $score += 40;
        } elseif ($totalSales >= 100000) {
            $score += 20 + (($totalSales - 100000) / 400000) * 15;
        } else {
            $score += ($totalSales / 100000) * 20;
        }

        // Taux de conversion (30 points)
        $maxScore += 30;
        $conversionRate = $this->calculateConversionRate($creator);
        $score += ($conversionRate / 100) * 30;

        return round(($score / $maxScore) * 100, 2);
    }

    /**
     * Calculer le score global.
     */
    public function calculateOverallScore(CreatorProfile $creator): float
    {
        $qualityScore = $this->calculateQualityScore($creator);
        $completenessScore = $this->calculateCompletenessScore($creator);
        $performanceScore = $this->calculatePerformanceScore($creator);

        // Ponderations : Qualité 40%, Complétude 30%, Performance 30%
        $overallScore = ($qualityScore * 0.4) + ($completenessScore * 0.3) + ($performanceScore * 0.3);

        return round($overallScore, 2);
    }

    /**
     * Mettre à jour tous les scores d'un créateur.
     */
    public function updateScores(CreatorProfile $creator): void
    {
        $creator->update([
            'quality_score' => $this->calculateQualityScore($creator),
            'completeness_score' => $this->calculateCompletenessScore($creator),
            'performance_score' => $this->calculatePerformanceScore($creator),
            'overall_score' => $this->calculateOverallScore($creator),
            'last_score_calculated_at' => now(),
        ]);
    }

    /**
     * Calculer la complétude du profil.
     */
    protected function calculateProfileCompleteness(CreatorProfile $creator): float
    {
        $fields = [
            'brand_name' => 10,
            'bio' => 10,
            'location' => 10,
            'website' => 5,
            'instagram_url' => 5,
            'tiktok_url' => 5,
            'legal_status' => 10,
            'registration_number' => 10,
            'logo_path' => 15,
            'banner_path' => 10,
            'payout_method' => 10,
        ];

        $total = 0;
        $max = 0;

        foreach ($fields as $field => $weight) {
            $max += $weight;
            if (!empty($creator->$field)) {
                $total += $weight;
            }
        }

        return $max > 0 ? ($total / $max) * 100 : 0;
    }

    /**
     * Calculer le score des documents.
     */
    protected function calculateDocumentsScore(CreatorProfile $creator): float
    {
        $totalDocs = $creator->documents()->count();
        $verifiedDocs = $creator->documents()->where('is_verified', true)->count();

        if ($totalDocs === 0) {
            return 0;
        }

        $percentage = ($verifiedDocs / $totalDocs) * 100;
        return ($percentage / 100) * 25; // Max 25 points
    }

    /**
     * Calculer le score de la checklist.
     */
    protected function calculateChecklistScore(CreatorProfile $creator): float
    {
        $percentage = CreatorValidationChecklist::getRequiredCompletionPercentage($creator->id);
        return ($percentage / 100) * 20; // Max 20 points
    }

    /**
     * Calculer le score de qualité des produits.
     */
    protected function calculateProductsQualityScore(CreatorProfile $creator): float
    {
        $products = Product::where('user_id', $creator->user_id)
            ->where('is_active', true)
            ->get();

        if ($products->count() === 0) {
            return 0;
        }

        $totalScore = 0;
        foreach ($products as $product) {
            $productScore = 0;
            if (!empty($product->title)) $productScore += 2;
            if (!empty($product->description)) $productScore += 2;
            if (!empty($product->main_image)) $productScore += 2;
            if (!empty($product->price)) $productScore += 2;
            if (!empty($product->category_id)) $productScore += 2;
            $totalScore += $productScore;
        }

        $maxScore = $products->count() * 10;
        return $maxScore > 0 ? ($totalScore / $maxScore) * 15 : 0; // Max 15 points
    }

    /**
     * Calculer le score des ventes.
     */
    protected function calculateSalesScore(CreatorProfile $creator): float
    {
        $totalSales = OrderItem::whereHas('product', function ($query) use ($creator) {
            $query->where('user_id', $creator->user_id);
        })
        ->whereHas('order', function ($query) {
            $query->where('status', 'paid');
        })
        ->sum(DB::raw('price * quantity'));

        // Échelle simplifiée : 0-50k = 0-5 points, 50k-200k = 5-8 points, 200k+ = 8-10 points
        if ($totalSales >= 200000) {
            return 10;
        } elseif ($totalSales >= 50000) {
            return 5 + (($totalSales - 50000) / 150000) * 3;
        } else {
            return ($totalSales / 50000) * 5;
        }
    }

    /**
     * Calculer le taux de conversion.
     */
    protected function calculateConversionRate(CreatorProfile $creator): float
    {
        $products = Product::where('user_id', $creator->user_id)
            ->where('is_active', true)
            ->count();

        if ($products === 0) {
            return 0;
        }

        $orders = OrderItem::whereHas('product', function ($query) use ($creator) {
            $query->where('user_id', $creator->user_id);
        })
        ->distinct('order_id')
        ->count('order_id');

        // Taux de conversion = (commandes / produits) * 100, max 100%
        $rate = ($orders / $products) * 100;
        return min($rate, 100);
    }
}

