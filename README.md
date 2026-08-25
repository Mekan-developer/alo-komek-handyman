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
| Map Tiles | Self-hosted **tileserver-gl** — style + tiles + glyphs + sprites; URL via `TILES_STYLE_URL`. In dev: static `public/maps/style.json` + the `/tiles/{z}/{x}/{y}.pbf` route reading `storage/maps/tiles.mbtiles` |

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
├── icons/
│   ├── logo/                   # App logo (also used as favicon)
│   └── services/               # Category icons — preset set + `u-*.svg` admin uploads
├── maps/                       # MapLibre style.json, glyphs, sprites (self-hosted basemap)
└── sounds/
    └── alarm.mp3               # Admin panel alert sound

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

# 8. Queue worker (required for image uploads, jobs and realtime location broadcasts)
# `broadcasts` first: master location events must not queue behind photo conversion
php artisan queue:work --queue=broadcasts,default

# 9. WebSocket server (optional — realtime order alerts only)
php artisan reverb:start
```

> Reverb is **optional**: with the server down, orders, statuses and locations are still
> saved and the app keeps working — realtime events are just skipped. See
> [Realtime Fault Tolerance](#realtime-fault-tolerance).

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
BROADCAST_CONNECTION=resilient        # Fault-tolerant wrapper, see Realtime Fault Tolerance
BROADCAST_RESILIENT_CONNECTION=reverb # Connection it delegates to
BROADCAST_UNAVAILABLE_COOLDOWN=30     # Seconds to skip broadcasting after a failure
REVERB_CONNECT_TIMEOUT=2              # Guzzle connect timeout when publishing events
REVERB_TIMEOUT=5
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
4. Sound alert plays from `public/sounds/alarm.mp3`
5. Notification bell counter increments in the topbar (via `unreadNotificationsCount` shared prop)

**Page Visibility API**: sound only plays when the browser tab is **active**, preventing stacked alerts when the admin returns to the tab.

### Realtime Order Updates

Every status transition — from the admin panel, the master app (`start` / `complete`) or the client app
(`cancel`) — reaches open admin screens without a manual refresh:

1. `OrderStatusChanged` broadcasts on the public `orders` channel. Assigning a master goes through
   `AssignMasterAction`, which dispatches it too (Pending → Assigned is the one transition that skips
   `UpdateOrderStatusAction`); a reassignment keeps the status, so no event fires.
2. `NotifyAdminsOnOrderStatusChanged` writes an `OrderStatusChangedNotification` to the bell panel.
   It is **not** queued: the broadcast leaves before listeners run, and the UI reloads its unread
   counter right after — a queued write would land after that reload.
3. The browser refreshes data through `resources/js/composables/useOrdersRealtime.js`:

| Screen | Reloaded props |
|--------|----------------|
| `Orders/Index` | `orders` (filters and page stay in the URL) |
| `Orders/Show` | `order`, `eligibleMasters` — only for the order the event belongs to; the map is not rebuilt |
| `Dashboard` | `stats`, `ordersByStatus`, `recentOrders` |
| `AdminLayout` | `unreadNotificationsCount`, `pendingOtpCount` |

`scheduleRealtimeReload()` merges every subscriber's keys into one debounced `router.reload({ async: true })`
— events arrive in bursts (an assignment fires `master.assigned` + `order.status.changed`) and Inertia does
not merge parallel visits. Pages subscribe with `useOrdersChannel()` and detach via `stopListening`, never
`Echo.leave`: the `orders` channel is shared with `AdminLayout`.

Covered by `tests/Feature/OrderRealtimeTest.php`.

### Realtime Fault Tolerance

Realtime is a **delivery channel, not a source of truth** — every action persists to MySQL *before*
dispatching its event, so a dead WebSocket server never breaks a request:

```
POST /api/v1/orders → saved to MySQL ✅ → event dispatched
                                            ├─ Reverb up   → 🔔 realtime notification
                                            └─ Reverb down → warning in log, request still 200 ✅
```

Mechanics:

| Layer | Behaviour |
|-------|-----------|
| `BROADCAST_CONNECTION=resilient` | `App\Broadcasting\ResilientBroadcaster` wraps the real connection and swallows publish failures (logs a `warning`) instead of throwing `BroadcastException`. Channel authorization (`auth`) still throws — it must never silently pass. |
| Circuit breaker | After a failure, broadcasting is skipped for `BROADCAST_UNAVAILABLE_COOLDOWN` seconds (cache key `broadcast:unavailable:{connection}`), so requests don't each pay the connect timeout. Masters push their location every few seconds — this matters. |
| `REVERB_CONNECT_TIMEOUT` / `REVERB_TIMEOUT` | Replaces Laravel's 10s/30s Guzzle defaults, capping the worst-case wait on the first failing publish. |
| Frontend | `resources/js/echo.js` only instantiates Echo when `VITE_REVERB_APP_KEY` is set; consumers guard with `window.Echo?`, so pages fall back to plain Inertia requests. The topbar WS indicator (`AdminLayout.vue`) shows connection state. |

Covered by `tests/Feature/ResilientBroadcasterTest.php`.

#### Broadcast queues

| Event | Contract | Queue | Why |
|-------|----------|-------|-----|
| `MasterLocationUpdated` | `ShouldBroadcast` | `broadcasts` (`tries = 1`) | High frequency — the master app posts coordinates every few seconds, so the request must not wait on Reverb. A retry is pointless: the next coordinate is already on its way. |
| `OrderCreated` | `ShouldBroadcast` | `default` | Fires once per order; admin alert can tolerate queue latency. |
| `OrderStatusChanged`, `MasterAssigned` | `ShouldBroadcastNow` | — | Rare, user-facing state changes where immediacy beats the queue hop. Safe because the resilient driver swallows failures. |

The worker **must** listen to both queues, `broadcasts` first, or location events will never leave the
table while photo conversion occupies `default`:

```bash
php artisan queue:work --queue=broadcasts,default --sleep=1
```

`--sleep=1` keeps map tracking responsive when the queue drains (default is 3s).
`after_commit => true` in `config/queue.php` guarantees jobs and broadcasts dispatched inside a
`DB::transaction()` (e.g. `CreateClientOrderAction`) are only pushed once the transaction commits.

### Notification Bell & Panel

The topbar bell button shows unread count and opens `NotificationPanel.vue` — a slide-in drawer listing all admin notifications. Supports: mark single as read, mark all as read, delete single, delete all. Routes handled by `NotificationController` (`/notifications/*`). The `unreadNotificationsCount` is shared via `HandleInertiaRequests` so the badge stays in sync without extra API calls.

### Notifications table

Laravel's built-in `notifications` table (UUID primary key, polymorphic `notifiable`). Migration: `2026_05_22_162610_create_notifications_table.php`. Run `php artisan migrate` to apply.

---

## Live Master Tracking on Admin Map

The admin map (`/masters/map`) shows masters in real-time. When a master mobile app pings its location:

1. Master POSTs to `/api/v1/master/{id}/location`
2. Backend stores it and dispatches `MasterLocationUpdated` — queued on `broadcasts`, so the mobile
   request returns immediately (see [Broadcast queues](#broadcast-queues))
3. The queue worker broadcasts to public channel `masters-map`
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

> The order-detail picker (`Pages/Orders/Partials/CreateOrderModal.vue`) and the order tracking map (`Pages/Orders/Show.vue`) use the same `@maplibre/maplibre-gl-leaflet` bridge and the same `TILES_STYLE_URL` — there is no second tile stack.

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

# Queue worker in a second terminal — location broadcasts are queued now
php artisan queue:work --queue=broadcasts,default --sleep=1

# Simulate master movement in a third terminal
php artisan master:simulate-movement 1 --interval=3 --steps=60
# Master 1 will broadcast a new location every 3 seconds for 3 minutes
```

> Without the worker the coordinates are still written to `master_locations`, but the map won't move
> — the broadcast jobs just sit in the `jobs` table.

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

### Order pricing — per-task prices + discount

An order's `final_price` is **derived**, not entered by hand: it is the sum of the prices of
its tasks (`order_tasks.price`), minus the order discount (`orders.discount_percent`).

- The admin sets a price on each task inline on `/orders/{id}` →
  `POST /orders/{order}/tasks/{task}/price` (`SetOrderTaskPriceAction`).
- [`OrderTaskObserver`](app/Observers/OrderTaskObserver.php) recalculates
  `orders.final_price` via `OrderRepository::syncFinalPriceFromTasks()` whenever a task price
  is created, changed or the task is deleted.
- Tasks without a price are ignored for the subtotal. When no task is priced, `final_price` is
  reset to `null`.
- An order can only move to `Completed` once **every** one of its tasks has a price (an order
  with zero tasks counts as unpriced too) — `UpdateOrderStatusAction` throws
  `OrderException::unpricedTasks()` otherwise. This applies to both the admin status-change
  flow (`POST /orders/{order}/status`) and the master's
  `POST /api/v1/master/orders/{order}/complete`.
- Prices are locked once the order reaches a final status — the master's balance has already
  been credited from `final_price` at completion.
- Masters and clients receive `price` on every task (read-only) in
  `MasterTaskResource` and `ClientOrderResource`.

**Discount.** `orders.discount_percent` (0–100, default `0`) is a percentage taken off the
tasks subtotal.

- The admin edits it inline on `/orders/{id}` → `POST /orders/{order}/discount`
  (`SetOrderDiscountAction`), blocked once the order is in a final status.
- `final_price = subtotal − round(subtotal × percent / 100, 2)`. The subtotal itself is never
  stored; `Order::tasksTotal()` / `Order::discountAmount()` derive it from the loaded tasks for
  display, `OrderRepository::tasksSubtotal()` from the database for the recalculation.
- Because the discount lands in `final_price`, `CreditMasterBalanceAction` credits percentage
  masters from the **discounted** total.
- Resources expose `discount_percent`, `tasks_total` and `discount_amount` (`OrderResource`,
  `ClientOrderResource`; the master resource gets `discount_percent` + `tasks_total`).

> The manual "set final price" flow (`POST /orders/{order}/price`, `SetOrderFinalPriceAction`,
> `SetPriceModal.vue`) has been removed.

### Master call-out fee notice

Free-text notice telling the client they owe the master for the call-out when they cancel an
order **after a master has already been assigned and is on the way**. No amount is stored —
the wording (and any figure inside it) is entirely the admin's.

- Stored as two rows in `settings` — `master_call_out_fee_note_ru` and
  `master_call_out_fee_note_tk` (`nullable|string|max:500`) — edited in the **Отмена заказа**
  card on `/settings`.
- Exposed to the client app by `GET /api/v1/client/settings` as
  `data.master_call_out_fee_note`: plain text already resolved for the request's `X-Locale`,
  falling back to the Russian variant, `""` when nothing is written. The app shows it on the
  cancel-confirmation screen.
- **Informational only** — the money changes hands in cash on site. Nothing is written to the
  order, and the master's `balance` is untouched. `CancelClientOrderAction` still refuses to
  cancel an order that already has a master (`OrderException::cannotCancelAssignedOrder()`),
  so today that cancellation goes through an operator in the admin panel.
- The Settings page saves these fields with their own Inertia form, so saving the notice never
  overwrites the rules editors (`UpdateSettingsAction` only writes the keys present in the
  request).

### Order receipts

Every order gets a receipt the moment it is completed — a till-roll style document listing
the client, the master who did the job, one line per priced task, and the total on the last
line.

- `IssueOrderReceiptAction` is called from `UpdateOrderStatusAction` on the transition to
  `Completed`. **Never call it by hand** — it is idempotent and returns the existing receipt.
- The receipt is a **snapshot**, not a view: `order_receipts` stores client/master names and
  phones, the category, `subtotal`, `discount_percent`, `discount_amount` and `total`;
  `order_receipt_items` stores one row per task (`title`, `description`, `price`). Task prices
  are locked at completion anyway, so the receipt never drifts.
- Tasks **without a price** are left out of the snapshot. Since completion itself is now
  blocked unless every task is priced (see "Order pricing" above), a receipt with zero items
  can only happen for orders completed before that rule shipped, backfilled via
  `receipts:backfill`.
- Number format is `MDD-NNNN`: month, day, then a per-day counter — `824-0036` is the 36th
  receipt issued on 24 August. Generated by `OrderReceiptRepository::nextNumberFor()`.
- Orders completed before this feature shipped have no receipt. Backfill them once with
  `php artisan receipts:backfill` (safe to re-run).

**Where it shows up**

| Surface | How |
|---|---|
| Admin | `Orders/Show` gets a `receipt` prop; the "Чек" button opens `OrderReceiptModal.vue`, "Распечатать" calls `window.print()` |
| Client app | `GET /api/v1/client/orders/{order}/receipt` |
| Master app | `GET /api/v1/master/orders/{order}/receipt` |

Both endpoints return `OrderReceiptResource` (shared between web and API — the snapshot is
identical everywhere; labels are localized client-side) and answer `404` until the order is
completed.

Printing is the one place with hand-written CSS: `resources/css/app.css` has an `@media print`
block that hides everything except `.receipt-print`. Tailwind's `print:` variants cannot
express "hide the rest of the page". The receipt itself is always light — it is a sheet of
paper, not a UI surface — so it has no `dark:` variants by design.

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

## OTP Delivery & Manual Fallback

OTP codes are generated by `DispatchOtpAction` and pushed to the Flutter SMS-gateway phone through the Socket.IO bridge (`OtpGatewayService`).

**When the gateway is unreachable, login is no longer blocked.** Instead:

1. The code is still written to cache, so `verify-otp` accepts it as usual.
2. A row is parked in `pending_otps` (`PendingOtpRepository::replaceForPhone()` — only the latest code per phone survives).
3. `request-otp` answers `200` with `delivery: "manual"` and a localized `delivery_message` telling the caller to phone support.
4. The **OTP-коды** sidebar section (`Pages/PendingOtps/Index.vue`) and the dashboard panel both render `Components/PendingOtpPanel.vue`, which polls `GET /pending-otps/data` every 10s and shows the code, phone, recipient and a live countdown, so an administrator or manager can dictate it. `DELETE /pending-otps/{id}` dismisses a delivered code; expired rows are purged on every poll.
5. Parking a code fires `PendingOtpCreated` — a **queued** broadcast (`ShouldBroadcast`, not `ShouldBroadcastNow`) on the private `admin.pending-otps` channel, so the caller's login request never waits on Reverb. Open panels prepend the code instantly; the 30s poll is the fallback if the worker or Reverb is down.
6. The sidebar item carries an amber badge fed by the `pendingOtpCount` shared prop (`HandleInertiaRequests`) and refreshed on broadcast via `router.reload({ only: ['pendingOtpCount'] })`, plus a toast and alarm sound from `AdminLayout`.

> The broadcast requires a running queue worker (`php artisan queue:work`) — the DB row is written synchronously, so nothing is lost if the worker is down, the code just surfaces on the next poll instead of instantly.

Codes live only as long as `OTP_TTL_MINUTES`. The routes sit behind `auth` + `role:administrator,manager` — operators never see them.

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
php artisan queue:work --queue=broadcasts,default   # Process queued jobs (see Queues)
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

## Roadmap / Known Gaps

Tracked here so the list stays next to the code it describes.

| # | Task | Priority |
|---|---|---|
| 1 | **`/docs` is unprotected** — `ProtectScribeDocs` exists but is wired nowhere (`config/scribe.php` was never published, so `route:list` shows an empty middleware stack on `docs`, `docs.openapi`, `docs.postman`). Publish the config and register the middleware. | High |
| 2 | **Private `orders` + `masters-map.*` channels** + admin gate — mobile `client.*` / `master.*` are already private, admin channels are not (see the note in `routes/channels.php`) | Low |
| 3 | **Auth for the location ping** — `POST /api/v1/master/{master}/location` is still open ("temporary open auth until OTP flow stabilises") | Low |
| 4 | **`OrderStatus` enum location** — lives in `app/OrderStatus.php` instead of `app/Enums/` alongside `UserRole` / `CategoryIconType`; `PaymentModel` has the same problem | Low |
| 5 | **Dashboard tests** — no coverage for `DashboardController` / `DashboardRepository` | Low |
| 6 | **Notification tests** — no coverage for `NotificationController` | Low |
| 7 | **Policies for the remaining modules** — only `UserPolicy` exists | Low |
| 8 | **Flutter apps** — the API is ready, the master/client clients are not built | — |

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

Ensure `APP_ENV=production`, `APP_DEBUG=false`, and Reverb WebSocket server is running. The queue
worker must be started as `queue:work --queue=broadcasts,default` — a plain `queue:work` only drains
`default` and live map tracking would stop.
"# admin" 
