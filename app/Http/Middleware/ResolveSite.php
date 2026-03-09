<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSite
{
    /**
     * Resolve the site from the {site} route parameter (e.g. "fr", "it", "be").
     * Binds the Site model into the request and the container for downstream use.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $siteCode = $request->route('site');

        $site = Site::where('code', $siteCode)->first();

        if (!$site) {
            return response()->json(['message' => 'Site introuvable.'], 404);
        }

        // Make the site available everywhere
        $request->merge(['_site' => $site]);
        app()->instance('current_site', $site);

        return $next($request);
    }
}
