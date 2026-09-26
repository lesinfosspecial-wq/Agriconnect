<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const UPDATED_AT = null;

    public const RESERVEE = 'reservee';

    public const EN_PREPARATION = 'en_preparation';

    public const EN_COLLECTE = 'en_collecte';

    public const COLLECTEE = 'collectee';

    public const TERMINEE = 'terminee';

    public const ANNULEE = 'annulee';

    protected $fillable = [
        'buyer_id',
        'stock_id',
        'quantite',
        'prix_unitaire',
        'statut',
        'canal',
        'nom_client',
        'reservee_jusqu_au',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'float',
            'prix_unitaire' => 'float',
            'reservee_jusqu_au' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function pickup(): HasOne
    {
        return $this->hasOne(Pickup::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function getQuantityAttribute(): float
    {
        return (float) $this->quantite;
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) $this->prix_unitaire;
    }

    public function getClientAttribute(): string
    {
        if ($this->canal === 'sur_place') {
            return (string) $this->nom_client;
        }

        return (string) ($this->buyer?->nom ?? 'Acheteur');
    }

    public function getStatusAttribute(): string
    {
        return (string) $this->statut;
    }

    public function getMeetupPointAttribute(): string
    {
        $pickup = $this->pickup;

        return $pickup ? 'Retrait à '.$pickup->quartier : 'Lieu de retrait à préciser';
    }

    public function getDistanceKmAttribute(): ?float
    {
        return $this->pickup?->distance_km;
    }

    public function getPickupAtAttribute()
    {
        return $this->pickup?->heure_collecte ?? $this->reservee_jusqu_au;
    }

    public function peutEtreNotee(): bool
    {
        return in_array($this->statut, [self::COLLECTEE, self::TERMINEE], true) && $this->rating === null;
    }

    public function suivant(): ?string
    {
        return match ($this->statut) {
            self::RESERVEE => self::EN_PREPARATION,
            self::EN_PREPARATION => self::EN_COLLECTE,
            self::EN_COLLECTE => self::COLLECTEE,
            self::COLLECTEE => self::TERMINEE,
            default => null,
        };
    }
}
