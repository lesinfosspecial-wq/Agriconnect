<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pickup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'latitude',
        'longitude',
        'quartier',
        'distance_km',
        'duree_minutes',
        'trace',
        'heure_collecte',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'distance_km' => 'float',
            'trace' => 'array',
            'heure_collecte' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
