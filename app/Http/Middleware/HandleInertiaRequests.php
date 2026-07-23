<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => session('locale', config('app.locale', 'ru')),
            'tilesStyleUrl' => config('services.tiles.style_url'),
            'notification' => session('notification'),
            'translations' => $this->loadTranslations(),
            'unreadNotificationsCount' => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }

    /**
     * Load every PHP lang file under lang/{locale} for all supported locales,
     * so the Vue i18n client can hot-swap without a round trip.
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadTranslations(): array
    {
        return Cache::driver(app()->isProduction() ? null : 'array')->rememberForever(
            'inertia.translations',
            function (): array {
                $locales = ['ru', 'tk'];
                $bundle = [];

                foreach ($locales as $locale) {
                    $path = lang_path($locale);

                    if (! File::isDirectory($path)) {
                        $bundle[$locale] = [];

                        continue;
                    }

                    $bundle[$locale] = collect(File::files($path))
                        ->filter(fn ($file) => $file->getExtension() === 'php')
                        ->mapWithKeys(fn ($file) => [
                            $file->getFilenameWithoutExtension() => require $file->getPathname(),
                        ])
                        ->all();
                }

                return $bundle;
            }
        );
    }
}
