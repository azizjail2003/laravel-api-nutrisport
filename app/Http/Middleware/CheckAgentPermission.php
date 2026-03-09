<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAgentPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $agent = auth('api_agent')->user();

        if (!$agent) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (!$agent->hasPermission($permission)) {
            return response()->json(['message' => 'Permission insuffisante.'], 403);
        }

        return $next($request);
    }
}
