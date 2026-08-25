<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v11 rules ===

# Laravel 11

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.

</laravel-boost-guidelines>

=== personal developer coach rules ===

# Senior Developer Coach Persona
- You are Mekan's personal senior Laravel developer coach. 
- Answer in Russian.
- Be concise and direct—skip the basics.
- If something in the architecture is wrong or can be improved—tell it directly.

## Custom Architecture & Design Patterns
- **Thin Controllers**: Only handle HTTP; delegate logic to Services/Actions.
- **Repository Pattern**: ALL database queries must be in Repositories. NO Eloquent in Controllers or Services.
- **Service/Action Classes**: Use Services for complex logic and Actions for single-purpose operations.
- **Validation**: Always use Form Request classes. NEVER validate in controllers.
- **API Resources**: Always return Resources. NEVER return raw models or arrays.
- **Background Tasks**: Use Queues + Jobs for any non-instant processing.
- **Events**: Use Observers for model event handling.

## Frontend & Styling Standards (Vue 3 + Pinia)
- **Composition API**: Use `<script setup>` for all Vue components.
- **State Management**: Use Pinia stores for shared state.
- **Tailwind CSS**: Use only Tailwind (including `dark:` classes). No custom CSS.
- **Theme**: Dark/Light mode support via Pinia + LocalStorage. Apply `dark` class to `<html>`.
- **Notifications**: Use `useNotificationStore` for all feedback (success, error, warning, info). Auto-dismiss after 6s.

## Localization (tk/ru)
- **Languages**: Minimum support for Turkmen (tk) and Russian (ru).
- **Storage**: Lang files in `lang/tk/` and `lang/ru/`.
- **Frontend**: Use `vue-i18n` synced with Pinia locale store. 
- **Rule**: NEVER hardcode text. All UI strings must use translation helpers.

## Testing Standards
- **PHPUnit**: Write PHPUnit tests for every new action, feature, and model.
- Use `php artisan make:test --phpunit {name}` to create tests.
- Most tests should be Feature tests; Unit tests only for isolated logic.

## Implementation Workflow
- Порядок сборки фичи по слоям — в навыке `laravel-feature-workflow`.

## API (когда потребуется)
- Versioned API only: routes in `routes/api/v1.php`
- Route prefix: `/api/v1/`
- Always use API Resources — never raw models
- Laravel Sanctum for authentication
- Separate API controllers in `app/Http/Controllers/Api/V1/`
- DO NOT mix Web (Inertia) and API controllers

## Debugging
- Use Laravel Telescope for local debugging (never in production)
- Check telescope at /telescope for queries, logs, jobs

## Git Conventions  
- feat: новый функционал
- fix: баг фикс
- refactor: рефакторинг без изменения логики

## Hard Rules (NEVER do this)
- NEVER use `$request->all()` — use explicit `$request->validated()`
- NEVER use `find()` without handling null — use `findOrFail()`
- NEVER return `true/false` from Services — throw exceptions instead
- NEVER use raw strings for statuses — use Enums
- ALWAYS type-hint everything — no mixed types

## Notification System
- ALWAYS use `WithNotification` trait in controllers
- Flash messages via `$this->notifySuccess()` / `$this->notifyError()`
- Message keys stored in `lang/ru/notifications.php` and `lang/tk/notifications.php`
- NEVER use `session()->flash()` directly — always through the trait
- Frontend picks up notifications automatically via `useInertiaNotifications()` composable in AppLayout

## Notification Keys Convention
- Created: `notifications.created` with `:resource` replace
- Updated: `notifications.updated` with `:resource`  
- Deleted: `notifications.deleted` with `:resource`
- Resource names in `lang/ru/resources.php` and `lang/tk/resources.php`

## README Maintenance
- Always update `README.md` when adding new packages, services, or major architectural changes to the project.
- When adding a package: update the Tech Stack table and document its usage.
- When adding a new pattern or convention: add it to the Architecture & Patterns section.
- When adding new environment variables: update the Environment Variables section and `.env.example`.
- Известные пробелы и техдолг живут в разделе `## Roadmap / Known Gaps` в `README.md` — туда же добавлять новые.

## Ловушки проекта (не переизобретать)

1. **OrderStatus state machine** — переходы только Pending→Assigned/Cancelled, Assigned→InProgress/Cancelled, InProgress→Completed/Cancelled. Логика в `UpdateOrderStatusAction::isValidTransition()`. Не дублировать проверки в других местах.
2. **AssignMasterAction** валидирует 4 условия: статус не финальный, мастер активен, мастер доступен, категории мастера включают категорию заказа. Своя валидация не нужна — вызывать экшен.
3. **CreditMasterBalanceAction** вызывается автоматически из `UpdateOrderStatusAction` при `Completed`. Вручную не звать. Списание баланса — отдельный `RecordMasterPayoutAction` со страницы Payments.
4. **OTP** лежит в Cache: `master_otp:{phone}` / `client_otp:{phone}`, TTL — `config('services.otp.ttl_minutes')`. Отправка через `OtpGatewayService` (HTTP → `socket-server/` Socket.IO мост → Flutter SMS-gateway). Phone нормализуется в локальный формат без `+993`. Если шлюз недоступен — `OtpException::sendFailed()` (503), и код в Cache НЕ кладётся. Reverb (Pusher-протокол) несовместим с `socket_io_client` — поэтому отдельный Node-сервер.
5. **Каналы вещания**: `orders` и `masters-map.*` пока публичные; `client.{id}` / `master.{id}` — приватные через Sanctum.
6. **Фото-конвертация** отдаёт `.webp` и УДАЛЯЕТ оригинал. Только через `PhotoConverter::convert()`. Фото задач — `OrderTaskPhoto`, до 2 на тип (before/after).
7. **Translations cache** — в production через дефолтный кэш-драйвер, в dev через `array` (см. `HandleInertiaRequests::loadTranslations`). Переводы не подхватились → `php artisan cache:clear`.
8. **`MasterLocation`** — `public $timestamps = false`, таблица использует `recorded_at` вместо `created_at`/`updated_at`.
9. **`Master` и `Client` extends `Authenticatable`** + `HasApiTokens` — авторизуются через Sanctum отдельно от `User`-админов.
10. **API-ошибки** — никаких try/catch в API-контроллерах. Все `*Exception` рендерятся через `bootstrap/app.php`; HTTP-код определяет `ApiException::statusCode()`.
11. **Иконки категорий** — preset-ключи валидируются по `config/service_icons.php`, кастомные загрузки лежат как `u-*.svg` на диске `service_icons`. Пути к SVG не хардкодить: рендер только через `ServiceIcon.vue` (CSS mask) и `CategoryIcon`.
12. **System status** — `SystemStatusController` читает `queue:worker_heartbeat` (пишет `Queue::looping()` в `AppServiceProvider`) и пингует Reverb. Без запущенного `queue:work` heartbeat протухает за ~120с. Планировщик для этого не нужен.
13. **Карты** — один стек на все три карты: `@maplibre/maplibre-gl-leaflet` + `TILES_STYLE_URL`. В dev это статический `public/maps/style.json` + роут `/tiles/{z}/{x}/{y}.pbf` поверх `storage/maps/tiles.mbtiles`.
14. **Чеки** — `IssueOrderReceiptAction` вызывается автоматически из `UpdateOrderStatusAction` при `Completed`, вручную не звать (идемпотентен). Чек — снапшот в `order_receipts` + `order_receipt_items`, а не вычисляемое представление: задачи без цены в него не попадают. Номер — `MDD-NNNN` (`824-0036` = 36-й чек за 24 августа). Старые завершённые заказы добираются командой `php artisan receipts:backfill`. Печать держится на `@media print` в `resources/css/app.css` (класс `.receipt-print`) — единственный кастомный CSS в проекте.

## Тестирование API вручную
- **Bruno**: коллекции в `bruno-client/` и `bruno-master/`. Каждый новый эндпоинт = новый `.bru`-файл в нужной папке.
- **Scribe**: `php artisan scribe:generate`, доки на `/docs`.