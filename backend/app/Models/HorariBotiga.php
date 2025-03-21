<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorariBotiga extends Model
{
    public $timestamps = false;

    protected $table = 'horaris_botiga';
    
    protected $fillable = ['botiga_id', 'dia', 'obertura', 'tancament'];

    public function botiga()
    {
        return $this->belongsTo(Botiga::class, 'botiga_id');
    }
}
