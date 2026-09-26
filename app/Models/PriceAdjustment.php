<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAdjustment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stock_id',
        'seller_id',
        'ancien_prix',
        'nouveau_prix',
        'indication',
        'raison',
        'cree_le',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
