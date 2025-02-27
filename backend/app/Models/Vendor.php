<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Vendor extends Authenticatable {
    use HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    public function botigues() {
        return $this->hasMany(Botiga::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }
}
