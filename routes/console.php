<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CleanupPendingPosPayments;
use App\Jobs\SendAbandonedCartReminders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task 3: Global Audit Trail (Automated cleanup based on Prunable trait)
Schedule::command('trusted-devices:prune')->daily();

Schedule::command('model:prune', [
    '--model' => [\App\Models\AuditLog::class],
])->daily();

Schedule::job(new CleanupPendingPosPayments())
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('[POS Cleanup] Scheduled job failed');
    });

// Relances paniers abandonnés : toutes les heures (détecte 1h, 24h, 72h)
Schedule::job(new SendAbandonedCartReminders())
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('[AbandonedCart] Scheduled reminder job failed');
    });
