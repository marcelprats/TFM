<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;

class Botiga extends Model
{
    use HasFactory;

    protected $table = 'botigues';

    protected $fillable = ['nom', 'descripcio', 'vendor_id', 'latitude', 'longitude'];

    // Amaguem la relació vendor per evitar recursió en la serialització
    protected $hidden = ['vendor'];

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

    /**
     * Cada botiga pertany a un venedor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
