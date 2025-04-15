<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'reserve_id',
        'order_number',
        'buyer_id',
        'buyer_type',      // Afegit per emmagatzemar el rol (user o vendor)
        'total_amount',
        'payment_method',
        'transaction_id',
        'status',
    ];

    // La comanda pertany a una reserva
    public function reserve()
    {
        return $this->belongsTo(Reserve::class, 'reserve_id');
    }

    // Relació polimòrfica per accedir al comprador (User o Vendor)
    public function buyer()
    {
        return $this->morphTo();
    }
}
