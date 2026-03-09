<?php

namespace App\Http\Controllers;

use App\Feeds\FeedDriverInterface;
use App\Feeds\JsonFeedDriver;
use App\Feeds\XmlFeedDriver;
use App\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class FeedController extends Controller
{
    /**
     * Registry of available feed drivers.
     * To add a new feed type, just add an entry here — no other code changes needed.
     */
    private array $drivers = [
        'json' => JsonFeedDriver::class,
        'xml'  => XmlFeedDriver::class,
    ];

    public function serve(string $format): Response
    {
        if (!isset($this->drivers[$format])) {
            return response()->json(['message' => "Format de flux '$format' non supporté."], 404);
        }

        /** @var FeedDriverInterface $driver */
        $driver   = app($this->drivers[$format]);
        $products = Product::all();

        return $driver->render($products);
    }
}
