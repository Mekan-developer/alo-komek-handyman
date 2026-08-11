# Handyman Service — Admin Panel & Backend

A platform for clients to search and book handyman services. Administrators manage masters, assign orders, track payments, and monitor activity in real time via a web admin panel. Masters and clients interact through dedicated Flutter mobile apps.

---

## System Components

| Component | Technology |
|---|---|
| Admin Panel | Laravel 11 + Inertia.js v2 + Vue 3 |
| Mobile Apps | Flutter (Android & iOS) |
| Backend API | Laravel 11 + Sanctum |
| WebSocket | Laravel Reverb |
| Database | MySQL |
| Maps | Self-hosted vector tiles (tileserver-gl) + MapLibre GL |

---

## Tech Stack

| Layer | Package / Version |
|---|---|
| PHP | 8.3 |
| Framework | Laravel 11 |
| SPA Bridge | Inertia.js v2 (`inertiajs/inertia-laravel`) |
| Frontend Framework | Vue 3 (Composition API, `<script setup>`) |
| State Management | Pinia |
| Styling | Tailwind CSS v3 |
| Named Routes | Ziggy v2 |
| API Auth | Laravel Sanctum v4 |
| WebSocket | Laravel Reverb |
| Testing | PHPUnit v10 |
| Code Style | Laravel Pint v1 |
| Basemap Renderer | MapLibre GL (`maplibre-gl`) via `@maplibre/maplibre-gl-leaflet` bridge on Leaflet |
| Map Tiles (masters map) | Self-hosted **tileserver-gl** — style + tiles + glyphs + sprites; URL via `TILES_STYLE_URL` |
| Map Tiles (order picker) | `protomaps-leaflet` + offline `pmtiles` (`turkmenistan.pmtiles`, `TilesController` w/ HTTP Range) |

---

## Architecture & Patterns

This project enforces strict layered architecture. Every developer must follow these patterns without exception.

```
HTTP Request
    └── Controller (thin — HTTP only)
            └── Form Request (validation)
            └── Service / Action (business logic)
                    └── Repository (all DB queries)
                    └── Job (background tasks)
            └── Resource (response formatting)
```

| Pattern | Rule |
|---|---|
| **Thin Controllers** | Only handle HTTP: receive request, call service, return response |
| **Repository Pattern** | ALL database queries live in Repositories — never in Controllers or Services |
| **Services** | Complex multi-step business logic |
| **Actions** | Single-purpose operations (e.g. `AssignMasterToOrderAction`) |
| **Form Requests** | All validation — never `$request->validate()` in controllers |
| **API Resources** | All responses — never return raw models or arrays |
| **Jobs** | All background/async processing (image uploads, notifications) |
| **Observers** | All model event handling |
| **Enums** | All statuses and fixed values — never raw strings |

### Hard Rules

```
❌ NEVER  $request->all()              ✅ USE  $request->validated()
❌ NEVER  Model::find($id)             ✅ USE  Model::findOrFail($id)
❌ NEVER  return true/false            ✅ THROW exceptions from Services
❌ NEVER  raw status strings           ✅ USE  Enums
❌ NEVER  session()->flash() directly  ✅ USE  WithNotification trait
❌ NEVER  hardcode UI text             ✅ USE  translation helpers t() / __()
```

---

## Project Structure

```
app/
├── Actions/                    # Single-purpose operations
├── Console/Commands/           # Artisan commands (auto-registered in L11)
├── Enums/                      # Status enums and fixed value sets
├── Events/                     # Application events
├── Http/
│   ├── Controllers/            # Web controllers (thin, Inertia)
│   │   └── Api/V1/             # API controllers (separate from web)
│   ├── Middleware/             # HTTP middleware
│   ├── Requests/               # Form Request validation classes
│   ├── Resources/              # Eloquent API Resources
│   └── Traits/
│       └── WithNotification.php
├── Jobs/                       # Queued jobs (image processing, etc.)
├── Models/                     # Eloquent models
├── Observers/                  # Model event observers
├── Repositories/               # All database query logic
└── Services/                   # Business logic services

resources/
└── js/
    ├── Components/             # Reusable Vue components
    │   └── PasswordInput.vue   # Password field with visibility toggle
    ├── Composables/            # Vue composables
    ├── Layouts/
    │   └── AdminLayout.vue     # Main admin layout (sidebar + topbar)
    ├── Pages/                  # Inertia page components
    │   ├── Auth/               # Login, Register
    │   ├── Dashboard.vue
    │   └── Profile/
    ├── stores/                 # Pinia stores
    │   ├── useThemeStore.js    # Dark/light mode
    │   ├── useLocaleStore.js   # ru/tk locale
    │   └── useNotificationStore.js
    ├── app.js                  # Inertia + Pinia + vue-i18n bootstrap
    └── i18n.js                 # vue-i18n setup with ru + tk messages

lang/
├── ru/                         # Russian translations
│   ├── auth.php
│   ├── layout.php
│   ├── notifications.php       # Flash notification messages
│   ├── profile.php
│   └── resources.php           # Model names ("Master", "Order", etc.)
└── tk/                         # Turkmen translations (mirrors ru/ exactly)

routes/
├── web.php
├── auth.php
└── api/
    └── v1.php                  # Versioned API routes

public/
└── sounds/
    └── new-order.mp3           # Admin panel alert sound

tests/
├── Feature/                    # Feature tests (primary)
└── Unit/                       # Unit tests (isolated logic only)
```

---

## Local Development Setup

### Requirements

- PHP 8.3
- Composer
- Node.js 20+
- MySQL 8

### Steps

```bash
# 1. Clone the repository
git clone <repo-url>
cd project

# 2. Install dependencies
composer install
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
# Set DB_CONNECTION=mysql, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Run migrations and seeders
php artisan migrate --seed

# 6. Link storage
php artisan storage:link

# 7. Start development servers
composer run dev        # runs Vite + php artisan serve together
# OR separately:
npm run dev
php artisan serve

# 8. Queue worker (required for image uploads and jobs)
php artisan queue:work

# 9. WebSocket server (required for realtime order alerts)
php artisan reverb:start
```

---

## Environment Variables

```dotenv
# ── Application ──────────────────────────────────────────────────────────────
APP_NAME=Handyman            # Shown in browser title bar
APP_ENV=local                # local | staging | production
APP_KEY=                     # Run: php artisan key:generate
APP_DEBUG=true               # Set false in production
APP_URL=http://localhost      # Full public URL (used in emails, links)
APP_TIMEZONE=Asia/Ashgabat   # Server timezone

# ── Localization ─────────────────────────────────────────────────────────────
APP_LOCALE=ru                # Default locale: ru or tk
APP_FALLBACK_LOCALE=ru       # Fallback when translation key is missing

# ── Database (MySQL required) ─────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=handyman
DB_USERNAME=root
DB_PASSWORD=

# ── Queue ─────────────────────────────────────────────────────────────────────
QUEUE_CONNECTION=database    # Use redis in production for performance

# ── WebSocket — Laravel Reverb ────────────────────────────────────────────────
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=               # Generated by: php artisan reverb:install
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http           # https in production

# Injected into Vite for the frontend Echo client
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# ── Storage ───────────────────────────────────────────────────────────────────
FILESYSTEM_DISK=local        # local | s3

# ── Session & Cache ───────────────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120         # Minutes before session expires
CACHE_STORE=database         # database | redis
```

---

## Frontend Standards

All frontend code lives in `resources/js/`. Every component uses `<script setup>` — no Options API, no class components.

### Pinia Stores

| Store | File | Purpose |
|---|---|---|
| Theme | `useThemeStore.js` | Dark/light toggle. Persists to `localStorage`. Applies `dark` class to `<html>`. |
| Locale | `useLocaleStore.js` | Active locale (`ru`/`tk`). Persists to `localStorage`. Synced with vue-i18n `locale` ref. |
| Notifications | `useNotificationStore.js` | Toast queue. Methods: `success()`, `error()`, `warning()`, `info()`. Auto-dismiss after 6s. |

### Component Template

```vue
<script setup>
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const { t } = useI18n()
</script>

<template>
    <AdminLayout :title="t('section.page_title')">
        <!-- Single root element inside the layout slot -->
        <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-slate-800">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ t('section.heading') }}
            </h2>
        </div>
    </AdminLayout>
</template>
```

- **Tailwind only** — no `<style>` blocks except scoped transition animations
- **Dark mode** — every visible element must have `dark:` variants
- **No hardcoded text** — every string goes through `t('key')`
- **`@` alias** resolves to `resources/js/`

---

## Localization

All user-facing text must be translated in **both** `ru` and `tk`. Missing a translation in one language is a bug.

### PHP — Server Side

```php
// lang/ru/notifications.php
return [
    'created' => ':resource успешно создан',
    'updated' => ':resource успешно обновлён',
    'deleted' => ':resource успешно удалён',
];

// lang/ru/resources.php
return [
    'master' => 'Мастер',
    'order'  => 'Заказ',
];

// Usage
__('notifications.created', ['resource' => __('resources.master')])
```

### JavaScript — Client Side

All frontend translations live in `resources/js/i18n.js` under `ru` and `tk` keys.

```js
// i18n.js structure
const messages = {
    ru: { layout: { nav: { dashboard: 'Дашборд' } } },
    tk: { layout: { nav: { dashboard: 'Baş sahypa' } } },
}
```

**Rule**: When adding new UI text, add keys to **all four** locations:
`lang/ru/` → `lang/tk/` → `i18n.js ru` → `i18n.js tk`

---

## Notification System

### Backend — `WithNotification` Trait

```php
use App\Http\Traits\WithNotification;

class MasterController extends Controller
{
    use WithNotification;

    public function store(StoreMasterRequest $request, CreateMasterAction $action): RedirectResponse
    {
        $action->handle($request->validated());
        $this->notifySuccess('notifications.created', ['resource' => __('resources.master')]);
        return redirect()->route('cities.index');
    }
}
```

Available methods: `notifySuccess()` · `notifyError()` · `notifyWarning()` · `notifyInfo()`

All methods accept a lang key + optional replace array. They flash to `session('notification')`, which `HandleInertiaRequests` shares as an Inertia prop.

### Frontend — Automatic Pickup

`AdminLayout.vue` watches `$page.props.notification` and passes it to `useNotificationStore.add()`. No per-page setup needed. Toasts appear top-right and dismiss after 6 seconds.

---

## Image Upload Convention

Synchronous uploads are **prohibited**. All photos must go through a queued Job.

```
Client uploads file
    └── Controller: store temp file, dispatch job
            └── ProcessUploadedImage Job:
                    ├── Convert to WebP
                    ├── Delete original
                    └── Update model with final path
```

Upload status is reflected in the UI (`pending → done`) so the user never waits for processing.

---

## Admin Realtime Alerts

When a client submits a new order:

1. Backend dispatches a broadcast event via **Laravel Reverb**
2. Admin panel receives the WebSocket message
3. `useNotificationStore.info()` displays a toast notification
4. Sound alert plays from `public/sounds/new-order.mp3`
5. Notification bell counter increments in the topbar (via `unreadNotificationsCount` shared prop)

**Page Visibility API**: sound only plays when the browser tab is **active**, preventing stacked alerts when the admin returns to the tab.

### Notification Bell & Panel

The topbar bell button shows unread count and opens `NotificationPanel.vue` — a slide-in drawer listing all admin notifications. Supports: mark single as read, mark all as read, delete single, delete all. Routes handled by `NotificationController` (`/notifications/*`). The `unreadNotificationsCount` is shared via `HandleInertiaRequests` so the badge stays in sync without extra API calls.

### Notifications table

Laravel's built-in `notifications` table (UUID primary key, polymorphic `notifiable`). Migration: `2026_05_22_162610_create_notifications_table.php`. Run `php artisan migrate` to apply.

---

## Live Master Tracking on Admin Map

The admin map (`/masters/map`) shows masters in real-time. When a master mobile app pings its location:

1. Master POSTs to `/api/v1/master/{id}/location`
2. Backend stores it and dispatches `MasterLocationUpdated` event
3. Event broadcasts to public channel `masters-map`
4. Admin's open map subscribes to that channel and animates the marker smoothly

### Basemap Rendering (`Pages/Masters/Map.vue`)

The base layer is rendered by **MapLibre GL** (GPU vector rendering — sharp at any zoom and on HiDPI), mounted into Leaflet via the `L.maplibreGL` bridge so all markers, popups, trajectories and Reverb channel code stay pure Leaflet.

- **Source**: a **self-hosted [tileserver-gl](https://github.com/maptiler/tileserver-gl)** instance serving the whole MapLibre stack (style + vector tiles + glyphs + sprites) from one origin. This is deliberate — public OSM tile servers are blocked in Turkmenistan, so the basemap must be self-hosted.
- **Style URL** is **not hardcoded**: `TILES_STYLE_URL` → `config('services.tiles.style_url')` → shared by `HandleInertiaRequests` as the `tilesStyleUrl` Inertia prop → read in the component via `usePage().props.tilesStyleUrl`.
- The component is renderer-agnostic about flavors now — tileserver serves a single style; to support dark mode / multiple styles, add them on the tileserver and switch the URL.
- `maplibre-gl` is a lazy chunk — `@maplibre/maplibre-gl-leaflet` (which pulls in `maplibre-gl`) is dynamically imported in `onMounted`, so it loads only on the map page.

Run a tileserver-gl instance (dev):

```bash
cd /path/to/openmaptiles   # dir holding your tiles.mbtiles
docker run --rm -it -v $(pwd)/data:/data -p 8080:8080 \
  maptiler/tileserver-gl --file tiles.mbtiles
# style:   http://localhost:8080/styles/basic-preview/style.json
# tilejson: http://localhost:8080/data/tiles.json
```

> **Production**: serve the tileserver over **HTTPS** behind nginx (e.g. `https://tiles.example.tm`) — an HTTP tile origin is blocked as mixed content on an HTTPS admin panel. tileserver-gl already sends permissive CORS headers. Set `TILES_STYLE_URL` per environment.

> The order-detail picker (`Pages/Orders/Partials/CreateOrderModal.vue`) still uses `protomaps-leaflet` + the offline `pmtiles` file — migrate it to the same tileserver style if needed.

> The order-detail picker (`Pages/Orders/Partials/CreateOrderModal.vue`) still uses `protomaps-leaflet` — migrate it the same way if pixel-crispness is needed there too.

### Per-Order Live Tracking (Orders → Show)

The order detail page (`/orders/{id}`) provides live tracking when a master is assigned and the order is `assigned` or `in_progress`:

- On mount, loads the master's trajectory polyline from `/orders/{id}/master-trajectory`
- Subscribes to `masters-map` via Reverb and moves the master marker in real-time
- Extends the trajectory polyline as new location events arrive
- Shows a live distance (Haversine) and ETA chip (assuming 60 km/h) with a pulsing dot
- Unsubscribes and cleans up on `onBeforeUnmount`

**Without Flutter app — for testing/demo**:
```bash
# Start Reverb in one terminal
php artisan reverb:start

# Simulate master movement in another terminal
php artisan master:simulate-movement 1 --interval=3 --steps=60
# Master 1 will broadcast a new location every 3 seconds for 3 minutes
```

Open `/masters/map` in the browser — the marker for master 1 will animate.

---

## API (Mobile Apps)

All Flutter mobile app communication uses the versioned REST API.

| Rule | Detail |
|---|---|
| Base path | `/api/v1/` |
| Controllers | `app/Http/Controllers/Api/V1/` |
| Auth | Laravel Sanctum — token-based, no sessions (planned for master endpoints) |
| Responses | Always via Eloquent API Resources |
| Routes | `routes/api/v1.php` |

Web (Inertia) and API controllers are **strictly separate**. Never reuse or share a controller between both.

**Flutter developer reference**: see [docs/MASTER_APP_SPEC.md](docs/MASTER_APP_SPEC.md) for the full Master mobile app technical specification — endpoints, WebSocket contracts, screen flow, and open questions.

### Currently implemented endpoints

**Master API**

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| `POST` | `/api/v1/master/{master}/location` | open (dev) | Master pings GPS location; broadcasts to admin map |

**Client API**

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| `POST` | `/api/v1/auth/request-otp` | — | Send OTP to phone number |
| `POST` | `/api/v1/auth/verify-otp` | — | Verify OTP, returns Sanctum token |
| `POST` | `/api/v1/auth/complete-registration` | Sanctum | Save name after first login |
| `POST` | `/api/v1/auth/logout` | Sanctum | Revoke current token |

### ⚠️ Breaking change — geography removed (v1)

The service now operates in **Ashgabat only**. Every geography concept (oblast → city, plus
the unused region directory) was dropped from the database, the admin panel and the API.

Mobile apps must be updated before this release ships:

| Removed | Replacement |
|---|---|
| `GET /api/v1/client/oblasts` | — (no directory; the city is implicitly Ashgabat) |
| `GET /api/v1/client/regions` | — |
| `GET /api/v1/client/cities` | — |
| `city_id` in `complete-registration`, `PATCH /client/me`, create/update order | Drop the field; the address is carried by `client_address` + `client_lat` / `client_lng` |
| `city` / `city_id` in client, profile, order and master responses | Removed from every resource |

The broadcast channel is now the single public `masters-map` (was `masters-map.{cityId}`).

**Full migration guide for mobile developers**: [docs/API_V1_GEOGRAPHY_REMOVAL.md](docs/API_V1_GEOGRAPHY_REMOVAL.md)
— before/after payloads, a per-app checklist, and the open questions to settle before release.

---

## Adding a New Feature

Follow this order every time — no skipping steps.

```
Step 1 — Database
    php artisan make:migration create_xxx_table
    php artisan make:model Xxx -f              # -f creates factory

Step 2 — Repository
    Create app/Repositories/XxxRepository.php

Step 3 — Business Logic
    Create app/Services/XxxService.php
    OR  app/Actions/CreateXxxAction.php

Step 4 — Controller
    php artisan make:controller XxxController  # thin — HTTP only

Step 5 — Validation & Response
    php artisan make:request StoreXxxRequest
    php artisan make:resource XxxResource

Step 6 — Vue Component
    Create resources/js/Pages/Xxx/Index.vue
    (dark mode + i18n, uses AdminLayout)

Step 7 — Tests
    php artisan make:test --phpunit XxxTest
    Cover: happy path + validation failure + edge cases

Step 8 — Translations
    Add keys to lang/ru/xxx.php AND lang/tk/xxx.php
    Add frontend keys to resources/js/i18n.js (both ru and tk)
```

---

## Adding a New Package or Service

When a new package or significant service is added:

1. **Update this README** — add to Tech Stack table, document usage
2. **Update `CLAUDE.md`** — add rules under the relevant section
3. **Add lang keys** if the package introduces any UI text
4. **Update `.env.example`** with any new required environment variables
5. After pulling: run `composer install` and/or `npm install`

---

## Code Style

### PHP — Laravel Pint

```bash
vendor/bin/pint --dirty    # Fix only changed files — run before every commit
vendor/bin/pint            # Fix entire codebase
```

### Static Analysis — PHPStan / Larastan (level 6)

```bash
vendor/bin/phpstan analyse
```

### Tests

```bash
php artisan test --compact                                     # All tests
php artisan test --compact tests/Feature/MasterTest.php       # Single file
php artisan test --compact --filter=it_creates_a_master     # Single test
```

Every feature, action, and model must have PHPUnit tests covering happy path, validation failure, and edge cases.

---

## Useful Commands

```bash
# ── Development ──────────────────────────────────────────────────────────────
composer run dev                  # Start Vite + Laravel server together
npm run dev                       # Vite only
npm run build                     # Production asset build
php artisan serve                 # Laravel dev server only

# ── Workers ──────────────────────────────────────────────────────────────────
php artisan queue:work            # Process queued jobs
php artisan reverb:start          # WebSocket server

# ── Database ─────────────────────────────────────────────────────────────────
php artisan migrate               # Run pending migrations
php artisan migrate --seed        # Migrate + seed
php artisan migrate:fresh --seed  # Drop all tables, migrate, seed
php artisan storage:link          # Symlink public/storage

# ── Inspection ────────────────────────────────────────────────────────────────
php artisan route:list --except-vendor          # All application routes
php artisan route:list --name=cities            # Filter by route name
php artisan config:show database                # Show database config

# ── Code Quality ─────────────────────────────────────────────────────────────
vendor/bin/pint --dirty           # Format changed PHP files
vendor/bin/phpstan analyse        # Static analysis

# ── Testing ──────────────────────────────────────────────────────────────────
php artisan test --compact        # Full test suite
```

---

## Git Conventions

| Prefix | When to use |
|---|---|
| `feat:` | New feature |
| `fix:` | Bug fix |
| `refactor:` | Code change with no behavior change |
| `docs:` | Documentation updates |
| `test:` | Adding or fixing tests |

Example: `feat: add master management with repository and PHPUnit tests`

---

## Deployment

The recommended deployment target is [Laravel Cloud](https://cloud.laravel.com/), which handles scaling, zero-downtime deploys, queue workers, and WebSocket servers automatically.

For any environment, before going live:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Ensure `APP_ENV=production`, `APP_DEBUG=false`, queue worker is running, and Reverb WebSocket server is running.
"# admin" 
