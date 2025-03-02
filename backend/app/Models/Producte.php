<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Producte extends Model
{
    use HasFactory;

    protected $table = 'productes'; // 👈 Definim el nom correcte de la taula

    protected $fillable = ['nom', 'descripcio', 'preu', 'stock', 'imatge', 'vendor_id'];

    /**
     * Relació amb el venedor (cada producte pertany a un venedor).
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relació Many-to-Many amb botigues (un producte pot estar en diverses botigues).
     */


    public function botigues()
    {
        return $this->belongsToMany(Botiga::class, 'botiga_productes', 'product_id', 'botiga_id');
    }
}
