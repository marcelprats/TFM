<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Botiga extends Model
{
    use HasFactory;

    protected $table = 'botigues'; // 👈 Definim el nom correcte de la taula

    protected $fillable = ['nom', 'descripcio', 'vendor_id'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
