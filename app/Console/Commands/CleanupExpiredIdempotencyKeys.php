<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupExpiredIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:prune {--days=30 : Number of days to keep keys}';

    protected $description = 'Prune expired idempotency keys older than N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $count = IdempotencyKey::where('updated_at', '<', $cutoff)->delete();

        $this->info("Pruned {$count} idempotency keys older than {$days} days.");

        return 0;
    }
}
