<?php

namespace Modules\CRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmInteraction extends Model
{
    protected $fillable = [
        'contact_id', 'user_id', 'type', 'subject', 
        'content', 'occurred_at', 'outcome'
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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
