<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'created_by',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_tag_members', 'tag_id', 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
