<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Importacio extends Model
{
    use HasFactory;

    // Defineix la taula associada
    protected $table = 'importacions';

    // Especifica els camps assignables
    protected $fillable = [
        'vendor_id',
        'botiga_id',
        'fitxer',
        'total_importats',
        'total_errors',
        'errors',
        'observacions',
    ];

    // Converteix el camp 'errors' a array automàticament
    protected $casts = [
        'errors' => 'array',
    ];

    /**
     * Relació amb el model User (o Vendor, segons com estigui definit).
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Relació amb el model Botiga.
     */
    public function botiga()
    {
        return $this->belongsTo(Botiga::class, 'botiga_id');
    }

    /**
     * Relació amb els productes importats.
     */
    public function productes()
    {
        return $this->hasMany(Producte::class, 'importacio_id');
    }
}
