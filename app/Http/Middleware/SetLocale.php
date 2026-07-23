<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['ru', 'tk'];

        if ($request->is('api/*')) {
            $locale = $request->header('X-Locale')
                ?? substr((string) $request->header('Accept-Language', ''), 0, 2);
        } else {
            $locale = session('locale', config('app.locale', 'ru'));
        }

        if (in_array($locale, $supported, strict: true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
