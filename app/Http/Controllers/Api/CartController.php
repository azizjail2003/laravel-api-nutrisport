<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    private const CART_TTL_DAYS = 3;

    /**
     * Cart key: per-session for guests, per-user-site for auth'd users.
     */
    private function cartKey(Request $request): string
    {
        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $user = auth('api')->user();

        return $user
            ? 'cart_user_' . $user->id . '_site_' . $site->id
            : 'cart_session_' . $request->header('X-Cart-Token', $request->ip() . '_' . $site->id);
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);
        return response()->json($this->enrichCart($cart));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $product = Product::findOrFail($request->product_id);

        if (!$product->isInStock()) {
            return response()->json(['message' => 'Produit en rupture de stock.'], 422);
        }

        $cart = $this->getCart($request);
        $productId = (string) $request->product_id;

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $request->quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
            ];
        }

        $this->saveCart($request, $cart);

        return response()->json(['message' => 'Produit ajouté au panier.', 'cart' => $this->enrichCart($cart)]);
    }

    public function remove(Request $request, string $site, string|int $productId): JsonResponse
    {
        $cart = $this->getCart($request);

        unset($cart[(string) $productId]);

        $this->saveCart($request, $cart);

        return response()->json(['message' => 'Produit retiré du panier.', 'cart' => $this->enrichCart($cart)]);
    }

    public function clear(Request $request): JsonResponse
    {
        Cache::forget($this->cartKey($request));
        return response()->json(['message' => 'Panier vidé.']);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    public function getCart(Request $request): array
    {
        return Cache::get($this->cartKey($request), []);
    }

    public function saveCart(Request $request, array $cart): void
    {
        Cache::put($this->cartKey($request), $cart, now()->addDays(self::CART_TTL_DAYS));
    }

    private function enrichCart(array $cart): array
    {
        if (empty($cart)) {
            return ['items' => [], 'total' => 0];
        }

        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $productIds = array_keys($cart);
        $products   = Product::with(['prices' => fn($q) => $q->where('site_id', $site->id)])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = [];
        $total = 0;

        foreach ($cart as $productId => $entry) {
            if (!is_int($productId) && !is_string($productId)) {
                dd('Invalid productId type in cart cache:', gettype($productId), $productId, $cart);
            }
            $product = $products[$productId] ?? null;
            if (!$product) {
                continue;
            }

            $priceModel = $product->prices->first();
            $price      = $priceModel ? (float) $priceModel->price : 0;
            $subtotal   = $price * $entry['quantity'];
            $total     += $subtotal;

            $items[] = [
                'product_id' => $product->id,
                'nom'        => $product->name,
                'prix'       => $price,
                'devise'     => 'EUR',
                'quantite'   => $entry['quantity'],
                'sous_total' => $subtotal,
                'en_stock'   => $product->isInStock(),
            ];
        }

        return ['items' => $items, 'total' => round($total, 2), 'devise' => 'EUR'];
    }
}
