<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id','product_id','quantity','reserved_price','selected'];
    protected $casts    = ['selected'=>'boolean','reserved_price'=>'float','quantity'=>'integer'];
    protected $with     = ['cart'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Producte::class, 'product_id');
    }

    /**
     * Assegura que la quantitat estigui entre 1 i stock.
     */
    protected static function booted()
    {
        static::saving(function (CartItem $item) {
            $stock = $item->product?->stock ?? 0;
            if ($item->quantity < 1 || $item->quantity > $stock) {
                throw ValidationException::withMessages([
                    'quantity' => ["Quantitat invàlida: només hi ha {$stock} unitats disponibles."],
                ]);
            }
        });
    }

    /**
     * Mutator per normalitzar la quantitat al fer set.
     */
    public function setQuantityAttribute($value)
    {
        $max = $this->product?->stock ?? 0;
        $this->attributes['quantity'] = min(max(1, intval($value)), $max);
    }
}