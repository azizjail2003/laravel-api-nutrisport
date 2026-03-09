<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $orders = Order::with(['items.product', 'site'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json($orders);
    }

    public function show(string $site, int $id): JsonResponse
    {

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $order = Order::with(['items.product', 'site'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json($this->formatOrder($order));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'shipping_full_name' => 'required|string|max:255',
            'shipping_address'   => 'required|string|max:255',
            'shipping_city'      => 'required|string|max:100',
            'shipping_country'   => 'required|string|max:100',
        ]);

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        /** @var \App\Models\Site $site */
        $site = app('current_site');

        // Get cart
        $cartController = app(CartController::class);
        $cart = $cartController->getCart($request);

        if (empty($cart)) {
            return response()->json(['message' => 'Votre panier est vide.'], 422);
        }

        // Verify stock and build order items
        $productIds = array_keys($cart);
        $products = Product::with(['prices' => fn($q) => $q->where('site_id', $site->id)])
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        DB::beginTransaction();
        try {
            $total = 0;
            $orderItems = [];

            foreach ($cart as $productId => $entry) {
                $product = $products[$productId] ?? null;
                if (!$product) {
                    DB::rollBack();
                    return response()->json(['message' => "Produit ID $productId introuvable."], 422);
                }

                if ($product->stock < $entry['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Stock insuffisant pour \"{$product->name}\".",
                    ], 422);
                }

                $priceModel = $product->prices->first();
                $unitPrice  = $priceModel ? (float) $priceModel->price : 0;
                $subtotal   = $unitPrice * $entry['quantity'];
                $total     += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity'   => $entry['quantity'],
                    'unit_price' => $unitPrice,
                ];

                // Decrement shared stock
                $product->decrement('stock', $entry['quantity']);
            }

            $order = Order::create([
                'user_id'            => $user->id,
                'site_id'            => $site->id,
                'total'              => round($total, 2),
                'status'             => 'pending',
                'shipping_full_name' => $request->shipping_full_name,
                'shipping_address'   => $request->shipping_address,
                'shipping_city'      => $request->shipping_city,
                'shipping_country'   => $request->shipping_country,
                'payment_method'     => 'bank_transfer',
            ]);

            $order->items()->createMany($orderItems);
            $order->load('items.product');

            DB::commit();

            // Clear cart
            $cartController->clear($request);

            // Send emails
            Mail::to($user->email)->queue(new OrderConfirmationMail($order, $user, 'client'));
            Mail::to(config('mail.admin_address'))->queue(new OrderConfirmationMail($order, $user, 'admin'));

            // Pusher broadcast
            event(new OrderPlaced($order));

            return response()->json($this->formatOrder($order), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la commande.', 'error' => $e->getMessage()], 500);
        }
    }

    private function formatOrder(Order $order): array
    {
        $devise = match ($order->site->code ?? 'fr') {
            'it' => 'EUR',
            'be' => 'CHF',
            default => 'EUR',
        };

        return [
            'id'                 => $order->id,
            'total'              => (float) $order->total,
            'devise'             => $devise,
            'status'             => $order->status,
            'payment_method'     => $order->payment_method,
            'shipping_full_name' => $order->shipping_full_name,
            'shipping_address'   => $order->shipping_address,
            'shipping_city'      => $order->shipping_city,
            'shipping_country'   => $order->shipping_country,
            'created_at'         => $order->created_at,
            'contenu'            => $order->items->map(fn($item) => [
                'product_id'  => $item->product_id,
                'nom'         => $item->product->name ?? '—',
                'quantite'    => $item->quantity,
                'prix_unit'   => (float) $item->unit_price,
                'sous_total'  => round($item->quantity * $item->unit_price, 2),
            ]),
        ];
    }
}
