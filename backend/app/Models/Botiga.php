<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Botiga extends Model
{
    use HasFactory;

    protected $table = 'botigues';

    protected $fillable = ['nom', 'descripcio', 'vendor_id', 'latitude', 'longitude'];

    /**
     * Relació Many-to-Many amb productes (una botiga pot tenir molts productes).
     */
    public function productes(): BelongsToMany
    {
        return $this->belongsToMany(Producte::class, 'botiga_productes', 'botiga_id', 'product_id');
    }
}
