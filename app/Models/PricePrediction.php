<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricePrediction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stock_id',
        'prix_recommande',
        'prix_min',
        'prix_max',
        'saison',
        'demande',
        'offre',
        'facteurs',
        'cree_le',
    ];

    protected function casts(): array
    {
        return [
            'prix_recommande' => 'float',
            'prix_min' => 'float',
            'prix_max' => 'float',
            'demande' => 'float',
            'offre' => 'float',
            'facteurs' => 'array',
            'cree_le' => 'datetime',
        ];
    }

    public function getSampleCountAttribute(): ?int
    {
        return isset($this->facteurs['echantillons']) ? (int) $this->facteurs['echantillons'] : null;
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
