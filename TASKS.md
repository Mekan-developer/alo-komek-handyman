# TASKS

Актуальное состояние задач по проекту (обновлено 2026-06-20).

---

## ✅ Выполнено

### Инфраструктура и фундамент
- Laravel 11 + Inertia v2 + Vue 3 + Tailwind v3
- Laravel Breeze (auth scaffolding)
- Pinia stores: `useThemeStore`, `useLocaleStore`, `useNotificationStore`
- vue-i18n: переводы из `lang/{ru,tk}/*.php` через Inertia shared prop
- Dark/Light тема через Pinia + LocalStorage
- `WithNotification` trait + автоподхват flash в AdminLayout
- Ziggy v2, Laravel Sanctum, Laravel Reverb + Echo
- Единый API error handler в `bootstrap/app.php` → локализованный JSON по заголовку locale
- Scribe — автодокументация API на `/docs`, защищена `ProtectScribeDocs` middleware
- Мониторинг системы: `SystemStatusController` (`GET /system-status`) + queue heartbeat через `Queue::looping()` в `AppServiceProvider` (зависит только от `queue:work`, не от планировщика), индикатор queue/reverb/websocket в сайдбаре AdminLayout

### Admin Panel — CRUD-модули (все полностью реализованы)

| Модуль | Маршруты | Vue | Тест |
|--------|---------|-----|------|
| Oblasts | ✅ CRUD | ✅ Index + Modal | ✅ OblastTest |
| Regions | ✅ CRUD | ✅ Index + Modal | ✅ RegionTest |
| Cities | ✅ CRUD | ✅ Index + Modal | ✅ CityTest |
| Categories | ✅ CRUD + иконки (preset/custom SVG) | ✅ Index + Modal + IconPicker | ✅ CategoryTest |
| CategoryContent | ✅ upsert + неск. изображений | ✅ CategoryContentModal | ✅ CategoryContentTest |
| Masters | ✅ CRUD + Map + Trajectory + Balance + availability + monthly_salary | ✅ Index + Map | ✅ MasterTest, MasterAvailabilityTest |
| Orders | ✅ index/show/store/update/destroy/assign/setPrice/updateStatus/masterTrajectory | ✅ Index + Show + EditOrderModal | ✅ OrderTest |
| Clients | ✅ CRUD + toggleBlock | ✅ Index + Modal | ✅ ClientTest |
| Banners | ✅ CRUD + toggle | ✅ Index + Modal | ✅ BannerTest |
| Notifications | ✅ read/readAll/delete/deleteAll | ✅ есть в AdminLayout | ❌ нет |
| Dashboard | ✅ реальные данные через DashboardRepository | ✅ 4 stat-карточки | ❌ нет |
| Payments | ✅ баланс мастеров + выплаты (RecordMasterPayout) + история + stats | ✅ Index (реальный, не заглушка) | ✅ PaymentTest |
| Users | ✅ CRUD + роли (Administrator/Manager/Operator) | ✅ Index + Modal | ✅ UserTest |

### Payments (выплаты мастерам)
- Модель `MasterPayout` + миграция `master_payouts`
- `PaymentRepository` (`history()`, `totalPaid()`), `MasterRepository` (`withOutstandingBalance()`, `totalOutstandingBalance()`, `countWithOutstandingBalance()`)
- `RecordMasterPayoutAction` (частичная/полная выплата), `PaymentException`
- `PaymentController` (index + payout), `PayoutMasterRequest`, `MasterPayoutResource`
- `Payments/Index.vue`: stat-карточки, список мастеров с балансом, модалка выплаты, история выплат
- `payments.payout` — только administrator/manager; `payments.index` — все роли

### Mobile API (api/v1)

#### Master API
- OTP auth: request-otp (через SMS-шлюз, 3 мин TTL), verify-otp, logout
- GET /api/v1/master/me
- PATCH /api/v1/master/availability (MasterAvailabilityController)
- Orders: index, show, start, complete
- Tasks: store + photo upload (WebP фоновая конвертация, OrderTaskPhoto — до 2 фото/тип)
- Location: POST /api/v1/master/{master}/location

#### Client API
- OTP auth: request-otp (через SMS-шлюз, 3 мин TTL), verify-otp, logout, complete-registration
- GET/PATCH /api/v1/client/me (ClientProfileController)
- Catalog: GET oblasts, regions, cities, categories, categories/search, category content, banners
- Orders: index, show, store, cancel

#### Broadcasting
- Приватные каналы `client.{clientId}` / `master.{masterId}` (Sanctum) — `master.assigned`, `order.status.changed`
- Broadcast auth для мобилы: `POST /api/v1/broadcasting/auth`

### Backend Infrastructure
- Модели: User (с ролями), Oblast, Region, City, Category, CategoryContent, CategoryContentImage, Master, MasterLocation, MasterPayout, Order, OrderTask, OrderTaskPhoto, OrderPhoto, Client, Banner
- Enums: `OrderStatus` (`app/OrderStatus.php`), `PaymentModel` (`app/PaymentModel.php`), `UserRole` (`app/Enums/UserRole.php`), `CategoryIconType` (`app/Enums/CategoryIconType.php`)
- Exceptions: OrderException, OtpException, MasterDisabledException, PaymentException, ApiException
- Events: OrderCreated, OrderStatusChanged, MasterAssigned, MasterLocationUpdated
- Jobs: ConvertOrderPhotoJob, ConvertTaskPhotoJob (WebP, tries=3, backoff=30); queue heartbeat — через `Queue::looping()` в `AppServiceProvider`, не отдельный job
- Repositories: Oblast, Region, City, Category, CategoryContent, Master, Order, Client, Banner, Dashboard, User, Payment
- Services: `OtpGatewayService` — отправка OTP через `socket-server/` (Node.js Socket.IO мост) на Flutter SMS-gateway телефон
- Actions: полный набор для всех модулей (48 классов, включая User CRUD, ToggleMasterAvailability, RecordMasterPayout)
- Policies: `UserPolicy` (`app/Policies/`)
- Middleware: `CheckRole` (`role:`), `EnsureMaster`, `EnsureClient`, `ProtectScribeDocs`, `SetLocale`, `HandleInertiaRequests`
- Resources: Admin + API V1 (Master + Client) + UserResource + MasterPayoutResource
- Support: `PhotoConverter`, `CategoryIcon`; `config/service_icons.php` — preset-набор иконок
- Lang: `lang/{ru,tk}/` — api, auth, banners, categories, cities, clients, dashboard, layout, masters, notifications, oblasts, orders, payments, profile, regions, resources, users, validation (18 файлов каждый)

### UI компоненты
- PhoneInput.vue — поле телефона с +993, форматом `XX XX-XX-XX`; `formatPhone` утилита
- OblastCitySelect.vue — каскадный выбор области → город (формы заказов/мастеров)
- ServiceIcon.vue — рендер иконки категории (CSS mask preset / custom SVG), IconPicker.vue — выбор preset
- Карты: MapLibre GL (мост `L.maplibreGL`) + self-hosted tileserver / pmtiles + protomaps-leaflet

### Тесты (Feature)
- ✅ OblastTest, RegionTest, CityTest, CategoryTest, CategoryContentTest
- ✅ MasterTest, MasterAvailabilityTest, OrderTest, BannerTest, ClientTest, UserTest, PaymentTest
- ✅ CreditMasterBalanceActionTest, ConvertOrderPhotoJobTest, PhotoConverterTest, UploadTaskPhotoTest
- ✅ MasterLocationApiTest, MasterAuthTest
- ✅ ClientOrderCancelTest, ClientCatalogCategoryContentTest, ClientCatalogSearchTest
- ✅ ApiExceptionHandlingTest, SystemStatusTest

---

## 🚧 Текущая незакоммиченная работа

- Полировка сайдбара AdminLayout (индикатор статуса системы) + ключи `lang/{ru,tk}/layout.php` — 3 файла в working tree

---

## ❌ Не сделано / TODO

| # | Задача | Приоритет |
|---|--------|-----------|
| 2 | **Приватные `orders` + `masters-map.*` каналы** + admin gate (мобильные `client.*`/`master.*` уже приватные) | Низкий |
| 3 | **Auth для location ping** — `/master/{master}/location` без auth | Низкий |
| 4 | **OrderStatus enum location** — лежит в `app/OrderStatus.php`, а не `app/Enums/` (как UserRole/CategoryIconType) | Низкий |
| 5 | **Dashboard тесты** — нет тестов для DashboardController/Repository | Низкий |
| 6 | **Notification тесты** — нет тестов для NotificationController | Низкий |
| 7 | **Policies для остальных модулей** — есть только `UserPolicy` | Низкий |
| 8 | **Flutter-приложение** — API готов, мобильного клиента нет | — |

---

## Полезные команды

```bash
php artisan route:list --except-vendor
php artisan test --compact
php artisan queue:work             # фото в WebP + queue heartbeat (статус очереди в сайдбаре)
php artisan scribe:generate        # API-документация
vendor/bin/pint --dirty --format agent
npm run dev
php artisan master:simulate-movement
```
