<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityValidation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'admin_id',
        'cree_le',
    ];

    protected function casts(): array
    {
        return [
            'cree_le' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
