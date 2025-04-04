<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price'
    ];

    // Defineix la relació amb els ítems del carret
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
