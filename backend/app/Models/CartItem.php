<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'reserved_price' => 'float',
        'quantity' => 'integer',
    ];

    /**
     * Relació amb el carret.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relació amb el producte.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Producte::class, 'product_id');
    }
}
