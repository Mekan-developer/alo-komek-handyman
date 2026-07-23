# ARCHITECTURE

Архитектура проекта **Handyman** — админ-панель + мобильный API для маркетплейса бытовых услуг.

---

## Стек

### Backend
- **PHP 8.3** / **Laravel 11** (новая структура без `Http/Kernel.php` — всё в `bootstrap/app.php`)
- **Inertia.js v2** (`inertiajs/inertia-laravel`) — server-side рендеринг SPA
- **Laravel Sanctum v4** — токен-аутентификация для мобильного API
- **Laravel Reverb v1** — WebSocket-сервер для broadcasting
- **Ziggy v2** (`tightenco/ziggy`) — named routes на фронте
- **SQLite** (по `.env`), сессии/кэш/queue хранятся в БД (`database` driver)

### Frontend
- **Vue 3** (Composition API, `<script setup>`)
- **@inertiajs/vue3 v2**
- **Pinia v3** — state management
- **vue-i18n v11** — переводы (синхронизируются с `lang/{ru,tk}/*.php` через Inertia shared prop)
- **Tailwind CSS v3** + `@tailwindcss/forms`
- **Leaflet** + `@vue-leaflet/vue-leaflet` — карты
- **laravel-echo** + `pusher-js` — клиент Reverb

### Dev / Quality
- **Laravel Pint** (форматирование PHP)
- **PHPUnit 10** (тесты)
- **Laravel Boost v2** (MCP-сервер, активирован в IDE)
- **Larastan / phpstan** (статический анализ)
- **Laravel IDE Helper**, **Laravel Breeze** (auth scaffolding)

---

## Структура папок

```
app/
├── Actions/                # Одно действие = один класс. handle() возвращает результат или бросает Exception
├── Console/Commands/       # SimulateMasterMovement — эмуляция GPS
├── Events/                 # OrderCreated, MasterLocationUpdated (ShouldBroadcastNow)
├── Exceptions/             # OrderException, OtpException — типизированные доменные ошибки
├── Http/
│   ├── Controllers/        # Тонкие контроллеры (Web: Inertia ответы)
│   │   ├── Api/V1/         # Мобильный API контроллеры (отдельная иерархия)
│   │   └── Auth/           # Breeze
│   ├── Middleware/         # HandleInertiaRequests (shared props + переводы)
│   ├── Requests/           # FormRequest классы (валидация)
│   │   └── Api/V1/         # API request'ы
│   ├── Resources/          # API Resources (Web и Api/V1)
│   └── Traits/             # WithNotification
├── Jobs/                   # ConvertOrderPhotoJob, ConvertTaskPhotoJob (ShouldQueue)
├── Models/                 # Eloquent
├── Providers/              # AppServiceProvider
├── Repositories/           # ВСЕ запросы к БД — здесь, не в контроллерах
├── Support/                # PhotoConverter (чистый PHP/GD)
├── OrderStatus.php         # enum (в корне app/)
└── PaymentModel.php        # enum

resources/js/
├── Components/             # Атомарные компоненты (PrimaryButton, Modal, InputLabel, ...)
├── Composables/            # (пусто на данный момент)
├── Layouts/                # AdminLayout, AuthenticatedLayout, GuestLayout
├── Pages/                  # Inertia-страницы по доменам: Cities, Categories, Masters, Orders, Clients, Payments, Profile, Auth, Dashboard
├── stores/                 # Pinia: useThemeStore, useLocaleStore, useNotificationStore
├── app.js                  # Точка входа, монтаж Inertia + i18n + Pinia + Ziggy
├── bootstrap.js            # Глобальный axios
├── echo.js                 # Laravel Echo (Reverb)
└── i18n.js                 # vue-i18n инстанс (messages подгружаются из shared props)

lang/{ru,tk}/               # Source of truth для всех UI-строк, грузятся в Vue через Inertia
routes/
├── web.php                 # Inertia routes
├── api/v1.php              # Mobile API
├── auth.php                # Breeze
├── channels.php            # Broadcast channels
└── console.php
```

---

## Ключевые модули

### 1. Cities
Простая иерархия CRUD: `Migration → Model → Factory → Repository → Action × 3 (Create/Update/Delete) → FormRequest × 2 → Controller → Resource → Vue Index + FormModal`.

### 2. Categories
То же что Cities + self-referencing `parent_id` (древовидная структура), методы `parent()`, `children()`, `isRoot()`.

### 3. Masters
- Мастер привязан к одному `City` и многим `Category` (pivot `category_master`)
- `PaymentModel` enum определяет схему расчёта зарплаты
- `balance` поле — для накопления заработка, обнуляется `ResetMasterBalanceAction`
- `is_active` + `access_expires_at` — контроль доступа (`hasActiveAccess()`)
- Геолокация — `MasterLocation` модель, `latestLocation` через `hasOne()->latestOfMany()`
- Реализована карта `Masters/Map.vue` с Leaflet, real-time через канал `masters-map.{cityId}`

### 4. Orders
**Центральный домен.** State machine через `OrderStatus`:

```
Pending → Assigned → InProgress → Completed
   ↓        ↓           ↓
       Cancelled (терминальное)
```

Логика переходов — в [UpdateOrderStatusAction::isValidTransition()](app/Actions/UpdateOrderStatusAction.php).

При создании заказа:
- `CreateOrderAction` пишет в БД в транзакции, сохраняет фото в `storage/app/public/orders/{id}/problem/`, диспатчит `ConvertOrderPhotoJob` для каждого
- Бросает event `OrderCreated` → канал `orders` → AdminLayout слышит → toast + beep
- При `Completed` — `CreditMasterBalanceAction` начисляет заработок по `PaymentModel`

`AssignMasterAction` валидирует:
- Заказ не финальный
- Мастер активен и доступ не истёк
- `master.city_id === order.city_id`
- Категория мастера включает категорию заказа

### 5. Master Mobile API (v1)
Авторизация OTP → Sanctum:
1. `POST /api/v1/master/auth/request-otp { phone }` — `RequestMasterOtpAction` генерирует 6-значный код, отправляет через `OtpGatewayService` (Socket.IO мост `socket-server/` → Flutter SMS-gateway телефон), кладёт в Cache на 3 мин
2. `POST /api/v1/master/auth/verify-otp { phone, code }` — `VerifyMasterOtpAction` сверяет и возвращает Sanctum token (`name=mobile`)
3. Все защищённые роуты под `auth:sanctum`

Эндпоинты:
- `GET /master/me` — профиль
- `GET /master/orders?filter=active|history`
- `GET/POST /master/orders/{id}/start|complete`
- `POST /master/orders/{order}/tasks` + `POST /master/orders/{order}/tasks/{task}/photo` (type=before|after)
- `POST /master/{master}/location` — публичный (пока без auth, см. комментарий в роутах)

### 6. Photo Pipeline
1. Файл загружается → `OrderPhoto`/`OrderTask` со статусом `pending`
2. `ConvertOrderPhotoJob` / `ConvertTaskPhotoJob` (tries=3, backoff=30) ставит `converting`
3. `PhotoConverter::convert()` — GD конвертирует в WebP, при height > 1800 px скейлит, quality 85
4. Старый файл удаляется, путь и статус `done` пишутся в БД
5. На исключении — статус `failed` + throw

### 7. Broadcasting (Reverb)
| Event | Channel | Кто слушает |
|---|---|---|
| `OrderCreated` | `orders` (public) | AdminLayout (real-time toast + звук нового заказа) |
| `MasterLocationUpdated` | `masters-map.{cityId}` (public) | `Masters/Map.vue` и `Orders/Show.vue` (живые точки) |

`broadcastAs()` задаёт alias, на фронте подписка через `.order.created`.

### 8. Notifications (UI)
- Backend: `WithNotification` trait → `session()->flash('notification', ...)`
- Inertia share → prop `notification`
- AdminLayout watch'ит prop и пушит в `useNotificationStore` (Pinia)
- Тосты автоматически уходят через 6 сек
- Все ключи — из `lang/{ru,tk}/notifications.php`, ресурсы — из `resources.php`

### 9. Локализация
- **Source of truth**: PHP-файлы в `lang/{ru,tk}/`
- `HandleInertiaRequests::loadTranslations()` собирает всё в bundle, кэширует на production
- Bundle отправляется в shared prop `translations`
- `app.js` применяет в `i18n.global.setLocaleMessage()` при первом mount и на каждом `router.on('success')` — горячая перезагрузка
- Текущий локаль — в Pinia store + LocalStorage (`useLocaleStore`)

---

## Связи модулей

```
              ┌────────────────────┐
              │  AdminLayout.vue   │ ←— shared props (auth, locale, notification, translations)
              │  (+ Echo: orders)  │
              └────────┬───────────┘
                       │
       ┌───────────────┼─────────────────────────────┐
       ▼               ▼                             ▼
  Pages/Cities    Pages/Orders                  Pages/Masters
                  Index.vue                      Index.vue
                  Show.vue ──── Echo: masters-map.{cityId} ──── Map.vue
                       │
                       │ Inertia POST/GET (Ziggy route())
                       ▼
              OrderController (web)
                       │ delegate
                       ▼
              Actions (CreateOrderAction, AssignMasterAction, UpdateOrderStatusAction, ...)
                       │
       ┌───────────────┼─────────────────────────────┐
       ▼               ▼                             ▼
  OrderRepository  Events (OrderCreated)        Jobs (ConvertOrderPhotoJob)
       │                                              │
       ▼                                              ▼
   Eloquent (Order, Master, ...)             PhotoConverter (GD)
```

Мобильное приложение (Flutter, ещё не написано):
```
Flutter App
    │ HTTPS + Bearer token
    ▼
api/v1/master/*  →  Api\V1 Controllers  →  Actions  →  Repositories  →  Eloquent
                                            │
                                            └──→ MasterLocationUpdated → Reverb → Admin Map
```

---

## Соглашения проекта (из CLAUDE.md)

- **Thin Controllers**: контроллер делает только HTTP, всю логику — в Actions/Services
- **Repository pattern**: ВСЕ Eloquent-запросы — в `Repositories/`, нет Eloquent в Service/Controller
- **Actions**: одно действие на класс, метод `handle()`, бросают типизированные exception'ы вместо возврата false
- **Validation**: только FormRequest, никогда `$request->all()` и `validate()` в контроллере
- **Resources**: всегда `JsonResource`, никогда raw модель
- **Enums** для статусов (`OrderStatus`, `PaymentModel`), никаких строковых констант
- **Background**: фото-конвертация через Queue (`database` driver)
- **Notifications**: только через `WithNotification` trait, ключи переводов
- **Frontend**: Composition API `<script setup>`, Pinia для shared state, Tailwind с `dark:`, vue-i18n
- **Тесты**: Feature на каждый Action / эндпоинт
- **Modal backdrops**: `backdrop-blur-md bg-black/40`
- **API**: только версионированный (`routes/api/v1.php`, префикс `/api/v1/`)

---

## Окружение и сервисы

- **Timezone**: `Asia/Ashgabat`
- **APP_LOCALE**: `en` в .env, но HandleInertiaRequests дефолтит на `ru` для UI
- **Queue**: `database`
- **Cache / Session**: `database`
- **Filesystem**: `local` (фото — на `public` диске)
- **Broadcast**: `reverb` (хост `127.0.0.1:8080`)
- **Mail**: `log` (для разработки)

## Скрипты

- `composer run dev` — обычно поднимает несколько процессов
- `npm run dev` / `npm run build` (Vite)
- `php artisan master:simulate-movement` — эмуляция GPS-ping'ов без Flutter
- `php artisan test --compact` — тесты
- `vendor/bin/pint --dirty --format agent` — формат

## Тестовое API (Bruno)

Коллекция в [bruno/](bruno/): 01_auth, 02_location, 03_profile, 04_orders.
