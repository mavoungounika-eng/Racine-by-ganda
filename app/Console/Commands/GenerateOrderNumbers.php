<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderNumberService;
use Illuminate\Console\Command;

class GenerateOrderNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:generate-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des numéros de commande formatés pour toutes les commandes qui n\'en ont pas encore';

    /**
     * Execute the console command.
     */
    public function handle(OrderNumberService $orderNumberService): int
    {
        $ordersWithoutNumber = Order::whereNull('order_number')->get();
        $count = $ordersWithoutNumber->count();

        if ($count === 0) {
            $this->info('Aucune commande à mettre à jour.');
            return self::SUCCESS;
        }

        $this->info("Génération de numéros pour {$count} commande(s)...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($ordersWithoutNumber as $order) {
            try {
                $order->order_number = $orderNumberService->generateOrderNumber();
                $order->save();
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Erreur pour la commande #{$order->id}: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$count} commande(s) mise(s) à jour avec succès.");

        return self::SUCCESS;
    }
}
