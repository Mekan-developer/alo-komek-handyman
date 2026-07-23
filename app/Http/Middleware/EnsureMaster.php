<?php

namespace App\Http\Middleware;

use App\Models\Master;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Master) {
            return response()->json(['message' => __('api.master.token_required')], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => __('api.master.disabled')], 403);
        }

        if (! $user->hasActiveAccess()) {
            return response()->json(['message' => __('api.master.access_expired')], 403);
        }

        return $next($request);
    }
}
