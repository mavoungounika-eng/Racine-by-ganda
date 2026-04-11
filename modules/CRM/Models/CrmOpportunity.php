<?php

namespace Modules\CRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmOpportunity extends Model
{
    protected $fillable = [
        'contact_id', 'user_id', 'title', 'value', 'currency',
        'stage', 'probability', 'expected_close_date', 'notes'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close_date' => 'date',
        'probability' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
