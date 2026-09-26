<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'role',
        'nom',
        'telephone',
        'mot_de_passe',
        'latitude',
        'longitude',
        'quartier',
        'ville',
        'verification',
        'quantite_recherchee',
        'prix_max',
        'rayon_km',
        'photo_profil',
        'piece_agriculteur',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
            'latitude' => 'float',
            'longitude' => 'float',
            'quantite_recherchee' => 'float',
            'prix_max' => 'float',
            'rayon_km' => 'float',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }

    public function getNameAttribute(): string
    {
        return (string) $this->nom;
    }

    public function getPhoneAttribute(): string
    {
        return (string) $this->telephone;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification === 'verifie';
    }

    public function estVerifie(): bool
    {
        return $this->verification === 'verifie';
    }

    public function demanderBadge(): void
    {
        if ($this->verification !== 'verifie') {
            $this->update(['verification' => 'en_cours']);
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVendeur(): bool
    {
        return $this->role === 'vendeur';
    }

    public function isAcheteur(): bool
    {
        return $this->role === 'acheteur';
    }

    public function espaceRoute(): string
    {
        return match ($this->role) {
            'admin' => route('admin.dashboard'),
            'vendeur' => route('vendeur.dashboard'),
            default => route('acheteur.dashboard'),
        };
    }

    public function noteMoyenne(): ?float
    {
        $moyenne = $this->notesRecues()->avg('note');

        return $moyenne === null ? null : round((float) $moyenne, 1);
    }

    public function niveauConfiance(): array
    {
        $notes = $this->relationLoaded('notesRecues') ? $this->notesRecues : $this->notesRecues()->get();
        $score = match ($this->verification) {
            'verifie' => 40,
            'en_cours' => 15,
            default => 0,
        };
        $detail = match ($this->verification) {
            'verifie' => 'Identité vérifiée',
            'en_cours' => 'Identité en cours',
            default => 'Identité non vérifiée',
        };

        if ($notes->isNotEmpty()) {
            $moyenne = round((float) $notes->avg('note'), 1);
            $score += (int) round(($moyenne / 5) * 60);
            $detail .= ' · '.$moyenne.'/5 · '.$notes->count().' note'.($notes->count() > 1 ? 's' : '');
        } else {
            $detail .= ' · pas encore noté';
        }

        $score = min(100, $score);

        return [
            'score' => $score,
            'libelle' => match (true) {
                $score >= 80 => 'Très bon',
                $score >= 60 => 'Bon',
                $score >= 40 => 'Correct',
                default => 'À confirmer',
            },
            'detail' => $detail,
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'seller_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function notesRecues(): HasMany
    {
        return $this->hasMany(Rating::class, 'cible_id');
    }

    public function identite(): HasOne
    {
        return $this->hasOne(IdentityValidation::class, 'user_id');
    }
}
