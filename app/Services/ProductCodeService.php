<?php

namespace App\Services;

use Modules\ERP\Models\ErpProductDetail;
use Illuminate\Support\Facades\DB;

/**
 * Service de génération de codes produits (SKU et code-barres)
 * 
 * Format SKU: SKU-YYYYMMDD-XXXXX (date + séquence sur 5 chiffres)
 * Format code-barres: Utilise le SKU comme code-barres interne
 */
class ProductCodeService
{
    /**
     * Génère un SKU unique au format SKU-YYYYMMDD-XXXXX
     * 
     * @return string
     */
    public function generateSku(): string
    {
        $date = now()->format('Ymd');
        $prefix = "SKU-{$date}-";
        
        // Trouver le dernier SKU du jour
        $lastSku = ErpProductDetail::where('sku', 'like', "{$prefix}%")
            ->orderBy('sku', 'desc')
            ->value('sku');
        
        if ($lastSku) {
            // Extraire le numéro séquentiel
            $sequence = (int) substr($lastSku, -5);
            $sequence++;
        } else {
            $sequence = 1;
        }
        
        // Formater avec 5 chiffres
        $sku = $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        
        // Vérifier l'unicité (au cas où)
        while (ErpProductDetail::where('sku', $sku)->exists()) {
            $sequence++;
            $sku = $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        }
        
        return $sku;
    }
    
    /**
     * Génère un code-barres interne basé sur le SKU
     * 
     * @param string $sku Le SKU du produit
     * @return string Code-barres interne
     */
    public function generateBarcode(string $sku): string
    {
        // Utilise le SKU comme code-barres interne
        // Format: SKU-YYYYMMDD-XXXXX devient CB-YYYYMMDD-XXXXX
        return str_replace('SKU-', 'CB-', $sku);
    }
    
    /**
     * Crée ou met à jour les détails ERP d'un produit avec SKU et code-barres
     * 
     * @param int $productId ID du produit
     * @param array $additionalData Données supplémentaires (cost_price, weight, etc.)
     * @return ErpProductDetail
     */
    public function createOrUpdateProductDetails(int $productId, array $additionalData = []): ErpProductDetail
    {
        $sku = $this->generateSku();
        $barcode = $this->generateBarcode($sku);
        
        return ErpProductDetail::updateOrCreate(
            ['product_id' => $productId],
            array_merge([
                'sku' => $sku,
                'barcode' => $barcode,
            ], $additionalData)
        );
    }
}

