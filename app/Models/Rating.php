<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'auteur_id',
        'cible_id',
        'order_id',
        'note',
        'commentaire',
    ];

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function cible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cible_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
