<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackOfficeController extends Controller
{
    /**
     * GET /api/backoffice/orders
     * Permission required: view_orders
     * Lists orders from last 5 days (all sites), paginated.
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = Order::with(['user', 'site'])
            ->where('created_at', '>=', now()->subDays(5))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $orders->getCollection()->map(fn($order) => [
                'id'           => $order->id,
                'nom_client'   => $order->user->name ?? '—',
                'site'         => $order->site->code ?? '—',
                'total'        => (float) $order->total,
                'devise'       => 'EUR',
                'status'       => $order->status,
                'reste_a_payer'=> $order->status === 'paid' ? 0 : (float) $order->total,
                'created_at'   => $order->created_at,
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * POST /api/backoffice/products
     * Permission required: create_products
     * Creates a product with per-site pricing.
     */
    public function createProduct(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'stock'        => 'required|integer|min:0',
            'prices'       => 'required|array',
            'prices.*.site_id' => 'required|exists:sites,id',
            'prices.*.price'   => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'name'  => $request->name,
            'stock' => $request->stock,
        ]);

        foreach ($request->prices as $priceData) {
            ProductPrice::updateOrCreate(
                ['product_id' => $product->id, 'site_id' => $priceData['site_id']],
                ['price' => $priceData['price']]
            );
        }

        $product->load('prices.site');

        return response()->json([
            'message' => 'Produit créé avec succès.',
            'product' => [
                'id'     => $product->id,
                'name'   => $product->name,
                'stock'  => $product->stock,
                'prices' => $product->prices->map(fn($p) => [
                    'site'  => $p->site->code,
                    'price' => (float) $p->price,
                ]),
            ],
        ], 201);
    }
}
