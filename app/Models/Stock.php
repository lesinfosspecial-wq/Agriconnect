<?php

namespace App\Models;

use App\Services\PricePredictor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stock extends Model
{
    public const UPDATED_AT = null;

    public const PUBLIE = 'publie';

    protected $fillable = [
        'product_id',
        'seller_id',
        'quantite',
        'quantite_disponible',
        'quantite_bloquee',
        'unite',
        'prix_souhaite',
        'prix_vendeur',
        'prix_minimum',
        'latitude',
        'longitude',
        'quartier',
        'ville',
        'recolte_le',
        'expiration_estimee',
        'fraicheur',
        'saison',
        'mois',
        'urgence',
        'photo_url',
        'mode_collecte',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'float',
            'quantite_disponible' => 'float',
            'quantite_bloquee' => 'float',
            'prix_souhaite' => 'float',
            'prix_vendeur' => 'float',
            'prix_minimum' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'recolte_le' => 'datetime',
            'expiration_estimee' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(PricePrediction::class);
    }

    public function dernierePrediction(): HasOne
    {
        return $this->hasOne(PricePrediction::class)->latestOfMany('cree_le');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PriceAdjustment::class);
    }

    public function getQuantityAttribute(): float
    {
        return (float) $this->quantite;
    }

    public function getUnitAttribute(): string
    {
        return (string) $this->unite;
    }

    public function getSellerPriceAttribute(): ?float
    {
        return $this->prix_vendeur ?? $this->prix_souhaite;
    }

    public function getMinPriceAttribute(): ?float
    {
        return $this->prix_minimum;
    }

    public function getFreshnessAttribute(): ?int
    {
        return $this->fraicheur === null ? null : (int) $this->fraicheur;
    }

    public function getUrgencyAttribute(): string
    {
        return (string) $this->urgence;
    }

    public function getStatusAttribute(): string
    {
        return (string) $this->statut;
    }

    public function getSeasonAttribute(): ?string
    {
        return $this->saison;
    }

    public function getPickupModeAttribute(): string
    {
        return (string) $this->mode_collecte;
    }

    public function getHarvestedOnAttribute()
    {
        return $this->recolte_le;
    }

    public function getExpiresOnAttribute()
    {
        return $this->expiration_estimee;
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->photo_url;
    }

    public function getAiPriceAttribute(): ?float
    {
        return $this->dernierePrediction?->prix_recommande;
    }

    public function getAiMinAttribute(): ?float
    {
        return $this->dernierePrediction?->prix_min;
    }

    public function getAiMaxAttribute(): ?float
    {
        return $this->dernierePrediction?->prix_max;
    }

    public function getFactorsAttribute(): ?array
    {
        $facteurs = $this->dernierePrediction?->facteurs;

        return $facteurs['impacts'] ?? null;
    }

    public function getVisionAttribute(): ?array
    {
        $vision = $this->dernierePrediction?->facteurs['vision'] ?? null;

        return is_array($vision) ? $vision : null;
    }

    public function availableQuantity(): float
    {
        return max(0, (float) $this->quantite_disponible);
    }

    public function pertinence(): ?array
    {
        if ($this->ai_min === null || $this->ai_max === null || $this->seller_price === null) {
            return null;
        }

        $price = (float) $this->seller_price;
        $min = (float) $this->ai_min;
        $max = (float) $this->ai_max;
        $marge = max(80, ((float) $this->ai_price) * 0.18);

        if ($price < $min) {
            return ['label' => 'Prix inférieur à l\'estimation', 'tone' => 'low', 'code' => 'inferieur'];
        }

        if ($price <= $max) {
            return ['label' => 'Prix cohérent avec l\'estimation', 'tone' => 'ok', 'code' => 'dans_la_fourchette'];
        }

        if ($price <= $max + $marge) {
            return ['label' => 'Prix relativement élevé', 'tone' => 'warn', 'code' => 'legerement_superieur'];
        }

        return ['label' => 'Prix fortement éloigné des données disponibles', 'tone' => 'bad', 'code' => 'fortement_superieur'];
    }

    public function joursRestants(): int
    {
        return max(0, (int) floor(($this->expiration_estimee->copy()->startOfDay()->timestamp - now()->startOfDay()->timestamp) / 86400));
    }

    public function appliquerPrediction(array $prediction): void
    {
        $this->fill([
            'saison' => $prediction['season'],
            'mois' => (int) now()->month,
            'urgence' => $prediction['urgency'],
        ])->save();

        $this->predictions()->create([
            'prix_recommande' => $prediction['recommended'],
            'prix_min' => $prediction['min'],
            'prix_max' => $prediction['max'],
            'saison' => $prediction['season'],
            'demande' => $prediction['demand'],
            'offre' => $prediction['supply'],
            'facteurs' => [
                'echantillons' => $prediction['sample_count'],
                'impacts' => $prediction['factors'],
                'vision' => $prediction['vision'] ?? null,
            ],
            'cree_le' => now(),
        ]);

        $this->unsetRelation('dernierePrediction');
    }

    public function recalculer(PricePredictor $predictor): array
    {
        $prediction = $predictor->estimate($this->product, [
            'days_left' => $this->joursRestants(),
            'freshness' => (int) $this->fraicheur,
            'quantity' => (float) $this->quantite,
        ]);

        $this->appliquerPrediction($prediction);

        return $prediction;
    }

    public function synchroniserDisponibilite(): void
    {
        if ($this->statut === 'suspendu') {
            return;
        }

        $libre = (float) $this->quantite_disponible;
        $bloque = (float) $this->quantite_bloquee;

        $this->statut = match (true) {
            $libre <= 0 && $bloque <= 0 => 'vendu',
            $libre <= 0 => 'reserve',
            $bloque > 0 => 'partiellement_reserve',
            default => self::PUBLIE,
        };
        $this->save();
    }

    public function visiblePar(User $user): bool
    {
        if ($user->isAdmin() || ($user->isVendeur() && $this->seller_id === $user->id)) {
            return true;
        }

        return in_array($this->statut, [self::PUBLIE, 'partiellement_reserve', 'reserve'], true);
    }
}
