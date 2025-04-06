<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reserve extends Model
{
    use HasFactory;

    protected $table = 'reserves';

    protected $fillable = [
        'vendor_id',
        'buyer_id',
        'botiga_id',
        'total_reserved',
        'deposit_amount',
        'paid_amount',
        'status', // Ex.: 'pending', 'confirmed', 'cancelled', 'completed'
    ];

    // Un Reserve té molts ReserveItems
    public function reserveItems()
    {
        return $this->hasMany(ReserveItem::class, 'reserve_id');
    }

    // Relació amb el venedor
    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    // Relació amb l'usuari (client)
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    // Relació amb la botiga
    public function botiga()
    {
        return $this->belongsTo(\App\Models\Botiga::class, 'botiga_id');
    }

    // Un Reserve pot generar una comanda
    public function order()
    {
        return $this->hasOne(Order::class, 'reserve_id');
    }
}
