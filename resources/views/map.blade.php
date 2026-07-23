<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="/maps/maplibre-gl.css">
  <script src="/maps/maplibre-gl.js"></script>
  <style>html,body,#map{margin:0;height:100%}</style>
</head>
<body>
  <div id="map"></div>
  <script>
    // Способ B: style.json содержит относительные пути, MapLibre же требует
    // абсолютные URL — достраиваем их от origin текущей страницы.
    fetch('/maps/style.json')
     .then(r => r.json())
     .then(style => {
       const base = window.location.origin;
        style.sources.openmaptiles = {
          type: 'vector',
          tiles: [base + '/tiles/{z}/{x}/{y}.pbf'],
          minzoom: 0,
          maxzoom: 14        // реальный maxzoom данных в tiles.mbtiles
        };
        style.glyphs = base + '/maps/fonts/{fontstack}/{range}.pbf';
        style.sprite = base + '/maps/sprite';

        const map = new maplibregl.Map({
          container: 'map',
          style: style,            // объект стиля, а не URL
          center: [58.38, 37.95],  // Ашхабад
          zoom: 11
        });
        map.addControl(new maplibregl.NavigationControl());

        // Грязь в данных openmaptiles: у редких POI subclass = " ", стиль просит
        // иконку с таким именем. Подсовываем прозрачный пиксель, чтобы не шуметь в консоли.
        map.on('styleimagemissing', (e) => {
          if (!e.id.trim()) {
            map.addImage(e.id, { width: 1, height: 1, data: new Uint8Array(4) });
          }
        });
      });
  </script>

  <!-- Для способа A скрипт проще: style: '/maps/style.json' строкой,
       без fetch — роут Laravel уже отдаёт абсолютные URL из APP_URL. -->
</body>
</html>
