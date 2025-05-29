<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\ReserveItem;
use App\Models\ReviewStoreDetail;
use Illuminate\Support\Facades\DB;

class ReviewsController extends Controller
{
    /**
     * GET /api/reviews
     * Llista TOTES les reviews (vista global).
     */
    public function index()
    {
        return Review::query()
            ->join('reserve_items as ri', 'ri.id', '=', 'reviews.reserve_item_id')
            ->join('orders as o', 'o.reserve_id', '=', 'ri.reserve_id')
            ->join('users as u', 'u.id', '=', 'reviews.user_id')
            ->join('productes as p', 'p.id', '=', 'ri.product_id')
            ->where('o.status', 'completed')
            ->orderBy('reviews.created_at', 'desc')
            ->get([
                'reviews.id',
                'reviews.rating',
                'reviews.comment',
                'reviews.created_at',
                'u.name as reviewer_name',
                'ri.product_id',
                'p.nom as product_name',
            ]);
    }

    /**
     * GET /api/productes/{productId}/reviews
     * Llista les reviews d'un producte concret.
     */
    public function byProduct($productId)
    {
        return Review::query()
            ->join('reserve_items as ri', 'ri.id', '=', 'reviews.reserve_item_id')
            ->join('orders as o', 'o.reserve_id', '=', 'ri.reserve_id')
            ->join('users as u', 'u.id', '=', 'reviews.user_id')
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
     * GET /api/productes/{productId}/store-summary
     * Mitjana i total de reviews d'un producte.
     */
    public function productSummary($productId)
    {
        $row = DB::table('reviews as r')
            ->select(
                DB::raw('ROUND(AVG(r.rating),2) as avg_score'),
                DB::raw('COUNT(*) as total')
            )
            ->join('reserve_items as ri', 'ri.id', '=', 'r.reserve_item_id')
            ->where('ri.product_id', $productId)
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('orders as o')
                      ->whereRaw('o.reserve_id = ri.reserve_id')
                      ->where('o.status', 'completed');
            })
            ->first();

        return response()->json([
            'avg'   => $row->avg_score ?? 0,
            'total' => $row->total ?? 0,
        ]);
    }

    /**
     * GET /api/my-reviews
     * Llista només les reviews que ha fet l'usuari loguejat.
     */
    public function myReviews(Request $request)
    {
        $userId = $request->user()->id;
        return Review::query()
            ->where('reviews.user_id', $userId)
            ->get(['id', 'reserve_item_id']);
    }

    /**
     * POST /api/reviews
     * Crea una nova review amb detalls de botiga.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'reserveItemId'   => 'required|integer|exists:reserve_items,id',
            'rating'          => 'required|integer|min:1|max:5',
            'comment'         => 'nullable|string',
            'store_ambient'   => 'required|integer|min:1|max:5',
            'store_personal'  => 'required|integer|min:1|max:5',
            'store_recollida' => 'required|integer|min:1|max:5',
        ]);

        $userId = $request->user()->id;
        $riId = $data['reserveItemId'];

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

        // Crear la review principal
        $review = Review::create([
            'user_id' => $userId,
            'reserve_item_id' => $riId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        // Crear detalls de botiga
        $categories = [
            'ambient'   => $data['store_ambient'],
            'personal'  => $data['store_personal'],
            'recollida' => $data['store_recollida'],
        ];
        foreach ($categories as $cat => $score) {
            $review->storeDetails()->create([
                'category' => $cat,
                'score'    => $score
            ]);
        }

        return response()->json($review, 201);
    }

    /**
     * GET /api/botigues/{botigaId}/store-summary
     * Mitjanes i totals per àmbit de la botiga.
     */
    public function storeSummary($botigaId)
    {
        $rows = DB::table('review_store_details as rsd')
            ->select(
                'rsd.category',
                DB::raw('ROUND(AVG(rsd.score),2) as avg_score'),
                DB::raw('COUNT(*) as total')
            )
            ->join('reviews as r', 'r.id', '=', 'rsd.review_id')
            ->join('reserve_items as ri', 'ri.id', '=', 'r.reserve_item_id')
            ->join('productes as p', 'p.id', '=', 'ri.product_id')
            ->where('p.botiga_id', $botigaId)
            ->groupBy('rsd.category')
            ->get();

        $summary = [];
        foreach ($rows as $r) {
            $summary[$r->category] = [
                'avg'   => (float) $r->avg_score,
                'total' => (int)   $r->total,
            ];
        }

        return response()->json($summary);
    }

    /**
     * GET /api/botigues/{botigaId}/reviews
     * Llista reviews recents de la botiga amb detalls.
     */
    public function byStore($botigaId)
    {
        $rows = DB::table('review_store_details as rsd')
            ->select(
                'r.id as review_id',
                'r.created_at',
                'rsd.category',
                'rsd.score'
            )
            ->join('reviews as r', 'r.id', '=', 'rsd.review_id')
            ->join('reserve_items as ri', 'ri.id', '=', 'r.reserve_item_id')
            ->join('productes as p', 'p.id', '=', 'ri.product_id')
            ->where('p.botiga_id', $botigaId)
            ->orderBy('r.created_at', 'desc')
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            $id = $r->review_id;
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id'           => $id,
                    'created_at'   => $r->created_at,
                    'storeDetails' => [],
                ];
            }
            $grouped[$id]['storeDetails'][] = [
                'category' => $r->category,
                'score'    => $r->score,
            ];
        }

        return response()->json(array_values($grouped));
    }
}
