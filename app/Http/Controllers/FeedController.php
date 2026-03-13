<?php

namespace App\Http\Controllers;

use App\Feeds\FeedDriverInterface;
use App\Feeds\JsonFeedDriver;
use App\Feeds\XmlFeedDriver;
use App\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class FeedController extends Controller
{
    public function serve(string $format): Response
    {
        $drivers = config('feeds.drivers', []);

        if (!isset($drivers[$format])) {
            return response()->json(['message' => "Format de flux '$format' non supporté."], 404);
        }

        /** @var FeedDriverInterface $driver */
        $driver   = app($drivers[$format]);
        $products = Product::all();

        return $driver->render($products);
    }
}
