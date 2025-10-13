<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSanctumToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get token from Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Hash token to match database
        $hashedToken = hash('sha256', $token);

        // Find token in database
        $accessToken = PersonalAccessToken::where('token', $hashedToken)->first();

        if (!$accessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // Check if token expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token expired'], 401);
        }

        // Update last_used_at
        $accessToken->update(['last_used_at' => now()]);

        // Set authenticated user
        auth()->setUser($accessToken->tokenable);
        
        // Store token in request for later use
        $request->attributes->set('sanctum_token', $accessToken);

        return $next($request);
    }
}
