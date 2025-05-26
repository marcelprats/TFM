<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\ReserveItem;

class ReviewsController extends Controller
{
    /**
     * GET /api/reviews
     * Llista TOTES les reviews (vista global).
     */
    public function index()
    {
        return Review::query()
            ->join('reserve_items as ri',     'ri.id',              '=', 'reviews.reserve_item_id')
            ->join('orders as o',              'o.reserve_id',       '=', 'ri.reserve_id')
            ->join('users as u',               'u.id',               '=', 'reviews.user_id')
            // IMPORTANT: la taula de productes es 'productes'
            ->join('productes as p',           'p.id',               '=', 'ri.product_id')
            ->where('o.status', 'completed')
            ->orderBy('reviews.created_at', 'desc')
            ->get([
                'reviews.id',
                'reviews.rating',
                'reviews.comment',
                'reviews.created_at',
                'u.name as reviewer_name',
                'ri.product_id',
                'p.nom as product_name',       // o el camp que es digui al teu esquema
            ]);
    }

    /**
     * GET /api/productes/{productId}/reviews
     * Llista les reviews d’un producte concret.
     */
    public function byProduct($productId)
    {
        return Review::query()
            ->join('reserve_items as ri', 'ri.id',              '=', 'reviews.reserve_item_id')
            ->join('orders as o',          'o.reserve_id',       '=', 'ri.reserve_id')
            ->join('users as u',           'u.id',               '=', 'reviews.user_id')
            ->where('ri.product_id', $productId)
            ->where('o.status', 'completed')
            ->orderBy('reviews.created_at', 'desc')
            ->get([
                'reviews.id',
                'reviews.rating',
                'reviews.comment',
                'reviews.created_at',
                'u.name as reviewer_name',
            ]);
    }

    /**
     * GET /api/my-reviews
     * Llista només les reviews que ha fet l’usuari loguejat.
     */
    public function myReviews(Request $request)
    {
        $userId = $request->user()->id;

        return Review::query()
            ->join('reserve_items as ri', 'ri.id',              '=', 'reviews.reserve_item_id')
            ->where('reviews.user_id', $userId)
            ->get([
                'reviews.id',
                'ri.product_id',
            ]);
    }

    /**
     * POST /api/reviews
     * Crea una nova review.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'reserveItemId' => 'required|integer|exists:reserve_items,id',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $riId   = $data['reserveItemId'];

        // Validar compra existosa
        $ok = ReserveItem::query()
            ->join('orders as o', 'o.reserve_id', '=', 'reserve_items.reserve_id')
            ->where('reserve_items.id', $riId)
            ->where('o.buyer_id', $userId)
            ->where('o.status', 'completed')
            ->exists();

        if (! $ok) {
            return response()->json(['error' => 'No pots valorar aquest ítem'], 403);
        }

        $review = Review::create([
            'user_id'         => $userId,
            'reserve_item_id' => $riId,
            'rating'          => $data['rating'],
            'comment'         => $data['comment'] ?? null,
        ]);

        return response()->json($review, 201);
    }
}
