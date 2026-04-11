<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardVerificationSeeder extends Seeder
{
    public function run()
    {
        // 0. Clean up previous test data (optional, be careful)
        // DB::table('orders')->truncate();

        $this->command->info('🌱 Démarrage de la génération de données pour le Dashboard Admin...');

        // 1. GLOBAL STATE SCENARIOS
        $this->createGlobalStateData();

        // 2. ALERTS SCENARIOS
        $this->createAlertsData();

        // 3. MARKETPLACE DATA
        $this->createMarketplaceData();

        // 4. OPERATIONS DATA
        $this->createOperationsData();

        // 5. TRENDS DATA (7 Days)
        $this->createTrendsData();

        $this->command->info('✅ Données Dashboard générées avec succès !');
    }

    private function createGlobalStateData()
    {
        // Aujourd'hui: Bon CA pour avoir du vert
        Order::factory(5)->create([
            'created_at' => Carbon::today(),
            'total_amount' => 50000,
            'status' => 'completed',
        ])->each(function($order) {
            $this->createOrderItems($order);
        });

        // Hier: CA moyen pour avoir une variation positive
        Order::factory(3)->create([
            'created_at' => Carbon::yesterday(),
            'total_amount' => 40000,
            'status' => 'completed',
        ])->each(function($order) {
            $this->createOrderItems($order);
        });

        // Pending orders pour le KPI "En attente"
        Order::factory(8)->create([
            'created_at' => Carbon::today(),
            'status' => 'pending',
        ]);
        
        $this->command->info(' - Global State: Commandes aujourd\'hui/hier créées.');
    }

    private function createAlertsData()
    {
        // 1. Commandes en retard (> 5 jours et toujours processing)
        Order::factory(3)->create([
            'created_at' => Carbon::now()->subDays(6),
            'status' => 'processing',
            'expected_delivery_date' => Carbon::now()->subDay(),
        ]);

        // 2. Produits Stock Critique (< 5)
        Product::factory(4)->create([
            'stock' => 2,
            'title' => 'Produit Critique Test ' . rand(1, 100),
        ]);

        // 3. Paiements échoués (si table payments existe, sinon via statut commande)
        // Simulation via statut de paiement 'failed' sur commande
        Order::factory(5)->create([
            'created_at' => Carbon::today(),
            'status' => 'cancelled',
            'payment_status' => 'failed',
        ]);
        
        $this->command->info(' - Alertes: Retards, Stock critique, Paiements échoués créés.');
    }

    private function createMarketplaceData()
    {
        // Créer un vendeur
        $creators = User::factory(3)->create(['role' => 'createur']); // Assurer que le role slug est bon

        foreach ($creators as $creator) {
            // Produits du créateur
            $products = Product::factory(2)->create([
                'user_id' => $creator->id,
            ]);

            // Commandes incluant ces produits
            foreach ($products as $product) {
               $order = Order::factory()->create([
                   'created_at' => Carbon::today(),
                   'status' => 'completed',
                   'total_amount' => $product->price * 2
               ]);
               
               OrderItem::create([
                   'order_id' => $order->id,
                   'product_id' => $product->id,
                   'quantity' => 2,
                   'price' => $product->price,
               ]);
            }
        }
        
        $this->command->info(' - Marketplace: Vendeurs, produits et ventes créés.');
    }

    private function createOperationsData()
    {
        // À préparer (Confirmed mais prepare_at null)
        Order::factory(12)->create([
            'status' => 'confirmed',
            'prepared_at' => null,
        ]);

        // Prêt non expédié (Prepared mais shipped_at null > 24h)
        Order::factory(5)->create([
            'status' => 'prepared',
            'prepared_at' => Carbon::yesterday(),
            'shipped_at' => null,
        ]);
        
        $this->command->info(' - Opérations: Commandes à préparer et prêtes créées.');
    }

    private function createTrendsData()
    {
        // Générer des ventes sur les 7 derniers jours
        for ($i = 0; $i < 7; $i++) {
            Order::factory(rand(2, 5))->create([
                'created_at' => Carbon::today()->subDays($i),
                'status' => 'completed',
                'total_amount' => rand(20000, 100000),
            ])->each(function($order) {
                $this->createOrderItems($order);
            });
        }
        
        $this->command->info(' - Tendances: Historique 7 jours généré.');
    }

    private function createOrderItems($order)
    {
        // Créer des produits simples si besoin
        $product = Product::first() ?? Product::factory()->create();
        
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => rand(1, 3),
            'price' => $order->total_amount / rand(1, 3), // Simplification
        ]);
    }
}
