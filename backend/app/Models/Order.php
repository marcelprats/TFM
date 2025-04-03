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
        'total',
        'payment_status',  // Ex.: 'pending', 'paid', 'failed'
        'payment_date',
    ];

    // La comanda pertany a una reserva
    public function reserve()
    {
        return $this->belongsTo(Reserve::class, 'reserve_id');
    }
}
