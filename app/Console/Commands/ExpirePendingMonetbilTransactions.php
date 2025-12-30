<?php

namespace App\Console\Commands;

use App\Models\PaymentTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour expirer les transactions Monetbil en attente depuis trop longtemps
 * 
 * Exécution recommandée : toutes les 30 minutes (via scheduler)
 */
class ExpirePendingMonetbilTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monetbil:expire-pending 
                            {--minutes=30 : Nombre de minutes avant expiration}
                            {--dry-run : Afficher les transactions sans les modifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire les transactions Monetbil en attente depuis plus de X minutes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');

        $this->info("Recherche des transactions pending depuis plus de {$minutes} minutes...");

        $cutoffTime = now()->subMinutes($minutes);

        $pendingTransactions = PaymentTransaction::where('provider', 'monetbil')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoffTime)
            ->get();

        if ($pendingTransactions->isEmpty()) {
            $this->info('Aucune transaction à expirer.');
            return Command::SUCCESS;
        }

        $this->info("Trouvé {$pendingTransactions->count()} transaction(s) à expirer.");

        if ($dryRun) {
            $this->warn('Mode DRY-RUN : aucune modification ne sera effectuée.');
            $this->table(
                ['ID', 'Payment Ref', 'Order ID', 'Amount', 'Created At', 'Age (minutes)'],
                $pendingTransactions->map(function ($transaction) use ($cutoffTime) {
                    return [
                        $transaction->id,
                        $transaction->payment_ref,
                        $transaction->order_id ?? 'N/A',
                        $transaction->amount . ' ' . $transaction->currency,
                        $transaction->created_at->format('Y-m-d H:i:s'),
                        $transaction->created_at->diffInMinutes(now()),
                    ];
                })
            );
            return Command::SUCCESS;
        }

        $expiredCount = 0;

        foreach ($pendingTransactions as $transaction) {
            $transaction->update([
                'status' => 'expired',
                'raw_payload' => array_merge($transaction->raw_payload ?? [], [
                    'expired_at' => now()->toIso8601String(),
                    'expired_reason' => "Pending for more than {$minutes} minutes",
                ]),
            ]);

            Log::info('Monetbil transaction expired', [
                'transaction_id' => $transaction->id,
                'payment_ref' => $transaction->payment_ref,
                'order_id' => $transaction->order_id,
                'age_minutes' => $transaction->created_at->diffInMinutes(now()),
            ]);

            $expiredCount++;
        }

        $this->info("✅ {$expiredCount} transaction(s) expirée(s) avec succès.");

        return Command::SUCCESS;
    }
}

