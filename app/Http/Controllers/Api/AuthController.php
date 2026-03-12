<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function guard()
    {
        return auth('api');
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var \App\Models\Site $site */
        $site = app('current_site');

        // Email unique per site
        if (User::where('email', $request->email)->where('site_id', $site->id)->exists()) {
            throw ValidationException::withMessages(['email' => ['Cette adresse email est déjà utilisée sur ce site.']]);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'site_id'  => $site->id,
        ]);

        $token = $this->guard()->login($user);

        $this->mergeCart($request, $user);

        return $this->respondWithToken($token, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
            'site_id'  => $site->id,
        ];

        if (!$token = $this->guard()->attempt($credentials)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $this->mergeCart($request, $this->guard()->user());

        return $this->respondWithToken($token);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->guard()->user());
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $this->guard()->user();

        $request->validate([
            'name'         => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|max:255|unique:users,email,' . $user->id . ',id,site_id,' . $user->site_id,
            'password'     => 'sometimes|string|min:8|confirmed',
            'old_password' => 'required_with:password|string',
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->old_password, $user->password)) {
                throw ValidationException::withMessages(['old_password' => ['Ancien mot de passe incorrect.']]);
            }
            $user->password = $request->password;
        }

        $user->fill($request->only(['name', 'email']))->save();

        return response()->json(['message' => 'Profil mis à jour.', 'user' => $user]);
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    private function mergeCart(Request $request, User $user): void
    {
        /** @var \App\Models\Site $site */
        $site = app('current_site');

        $sessionKey = 'cart_session_' . $request->header('X-Cart-Token', $request->ip() . '_' . $site->id);
        $userKey = 'cart_user_' . $user->id . '_site_' . $site->id;

        $sessionCart = \Illuminate\Support\Facades\Cache::get($sessionKey, []);
        
        if (empty($sessionCart)) {
            return;
        }

        $userCart = \Illuminate\Support\Facades\Cache::get($userKey, []);

        foreach ($sessionCart as $productId => $entry) {
            if (isset($userCart[$productId])) {
                $userCart[$productId]['quantity'] += $entry['quantity'];
            } else {
                $userCart[$productId] = $entry;
            }
        }

        \Illuminate\Support\Facades\Cache::put($userKey, $userCart, now()->addDays(3));
        \Illuminate\Support\Facades\Cache::forget($sessionKey);
    }

    private function respondWithToken(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ], $status);
    }
}
