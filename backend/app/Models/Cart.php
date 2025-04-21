<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'total_price',
    ];

    /**
     * Relació polimòrfica inversa: el propietari pot ser User o Vendor.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relació amb els ítems del carret.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
