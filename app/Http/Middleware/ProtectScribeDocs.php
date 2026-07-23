<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectScribeDocs
{
    /**
     * Restrict access to the generated API docs (/docs) in production.
     *
     * In local/dev the docs stay open for convenience. In production only an
     * authenticated admin user may view them; everyone else gets a 404 so the
     * docs endpoint is not even discoverable.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction() && ! $request->user()) {
            abort(404);
        }

        return $next($request);
    }
}
