<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ErpStock extends Model
{
    protected $fillable = [
        'stockable_type', 'stockable_id', 'location', 
        'quantity', 'shelf_location'
    ];

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }
}
