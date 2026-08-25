# API v1 — Реалтайм-уведомление о цене задачи (`order.task.price.updated`)

**Кому:** разработчикам мобильных приложений (Client + Master)
**Тип изменения:** аддитивное — новое WebSocket-событие, ничего в REST API не менялось
**Отвечает на вопрос:** §8.4 из [API_V1_TASK_PRICING.md](API_V1_TASK_PRICING.md) — «Есть ли
реалтайм-уведомление об изменении цены, или приложению нужно поллить `GET /orders/{id}`?»
— **теперь есть.**
**Статус:** реализовано на бэкенде, покрыто тестами (`tests/Feature/OrderRealtimeTest.php`)

---

## Что случилось

Когда администратор в веб-панели проставляет (или очищает) цену подзадачи, бэкенд рассылает
событие `order.task.price.updated` через Reverb — то же WebSocket-соединение, которое уже
используется под `master.assigned` / `order.status.changed` (см. [MASTER_APP_SPEC.md](MASTER_APP_SPEC.md) §4).
Поднимать отдельное соединение не нужно, достаточно добавить ещё один `.listen(...)` на уже
открытом private-канале.

---

## ⚠️ Поправка к MASTER_APP_SPEC.md

В §4 того документа auth-эндпоинт для приватных каналов указан как `POST /broadcasting/auth`.
Это адрес для веб-админки (сессионная авторизация). **Мобильные приложения должны использовать
`POST /api/v1/broadcasting/auth`** — отдельный роут под `auth:sanctum`, специально заведённый
для Bearer-токенов (`routes/api/v1.php`):

```
POST /api/v1/broadcasting/auth
Header: Authorization: Bearer <sanctum_token>
Body:   { "channel_name": "private-master.42", "socket_id": "..." }
```

Если у вас уже реализована подписка на `master.assigned` и она работает — вы уже используете
правильный адрес, эта поправка ни на что не влияет. Если ещё нет — используйте адрес выше, а не
из §4 MASTER_APP_SPEC.md.

---

## Канал и событие

| | |
|---|---|
| Канал (Master) | `private-master.{masterId}` |
| Канал (Client) | `private-client.{clientId}` |
| Событие | `.order.task.price.updated` |
| Доставка | `ShouldBroadcastNow` — летит сразу, без задержки на очередь |

## Payload

```json
{
  "order_id": 19,
  "task_id": 41,
  "price": 350.5,
  "final_price": 450.25
}
```

- `price` — новая цена именно этой задачи (`task_id`). `null`, если админ цену снял.
- `final_price` — уже пересчитанный итог по всему заказу (сумма цен задач минус скидка), тип —
  число, как и в REST-ответах (см. §2 в [API_V1_TASK_PRICING.md](API_V1_TASK_PRICING.md)). Тоже
  может быть `null`, если ни одна задача ещё не оценена.
- `discount_amount` и `tasks_total` в событие **не входят**. Если на экране показываете полную
  разбивку («работы / скидка / итого»), после события либо пересчитайте `tasks_total` суммой
  локальных `price` задач, либо просто перечитайте `GET /orders/{id}`.

## Когда событие НЕ прилетит

- Цену задачи можно поставить только когда на заказе уже есть мастер — иначе бэкенд отклоняет
  запрос ещё до броадкаста. Поэтому `private-master.*` получает событие всегда, а
  `private-client.*` — только если у заказа вообще есть привязанный `client_id` (клиент оформил
  заявку из приложения, а не по звонку через оператора).
- Изменение **скидки на заказ** (`discount_percent`) тоже меняет `final_price`, но отдельного
  события пока нет — это самостоятельный пробел, не путайте с этим. На скидку пока приходится
  поллить `GET /orders/{id}`, как и раньше.
- Если Reverb или очередь на бэкенде не подняты, событие теряется молча (не ретраится) — запрос
  админа всё равно вернёт 200. WebSocket только ускоряет обновление экрана, источник правды
  всегда `GET /orders/{id}` (см. README «Realtime Fault Tolerance»).

---

## Как проверить у себя, что это реально работает

Самодостаточный сценарий: получить токен, подписаться на канал, спровоцировать событие,
убедиться, что оно долетело — без сборки Flutter-приложения.

### 1. Токен тестового мастера (в обход OTP)

```bash
php artisan tinker --execute '
$master = App\Models\Master::query()->where("is_active", true)->first();
echo $master->id . " " . $master->createToken("mobile")->plainTextToken;
'
```

Для клиента — то же самое с `App\Models\Client::query()->first()`.

### 2. Подписаться на канал любым Pusher-совместимым клиентом

Протокол тот же, что у `pusher_channels_flutter` в приложении, так что подойдёт `pusher-js` в
Node — не нужно разворачивать мобильный проект, чтобы проверить именно доставку события.

```js
const Pusher = require('pusher-js')

const pusher = new Pusher('<REVERB_APP_KEY из .env бэкенда>', {
  wsHost: '192.168.31.163', wsPort: 8880, forceTLS: false,
  enabledTransports: ['ws'],
  authEndpoint: 'http://192.168.31.163:8090/api/v1/broadcasting/auth',
  auth: { headers: { Authorization: 'Bearer <TOKEN из шага 1>' } },
})

const channel = pusher.subscribe('private-master.<MASTER_ID>')
channel.bind('order.task.price.updated', (data) => console.log('GOT IT', data))
```

### 3. Спровоцировать событие

Через веб-админку — открыть `/orders/{id}` заказа с этим мастером и проставить цену любой
подзадаче. Либо тем же tinker, без UI:

```bash
php artisan tinker --execute '
$order = App\Models\Order::query()->whereNotNull("master_id")->first();
$task = $order->tasks()->first();
app(App\Actions\SetOrderTaskPriceAction::class)->handle($order, $task, 350.50);
'
```

### 4. Критерий «работает»

- Консоль из шага 2 печатает `GOT IT { order_id, task_id, price: 350.5, final_price: ... }` в
  течение доли секунды после шага 3.
- Тишина в консоли обычно означает одно из:
  - `php artisan reverb:start` не запущен;
  - `authEndpoint` указывает на `/broadcasting/auth` вместо `/api/v1/broadcasting/auth`
    (см. поправку выше) — тогда `/broadcasting/auth` ответит 401/419, потому что мобильный
    Bearer-токен там не проходит сессионную авторизацию;
  - токен не принадлежит мастеру/клиенту этого заказа — канал вернёт 403 при подписке.

---

## Что сделать в приложениях

### Master
- [ ] Подписаться на `.order.task.price.updated` там же, где уже слушаете
      `.order.status.changed` (экран активного заказа).
- [ ] По приходу события обновить `price` соответствующей задачи и `final_price` заказа в
      локальном состоянии, не дожидаясь повторного `GET /orders/{id}`.

### Client
- [ ] То же самое на `private-client.{clientId}`, но учитывать, что для гостевых заказов
      (созданных по звонку, без привязки к аккаунту) события не будет вообще.

### Обоим
- [ ] Не полагаться только на WebSocket: при возврате из фона / восстановлении соединения
      по-прежнему перечитывать `GET /orders/{id}` — WebSocket ускоряет обновление, но не
      заменяет REST как источник правды.

---

## Связанные документы

- [API_V1_TASK_PRICING.md](API_V1_TASK_PRICING.md) — сама модель цен по задачам и скидке.
- [MASTER_APP_SPEC.md](MASTER_APP_SPEC.md) §4 — подключение к Reverb, остальные каналы.
