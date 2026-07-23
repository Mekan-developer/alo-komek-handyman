<?php

/*
|--------------------------------------------------------------------------
| Service Icons — curated preset set for categories
|--------------------------------------------------------------------------
|
| Single source of truth for the built-in icon set an admin can pick from
| when creating a category / subcategory. Keys map to SVG files stored at
| public/icons/services/{key}.svg (monochrome line, Heroicons outline).
|
| Used in two places:
|   - Backend validation: only keys listed here are accepted as a preset icon.
|   - Frontend picker: grouped grid passed to the Categories page via Inertia.
|
| Group labels are translated on the frontend (categories.icon_group_*).
| To extend the set: drop a `currentColor` SVG into public/icons/services/
| and add its filename (without extension) to the relevant group below.
|
*/

return [
    'repair' => ['wrench', 'cog-6-tooth', 'adjustments-horizontal', 'bolt', 'light-bulb', 'fire', 'sun'],
    'home' => ['home', 'building-office', 'building-storefront', 'paint-brush', 'swatch', 'squares-2x2', 'window', 'cube'],
    'tech' => ['cpu-chip', 'tv', 'computer-desktop', 'device-phone-mobile', 'device-tablet', 'printer', 'wifi', 'signal', 'globe-alt'],
    'security' => ['shield-check', 'lock-closed', 'key', 'video-camera', 'bell'],
    'services' => ['truck', 'archive-box', 'user-group', 'sparkles', 'beaker', 'trash', 'map-pin', 'calendar-days', 'phone', 'heart', 'academic-cap', 'musical-note'],
];
