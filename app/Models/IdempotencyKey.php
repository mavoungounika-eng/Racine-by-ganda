<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $table = 'idempotency_keys';

    protected $fillable = [
        'key',
        'status',
        'response',
    ];

    protected $casts = [
        'response' => 'string',
    ];
}
