<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'reserve_item_id',
        'rating',
        'comment',
    ];

    /**
     * Relació a l’usuari que ha escrit la review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relació a la línia de reserva (pivot de comanda→producte).
     */
    public function reserveItem()
    {
        return $this->belongsTo(ReserveItem::class);
    }
}
