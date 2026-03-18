<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CleanupPendingPosPayments;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task 3: Global Audit Trail (Automated cleanup based on Prunable trait)
Schedule::command('model:prune', [
    '--model' => [\App\Models\AuditLog::class],
])->daily();

Schedule::job(new CleanupPendingPosPayments())
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('[POS Cleanup] Scheduled job failed');
    });
