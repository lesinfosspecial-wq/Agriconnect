<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'month',
        'freshness',
        'days_left',
        'urgency',
        'demand',
        'supply',
        'quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'demand' => 'float',
            'supply' => 'float',
            'quantity' => 'float',
            'price' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
