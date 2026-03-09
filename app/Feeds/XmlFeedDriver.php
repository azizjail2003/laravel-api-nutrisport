<?php

namespace App\Feeds;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class XmlFeedDriver implements FeedDriverInterface
{
    public function render(Collection $products): Response
    {
        $xml = new \SimpleXMLElement('<catalogue/>');
        $xml->addAttribute('titre', 'NutriSport Catalogue');

        foreach ($products as $product) {
            $item = $xml->addChild('produit');
            $item->addChild('id', $product->id);
            $item->addChild('nom', htmlspecialchars($product->name));
            $item->addChild('en_stock', $product->isInStock() ? 'true' : 'false');
        }

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }
}
