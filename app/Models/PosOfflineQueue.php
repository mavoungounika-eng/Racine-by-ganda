<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOfflineQueue extends Model
{
    protected $table = 'pos_offline_queue';

    protected $fillable = [
        'machine_id',
        'sale_data',
        'status',
        'queued_at',
        'synced_at',
        'attempts',
        'error_message',
    ];

    protected $casts = [
        'sale_data' => 'array',
        'queued_at' => 'datetime',
        'synced_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
