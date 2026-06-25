<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for admin-only API endpoints. Requires an authenticated Sanctum user
 * whose `type` is 13 (the admin role the frontend AdminRoute also checks).
 * Returns 401 when no/invalid token, 403 when authenticated but not an admin.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'unauthenticated'], 401);
        }
        if ((int) $user->type !== 13) {
            return response()->json(['message' => 'forbidden — admin only'], 403);
        }
        return $next($request);
    }
}
