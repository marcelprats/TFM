<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producte extends Model
{
    use HasFactory;

    protected $table = 'productes';

    protected $fillable = [
        'nom',
        'descripcio',
        'categoria',
        'preu',
        'stock',
        'imatge',
        'vendor_id',
        'botiga_id',
    ];

    /**
     * Cada producte pertany a un venedor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Cada producte pertany a una única botiga.
     */
    public function botiga()
    {
        return $this->belongsTo(Botiga::class, 'botiga_id');
    }
}
