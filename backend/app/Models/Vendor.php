<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Authenticatable {
    use HasFactory, HasApiTokens;

    protected $fillable = ['name', 'email', 'password'];

    public function botigues() {
        return $this->hasMany(Botiga::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }
}
