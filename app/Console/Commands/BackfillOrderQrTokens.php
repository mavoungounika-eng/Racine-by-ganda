<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillOrderQrTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-qr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un qr_token unique pour toutes les commandes qui n\'en ont pas encore';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ordersWithoutToken = Order::whereNull('qr_token')->get();
        $count = $ordersWithoutToken->count();

        if ($count === 0) {
            $this->info('Aucune commande à mettre à jour.');
            return self::SUCCESS;
        }

        $this->info("Mise à jour de {$count} commande(s)...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($ordersWithoutToken as $order) {
            $order->qr_token = $this->generateUniqueQrToken();
            $order->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$count} commande(s) mise(s) à jour avec succès.");

        return self::SUCCESS;
    }

    /**
     * Generate a unique QR token
     */
    protected function generateUniqueQrToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while (Order::where('qr_token', $token)->exists());

        return $token;
    }
}
