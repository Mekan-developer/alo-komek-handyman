<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClient
{
    /**
     * Ensure the Sanctum tokenable is an unblocked Client.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Client) {
            return response()->json(['message' => __('api.client.token_required')], 403);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => __('api.client.blocked')], 403);
        }

        return $next($request);
    }
}
