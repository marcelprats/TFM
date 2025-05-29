<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewStoreDetail extends Model
{
    protected $table = 'review_store_details';

    public $timestamps = false; // Només fem servir created_at

    protected $fillable = [
        'review_id',
        'category',
        'score',
    ];

    // Relació cap a Review
    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
