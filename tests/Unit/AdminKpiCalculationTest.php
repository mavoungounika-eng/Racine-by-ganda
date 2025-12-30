<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests unitaires pour les calculs de KPIs admin critiques
 * 
 * Vérifie la logique de calcul des revenus, commandes, etc.
 */
class AdminKpiCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Calcul des ventes mensuelles
     */
    public function test_monthly_sales_calculation(): void
    {
        // Créer des paiements pour le mois en cours
        Payment::factory()->create([
            'status' => 'paid',
            'amount' => 100.00,
            'created_at' => now(),
        ]);
        
        Payment::factory()->create([
            'status' => 'paid',
            'amount' => 50.00,
            'created_at' => now(),
        ]);
        
        // Paiement non payé (ne doit pas compter)
        Payment::factory()->create([
            'status' => 'pending',
            'amount' => 200.00,
            'created_at' => now(),
        ]);
        
        // Vider le cache
        Cache::flush();
        
        // Calculer les ventes mensuelles
        $monthlySales = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        $this->assertEquals(150.00, $monthlySales);
    }

    /**
     * Test : Calcul du nombre de commandes mensuelles
     */
    public function test_monthly_orders_count(): void
    {
        // Créer des commandes pour le mois en cours
        Order::factory()->count(5)->create([
            'created_at' => now(),
        ]);
        
        // Commandes du mois précédent (ne doivent pas compter)
        Order::factory()->count(3)->create([
            'created_at' => now()->subMonth(),
        ]);
        
        // Calculer le nombre de commandes mensuelles
        $monthlyOrders = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $this->assertEquals(5, $monthlyOrders);
    }

    /**
     * Test : Calcul de l'évolution des ventes
     */
    public function test_sales_evolution_calculation(): void
    {
        // Vider le cache
        Cache::flush();
        
        // Mois précédent : 100€
        Payment::factory()->create([
            'status' => 'paid',
            'amount' => 100.00,
            'created_at' => now()->subMonth(),
        ]);
        
        // Mois actuel : 150€
        Payment::factory()->create([
            'status' => 'paid',
            'amount' => 150.00,
            'created_at' => now(),
        ]);
        
        $currentMonth = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        $previousMonth = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');
        
        $evolution = round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2);
        
        // Évolution attendue : +50%
        $this->assertEquals(50.00, $evolution);
    }

    /**
     * Test : Calcul des commandes en attente
     */
    public function test_pending_orders_count(): void
    {
        // Créer des commandes avec différents statuts
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'paid']);
        Order::factory()->count(1)->create(['status' => 'delivered']);
        
        // Calculer le nombre de commandes en attente
        $pendingOrders = Order::where('status', 'pending')->count();
        
        $this->assertEquals(3, $pendingOrders);
    }
}

