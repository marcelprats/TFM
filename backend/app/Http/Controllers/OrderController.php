<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Reserve;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\Relation;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Retorna l'àlies curt (vendor/user) pel model donat.
     */
    protected function getMorphAlias($model): string
    {
        return array_search(get_class($model), Relation::morphMap()) ?? get_class($model);
    }

    /**
     * Llistat general amb filtre de tipus (?type=buyer/vendor/all)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->authorize('viewAny', Order::class);

        $type = $request->query('type', 'buyer');
        $morph = $this->getMorphAlias($user);

        $query = Order::with(['reserve.reserveItems.product', 'reserve.botiga']);

        if ($type === 'buyer') {
            $query->where('buyer_id', $user->id)
                  ->where('buyer_type', $morph);
        } elseif ($type === 'vendor') {
            $query->whereHas('reserve.botiga', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            });
        } else {
            $query->where(function ($q) use ($user, $morph) {
                $q->where('buyer_id', $user->id)
                  ->where('buyer_type', $morph);
            })->orWhereHas('reserve.botiga', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            });
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    /**
     * Comandes del comprador autenticat
     */
    public function myOrders(): JsonResponse
    {
        $user = Auth::user();
        $this->authorize('viewAny', Order::class);

        $morph = $this->getMorphAlias($user);

        $orders = Order::with(['reserve.reserveItems.product', 'reserve.botiga'])
            ->where('buyer_id', $user->id)
            ->where('buyer_type', $morph)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    /**
     * Crear nova comanda
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);
    
        $data = $request->validated();
        $user = Auth::user();
    
        // 🔍 Comprovem el que realment retorna el morphMap
        $userClass = get_class($user);
        $morphAlias = array_search($userClass, \Illuminate\Database\Eloquent\Relations\Relation::morphMap());
    
        \Log::info('ORDER MORPH INFO', [
            'user_class' => get_class($user),
            'morph_alias' => $morphAlias,
            'morphMap' => \Illuminate\Database\Eloquent\Relations\Relation::morphMap(),
        ]);
        
    
        $reserve = Reserve::findOrFail($data['reserve_id']);
    
        if ($reserve->buyer_id !== $user->id || $reserve->buyer_type !== $morphAlias) {
            abort(403, 'Reserva no vàlida per a aquest usuari.');
        }
    
        $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . $user->id;
    
        $order = Order::create([
            'reserve_id'     => $data['reserve_id'],
            'order_number'   => $orderNumber,
            'total_amount'   => $data['total_amount'],
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'status'         => $data['status'],
            'buyer_id'       => $user->id,
            'buyer_type'     => $morphAlias,
            'payment_date'   => now(),
        ]);
    
        return response()->json($order, 201);
    }
    
    
    public function show(Order $order): JsonResponse
    {
        $order->loadMissing('reserve.botiga');
    
        $this->authorize('view', $order);
    
        $order->load([
            'reserve.reserveItems.product',
            'reserve.botiga.vendor'
        ]);
    
        return response()->json($order);
    }
    
    

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $validated = $request->validated();
    
        $order->status = $validated['status'];
        $order->cancellation_reason = $validated['cancellation_reason'] ?? null;
    
        $order->save();
    
        return response()->json([
            'message' => 'Estat actualitzat correctament',
            'order' => $order,
        ]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->load(['reserve.botiga']);
        $this->authorize('delete', $order);

        $order->delete();

        return response()->json(['message' => 'Comanda eliminada correctament.']);
    }

    public function vendorOrders(): JsonResponse
    {
        $vendor = Auth::user();
        $this->authorize('viewAny', Order::class);

        $orders = Order::with(['reserve.reserveItems.product', 'reserve.botiga'])
            ->whereHas('reserve.botiga', fn($q) => $q->where('vendor_id', $vendor->id))
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }
}
