<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'nom',
        'slug',
        'descripcio',
        'parent_id', // Assegura’t que aquest camp existeix a la taula
    ];

    public $timestamps = true;

    // Un producte pot tenir moltes categories (una relació antiga, correcte si s'usa)
    public function productes()
    {
        return $this->hasMany(Producte::class);
    }

    // 🧩 Relació amb subcategories
    public function subcategories(): HasMany
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    // 🔁 Relació amb la categoria pare
    public function pare(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }
}

