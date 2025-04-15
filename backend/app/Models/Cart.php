<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    // Ara el carret té les columnes 'owner_id' i 'owner_type'
    protected $fillable = [
        'owner_id',
        'owner_type',
        'total_price'
    ];

    /**
     * Relació polimòrfica per identificar el propietari del carret.
     * Aquest propietari pot ser un usuari (User) o un venedor (Vendor).
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * Relació one-to-many amb els ítems del carret.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
