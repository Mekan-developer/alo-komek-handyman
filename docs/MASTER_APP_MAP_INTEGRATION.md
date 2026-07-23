# Master App — Map Screen Integration (Flutter)

> Audience: Flutter developer wiring up the **Map screen** in the Master mobile app.
> Backend: Laravel 11, source of truth verified directly against the codebase on 2026-07-17.
> Companion doc: [`MASTER_APP_SPEC.md`](./MASTER_APP_SPEC.md) — general app spec (auth, orders, photos).

---

## 0. Decision you need to make first

**The backend does not serve raster PNG tiles.** It only serves **vector tiles** (`.pbf`, OpenMapTiles schema) and a MapLibre `style.json`. This is the same source the web admin map (`/map`) uses — see `resources/views/map.blade.php`.

Two options:

1. **Recommended: switch the map screen to a vector renderer** — use the [`maplibre_gl`](https://pub.dev/packages/maplibre_gl) Flutter package and point it at the backend's `style.json`. This reuses the exact same tiles/style/fonts as the web map, zero extra backend work, and you get proper label rendering (streets, POIs) instead of a blank raster.
2. Ask backend to stand up a raster tile renderer (e.g. `tileserver-gl` in raster mode) that rasterizes the same `.mbtiles` into `{z}/{x}/{y}.png`. This is new infrastructure, not a config flag — budget for it if you need to keep `flutter_map`.

This doc assumes **option 1**. If you need option 2 instead, say so and backend will scope it separately.

---

## 1. Package

```yaml
dependencies:
  maplibre_gl: ^0.20.0   # check pub.dev for latest; supports iOS/Android/Web
```

Do **not** use `flutter_map` with a `TileLayer` for this — it expects raster image tiles and cannot render `.pbf` vector tiles.

---

## 2. Style & tile endpoints

| Resource | Production URL |
|---|---|
| Style JSON | `https://alo-komek.com.tm/maps/style.json` |
| Vector tiles | `https://alo-komek.com.tm/tiles/{z}/{x}/{y}.pbf` |
| Glyphs (fonts) | `https://alo-komek.com.tm/maps/fonts/{fontstack}/{range}.pbf` |
| Sprite (icons) | `https://alo-komek.com.tm/maps/sprite` |

| Param | Value |
|---|---|
| `MIN_ZOOM` | `0` |
| `MAX_ZOOM` | `14` |
| Auth on tiles/style | none — fully public |
| Attribution (must display) | `© OpenMapTiles © OpenStreetMap contributors` |
| Bounds | lon 51.83–66.72, lat 35.12–42.81 (Turkmenistan) |
| Default center (no GPS / no jobs) | lat `37.9415`, lng `58.3794` (Ashgabat), zoom `11` |

### ⚠️ Important: `style.json` has relative paths — you must rewrite them

The raw `/maps/style.json` file on disk has relative URLs (`"tiles": ["/tiles/{z}/{x}/{y}.pbf"]`, `"glyphs": "/maps/fonts/..."`, `"sprite": "/maps/sprite"`). MapLibre's native renderers require **absolute** URLs. The web admin map does this client-side (`resources/views/map.blade.php:12-25`) — do the same in Flutter: fetch the JSON, rewrite the three fields with the production origin, then pass the resulting object/string to the map widget.

```dart
Future<String> loadStyleJson() async {
  final res = await http.get(Uri.parse('https://alo-komek.com.tm/maps/style.json'));
  final style = jsonDecode(res.body) as Map<String, dynamic>;

  const base = 'https://alo-komek.com.tm';
  (style['sources'] as Map<String, dynamic>)['openmaptiles'] = {
    'type': 'vector',
    'tiles': ['$base/tiles/{z}/{x}/{y}.pbf'],
    'minzoom': 0,
    'maxzoom': 14,
  };
  style['glyphs'] = '$base/maps/fonts/{fontstack}/{range}.pbf';
  style['sprite'] = '$base/maps/sprite';

  return jsonEncode(style); // maplibre_gl's MapLibreMap accepts a style string (URL or inline JSON)
}
```

Then in the widget:

```dart
MapLibreMap(
  styleString: cachedStyleJsonString, // result of loadStyleJson(), cache it — don't refetch every build
  initialCameraPosition: const CameraPosition(
    target: LatLng(37.9415, 58.3794), // Ashgabat default
    zoom: 11,
  ),
  onMapCreated: (controller) => _controller = controller,
)
```

Fetch the style once at app start (or cache to disk), not per screen build — it's a few hundred KB of JSON.

---

## 3. Order markers (job pins on the map)

`GET /api/v1/master/orders?filter=active` — Bearer token required (`ensure.master` middleware).

Coordinates are already included in the **list** response — no need to call order detail per pin.

```json
{
  "data": [
    {
      "id": 123,
      "status": "assigned",
      "client_name": "Aman",
      "client_phone": "+99361234567",
      "address": "...",
      "latitude": 37.952321,
      "longitude": 58.382345,
      "category": "Plumbing",
      "final_price": null,
      "assigned_at": "2026-07-17T09:00:00+05:00"
    }
  ]
}
```

- `latitude`/`longitude` are `null` when the order has no coordinates — **skip the pin**, don't plot `(0,0)`.
- Field names are exactly `latitude`/`longitude`, WGS84 decimal degrees.
- Response is paginated (15/page) — handle `meta`/`links` if you expect >15 active orders per master (unlikely, but don't assume a flat array forever).
- `filter=active` returns only `assigned` and `in_progress` orders — that's what belongs on the map. Omit `filter` or use `filter=history` for completed/cancelled (don't plot those).

---

## 4. Sending the master's own live location

`POST /api/v1/master/{masterId}/location`

```json
{
  "latitude": 37.952321,
  "longitude": 58.382345,
  "order_id": 42,
  "recorded_at": "2026-07-17T13:45:00+05:00"
}
```

| Field | Required | Notes |
|---|---|---|
| `latitude` | yes | `-90..90` |
| `longitude` | yes | `-180..180` |
| `order_id` | no | set when tracking a specific trip, otherwise omit |
| `recorded_at` | no | defaults to server time |

- **This endpoint currently has no auth check** (no Bearer required) — send the token anyway once you have it, but don't rely on it being enforced yet; backend is aware this needs to close before wider release.
- No documented interval requirement in backend config — send every 10–15s while online, matching `MASTER_APP_SPEC.md` §3.1.
- `403` = master inactive/access expired, `404` = unknown master id, `422` = validation error.

---

## 5. Realtime pin updates (optional, nice-to-have)

If you want the map to update live instead of polling `GET /orders`, subscribe to the private channel `master.{masterId}` (Reverb/Pusher-protocol) and listen for:

- `order.status.changed` → refetch/patch the order's status locally
- `master.assigned` → a new order was assigned to this master; add its pin

Full Reverb connection details (host/key/port) are not filled in for production in this repo — get them from backend separately (`.env.production` on the server, not checked into git). See the realtime section of `MASTER_APP_SPEC.md` for the channel/event contract; it's stale on a couple of field names, ask backend to confirm before wiring it up.

---

## 6. Quick checklist for the Flutter side

- [ ] Add `maplibre_gl`, remove/skip `flutter_map` raster layer for this screen
- [ ] Fetch `style.json`, rewrite `tiles`/`glyphs`/`sprite` to absolute prod URLs, cache result
- [ ] Show attribution `© OpenMapTiles © OpenStreetMap contributors` somewhere on screen (required by license)
- [ ] Plot pins from `GET /api/v1/master/orders?filter=active`, skip null coordinates
- [ ] Send location via `POST /api/v1/master/{masterId}/location` every 10–15s while online
- [ ] Default camera: Ashgabat `(37.9415, 58.3794)`, zoom `11`, when no GPS/no jobs
