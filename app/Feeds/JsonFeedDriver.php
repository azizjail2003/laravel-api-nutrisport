<?php

namespace App\Feeds;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class JsonFeedDriver implements FeedDriverInterface
{
    public function render(Collection $products): BaseResponse
    {
        $data = $products->map(fn($p) => [
            'id'          => $p->id,
            'nom'         => $p->name,
            'en_stock'    => $p->isInStock(),
        ])->values();

        return response()->json([
            'feed'     => 'NutriSport Catalogue',
            'produits' => $data,
        ]);
    }
}
