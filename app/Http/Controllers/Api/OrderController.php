<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Order::with(['products:id,name,price', 'user:id,name']);

        if (!$user || !$user->is_admin) {
            $query->where('user_id', $user?->id);
        }

        $orders = $query->latest()->paginate($request->query('per_page', 15));
        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        try {
            DB::beginTransaction();

            $productIds = collect($validated['products'])->pluck('id');
            $productsFromDb = Product::findMany($productIds)->keyBy('id');

            $totalAmount = 0;
            $orderProductsData = [];

            foreach ($validated['products'] as $reqProduct) {
                $productId = $reqProduct['id'];
                $quantity = $reqProduct['quantity'];

                if (!isset($productsFromDb[$productId])) {
                    throw new \Exception("Product with ID {$productId} not found.");
                }

                $product = $productsFromDb[$productId];
                $priceAtOrderTime = $product->price;
                $totalAmount += $priceAtOrderTime * $quantity;

                $orderProductsData[$productId] = [
                    'quantity' => $quantity,
                    'price_at_time_of_order' => $priceAtOrderTime,
                ];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            $order->products()->attach($orderProductsData);

            DB::commit();

            $order->load(['products' => function ($query) {
                $query->withPivot('quantity', 'price_at_time_of_order');
            }]);

            return response()->json($order, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to place order.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $user = Auth::user();
        if (!$user || ($order->user_id !== $user->id && !$user->is_admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $order->load([
            'user:id,name,email',
            'products' => function ($query) {
                $query->select('products.id', 'products.name', 'products.image_url')
                    ->withPivot('quantity', 'price_at_time_of_order');
            }
        ]);
        return response()->json($order);
    }


    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $order->update($request->validated());
        $order->load([
            'user:id,name,email',
            'products' => function ($query) {
                $query->select('products.id', 'products.name', 'products.image_url')
                    ->withPivot('quantity', 'price_at_time_of_order');
            }
        ]);
        return response()->json($order);
    }


    public function destroy(Request $request, Order $order): JsonResponse
    {
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $order->delete();
        return response()->json(null, 204);
    }
}
