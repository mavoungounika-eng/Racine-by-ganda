<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task 3: Global Audit Trail (Automated cleanup based on Prunable trait)
Schedule::command('model:prune', [
    '--model' => [\App\Models\AuditLog::class],
])->daily();
