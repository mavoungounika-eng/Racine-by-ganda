<?php

namespace App\Services;

use App\Models\Order;

/**
 * Service de génération de numéros de commande
 * 
 * Format: CMD-YYYY-XXXXXX (année + séquence sur 6 chiffres)
 * Exemple: CMD-2025-001234
 */
class OrderNumberService
{
    /**
     * Génère un numéro de commande unique au format CMD-YYYY-XXXXXX
     * 
     * @return string
     */
    public function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "CMD-{$year}-";
        
        // Trouver le dernier numéro de commande de l'année
        $lastOrder = Order::where('order_number', 'like', "{$prefix}%")
            ->orderBy('order_number', 'desc')
            ->value('order_number');
        
        if ($lastOrder) {
            // Extraire le numéro séquentiel
            $sequence = (int) substr($lastOrder, -6);
            $sequence++;
        } else {
            $sequence = 1;
        }
        
        // Formater avec 6 chiffres
        $orderNumber = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        
        // Vérifier l'unicité (au cas où)
        while (Order::where('order_number', $orderNumber)->exists()) {
            $sequence++;
            $orderNumber = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        }
        
        return $orderNumber;
    }
}

