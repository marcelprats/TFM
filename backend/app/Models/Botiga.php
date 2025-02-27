<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Botiga extends Model {
    use HasFactory;

    protected $fillable = ['vendor_id', 'name', 'description'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function productes() {
        return $this->belongsToMany(Product::class, 'botiga_productes')->withPivot('stock')->withTimestamps();
    }
}

