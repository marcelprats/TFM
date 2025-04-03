<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReserveItem extends Model
{
    use HasFactory;

    protected $table = 'reserve_items';

    protected $fillable = [
        'reserve_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    // Cada ReserveItem pertany a un Reserve
    public function reserve()
    {
        return $this->belongsTo(Reserve::class, 'reserve_id');
    }

    // Cada ReserveItem correspon a un producte
    public function product()
    {
        return $this->belongsTo(\App\Models\Producte::class, 'product_id');
    }
}
