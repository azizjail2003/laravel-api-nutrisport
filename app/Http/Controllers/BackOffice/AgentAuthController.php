<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Agent guard uses 8h TTL (480 minutes)
        if (!$token = auth('api_agent')->setTTL(480)->attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => 8 * 60 * 60, // 8 hours in seconds
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('api_agent')->logout();
        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth('api_agent')->user());
    }
}
