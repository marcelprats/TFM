<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Botiga extends Model
{
    use HasFactory;

    protected $table = 'botigues';

    protected $fillable = ['nom', 'descripcio', 'vendor_id', 'latitude', 'longitude'];

    /**
     * Una botiga pot tenir molts productes (relació one-to-many).
     */
    public function productes()
    {
        return $this->hasMany(Producte::class, 'botiga_id');
    }

    /**
     * Relació amb els horaris de la botiga.
     */
    public function horaris()
    {
        return $this->hasMany(HorariBotiga::class, 'botiga_id');
    }
}
