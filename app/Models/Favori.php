<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Favori extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'stock_id',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public static function idsPour(int $userId): Collection
    {
        return static::query()->where('user_id', $userId)->pluck('stock_id');
    }
}
