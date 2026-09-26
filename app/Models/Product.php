<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'nom',
        'categorie',
        'unite',
        'conservation_heures_reference',
    ];

    public function getNameAttribute(): string
    {
        return (string) $this->nom;
    }

    public function getUnitAttribute(): string
    {
        return (string) $this->unite;
    }

    public function getCategoryAttribute(): string
    {
        return (string) $this->categorie;
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->nom);
    }

    public function getEmojiAttribute(): string
    {
        return match ($this->slug) {
            'tomate' => '🍅',
            'banane' => '🍌',
            'ananas' => '🍍',
            'piment' => '🌶️',
            'mangue' => '🥭',
            'oignon' => '🧅',
            default => '🥬',
        };
    }

    public function visuel(): string
    {
        $fichier = match ($this->slug) {
            'tomate' => 'produit-tomates.jpg',
            'banane' => 'produit-bananes.jpg',
            'ananas' => 'produit-ananas.jpg',
            'piment' => 'produit-piments.jpg',
            'mangue' => 'produit-mangues.jpg',
            'oignon' => 'produit-oignons.jpg',
            default => 'produit-legumes.jpg',
        };

        return asset('images/home/'.$fichier);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }
}
