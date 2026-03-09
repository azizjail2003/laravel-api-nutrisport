<?php

namespace App\Feeds;

interface FeedDriverInterface
{
    /**
     * Render the product catalog in this feed's format.
     *
     * @param  \Illuminate\Support\Collection $products
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render(\Illuminate\Support\Collection $products): \Symfony\Component\HttpFoundation\Response;
}
