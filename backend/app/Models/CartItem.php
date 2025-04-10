<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'reserved_price',
        'selected',
    ];

    protected $casts = [
        'selected' => 'boolean',
    ];
    /**
     * Relació: Cada ítem pertany a un carret.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relació: Cada ítem fa referència a un producte.
     */
    public function product()
    {
        return $this->belongsTo(Producte::class, 'product_id');
    }
}
