<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketNotification extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'stock_id',
        'type',
        'message',
        'lu',
    ];

    protected function casts(): array
    {
        return [
            'lu' => 'boolean',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
