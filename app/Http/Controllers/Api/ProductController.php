<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $products = Product::with(['prices' => fn($q) => $q->where('site_id', $site->id)])
            ->get()
            ->map(fn($product) => $this->formatProduct($product, $site->id));

        return response()->json($products);
    }

    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $product = Product::with(['prices' => fn($q) => $q->where('site_id', $site->id)])
            ->findOrFail($id);

        return response()->json($this->formatProduct($product, $site->id));
    }

    private function formatProduct($product, int $siteId): array
    {
        $priceModel = $product->prices->first();

        return [
            'id'          => $product->id,
            'nom'         => $product->name,
            'prix'        => $priceModel ? (float) $priceModel->price : null,
            'devise'      => 'EUR',
            'en_stock'    => $product->isInStock(),
            'stock'       => $product->stock,
        ];
    }
}
