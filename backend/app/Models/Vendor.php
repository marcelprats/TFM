<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // Amaguem camps que poden generar cicles
    protected $hidden = [
        'password',
        'remember_token',
        'tokens',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function botigues()
    {
        return $this->hasMany(Botiga::class);
    }

    /**
     * Relació polimòrfica: un Vendor té un únic Cart.
     */
    public function cart()
    {
        return $this->morphOne(Cart::class, 'owner');
    }

    public function orders()
    {
        return $this->morphMany(Order::class, 'buyer');
    }
    
    public function reserves()
    {
        return $this->morphMany(Reserve::class, 'buyer');
    }

    public function getMorphAlias()
    {
        return 'vendor';
    }

}
