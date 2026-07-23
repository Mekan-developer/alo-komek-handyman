<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { loadMapStyle, suppressBlankIconWarnings } from '@/utils/loadMapStyle'
import 'leaflet/dist/leaflet.css'
import 'maplibre-gl/dist/maplibre-gl.css'

const { t } = useI18n()
const page = usePage()

const props = defineProps({
    masters: Array,
    cityIds: { type: Array, default: () => [] },
})

const mapContainer = ref(null)
let map = null
let L = null
let baseLayer = null
let locateMarker = null
const markers = {}
const trajectoryLayers = {}
const activeTrajectories = ref(new Set())
const lastUpdateAt = ref({})
const subscribedChannels = []

const TM_CENTER = [37.95, 58.38]

// tileserver-gl отдаёт один стиль (basic-preview) и только на чтение, поэтому
// «режимы» — это CSS-фильтры поверх WebGL-канваса MapLibre, а не разные стили.
const MAP_MODES = ['auto', 'light', 'dark', 'white', 'grayscale', 'black']
const MAP_FILTERS = {
    light: '',
    dark: 'invert(1) hue-rotate(180deg) brightness(0.92) contrast(0.95)',
    white: 'grayscale(1) brightness(1.18) contrast(0.92)',
    grayscale: 'grayscale(1)',
    black: 'invert(1) grayscale(1) brightness(0.85) contrast(1.1)',
}
const MAP_MODE_STORAGE_KEY = 'masters-map-mode'
const currentMode = ref('auto')
let themeObserver = null

onMounted(async () => {
    L = (await import('leaflet')).default
    // Side-effect: добавляет L.maplibreGL — мост Leaflet ↔ MapLibre GL (сам тянет maplibre-gl).
    await import('@maplibre/maplibre-gl-leaflet')

    const TM_BOUNDS = L.latLngBounds([[35.1, 52.5], [42.8, 66.7]])

    map = L.map(mapContainer.value, {
        maxBounds: TM_BOUNDS,
        maxBoundsViscosity: 1.0,
        minZoom: 7,
        maxZoom: 20,
        zoomControl: true,
        attributionControl: false,
    }).setView(TM_CENTER, 11)

    baseLayer = (await createBaseLayer()).addTo(map)
    suppressBlankIconWarnings(baseLayer.getMaplibreMap?.())
    if (typeof baseLayer.bringToBack === 'function') { baseLayer.bringToBack() }

    currentMode.value = localStorage.getItem(MAP_MODE_STORAGE_KEY) ?? 'auto'
    applyMapMode()
    baseLayer.getMaplibreMap?.()?.on('load', applyMapMode)
    watchTheme()

    setupControls()
    registerLocateHandlers()

    setTimeout(() => map.invalidateSize(), 100)

    props.masters.forEach((master) => {
        if (!master.latest_location) { return }
        addOrUpdateMarker(master.id, master, master.latest_location.latitude, master.latest_location.longitude)
        lastUpdateAt.value[master.id] = master.latest_location.recorded_at
    })

    fitAllMasters()

    window.__showTrajectory = (masterId) => toggleTrajectory(masterId)

    subscribeToCityChannels()
})

onBeforeUnmount(() => {
    delete window.__showTrajectory
    subscribedChannels.forEach((channel) => window.Echo?.leave(channel))
    themeObserver?.disconnect()
    if (map) {
        map.remove()
        map = null
    }
})

// Фильтр вешаем на сам канвас MapLibre — Leaflet-маркеры/попапы в других панелях не затрагиваются.
function resolveFilter(mode) {
    if (mode === 'auto') {
        return document.documentElement.classList.contains('dark') ? MAP_FILTERS.dark : MAP_FILTERS.light
    }

    return MAP_FILTERS[mode] ?? ''
}

function applyMapMode() {
    const canvas = baseLayer?.getCanvas?.()
    if (!canvas) { return }
    canvas.style.transition = 'filter 0.25s ease'
    canvas.style.filter = resolveFilter(currentMode.value)
}

function setMapMode(mode) {
    currentMode.value = mode
    localStorage.setItem(MAP_MODE_STORAGE_KEY, mode)
    applyMapMode()
}

// В режиме «авто» подхватываем переключение темы (класс dark на <html>).
function watchTheme() {
    themeObserver = new MutationObserver(() => {
        if (currentMode.value === 'auto') { applyMapMode() }
    })
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
}

// Базовый слой — внешний MapLibre-стиль с self-hosted tileserver-gl.
// URL приходит из .env через Inertia (config/services.php → HandleInertiaRequests), не хардкодим.
async function createBaseLayer() {
    return L.maplibreGL({ style: await loadMapStyle(page.props.tilesStyleUrl) })
}

function setupControls() {
    L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(map)

    addButtonControl('topright', [
        {
            title: t('masters.fit_all'),
            html: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9V5a2 2 0 012-2h4M21 9V5a2 2 0 00-2-2h-4M3 15v4a2 2 0 002 2h4M21 15v4a2 2 0 01-2 2h-4"/></svg>',
            onClick: fitAllMasters,
        },
        {
            title: t('masters.my_location'),
            html: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>',
            onClick: locateMe,
        },
        {
            title: t('masters.fullscreen'),
            html: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>',
            onClick: toggleFullscreen,
        },
        {
            title: styleButtonTitle(),
            html: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 100 20 2 2 0 002-2 2 2 0 012-2h1a4 4 0 004-4 9 9 0 00-9-10z"/></svg>',
            onClick: (link) => {
                cycleMapMode()
                link.title = styleButtonTitle()
            },
        },
    ])
}

function addButtonControl(position, buttons) {
    const ButtonControl = L.Control.extend({
        onAdd() {
            const container = L.DomUtil.create('div', 'leaflet-bar')
            buttons.forEach(({ title, html, onClick }) => {
                const link = L.DomUtil.create('a', '', container)
                link.href = '#'
                link.title = title
                link.role = 'button'
                link.innerHTML = html
                link.style.display = 'flex'
                link.style.alignItems = 'center'
                link.style.justifyContent = 'center'
                L.DomEvent.on(link, 'click', L.DomEvent.stop).on(link, 'click', () => onClick(link))
            })
            L.DomEvent.disableClickPropagation(container)
            return container
        },
    })

    new ButtonControl({ position }).addTo(map)
}

// Подпись кнопки = текущий режим; на каждый клик режим переключается по кругу.
function styleButtonTitle() {
    return `${t('masters.mode')}: ${t(`masters.modes.${currentMode.value}`)}`
}

function cycleMapMode() {
    const next = MAP_MODES[(MAP_MODES.indexOf(currentMode.value) + 1) % MAP_MODES.length]
    setMapMode(next)
}

function fitAllMasters() {
    const latLngs = Object.values(markers).map((marker) => marker.getLatLng())
    if (!latLngs.length) { return }
    map.fitBounds(L.latLngBounds(latLngs), { padding: [60, 60], maxZoom: 15 })
}

function locateMe() {
    map.locate({ setView: true, maxZoom: 16, enableHighAccuracy: true })
}

function registerLocateHandlers() {
    map.on('locationfound', (event) => {
        if (locateMarker) {
            locateMarker.setLatLng(event.latlng)
            return
        }
        locateMarker = L.circleMarker(event.latlng, {
            radius: 8, color: '#7c3aed', fillColor: '#a855f7', fillOpacity: 0.9, weight: 3,
        }).addTo(map)
    })
}

function toggleFullscreen() {
    const element = mapContainer.value?.parentElement ?? mapContainer.value
    if (document.fullscreenElement) {
        document.exitFullscreen?.()
    } else {
        element?.requestFullscreen?.()
    }
    setTimeout(() => map.invalidateSize(), 200)
}

function subscribeToCityChannels() {
    if (!window.Echo || !props.cityIds?.length) { return }

    props.cityIds.forEach((cityId) => {
        const channelName = `masters-map.${cityId}`
        subscribedChannels.push(channelName)

        window.Echo.channel(channelName)
            .listen('.master.location.updated', (payload) => {
                handleLocationUpdate(payload)
            })
    })
}

function handleLocationUpdate(payload) {
    const master = props.masters.find((m) => m.id === payload.master_id)
    if (!master) { return }

    addOrUpdateMarker(payload.master_id, master, payload.latitude, payload.longitude, true)
    lastUpdateAt.value[payload.master_id] = payload.recorded_at

    if (activeTrajectories.value.has(payload.master_id) && trajectoryLayers[payload.master_id]) {
        trajectoryLayers[payload.master_id].addLatLng([parseFloat(payload.latitude), parseFloat(payload.longitude)])
    }
}

function addOrUpdateMarker(masterId, master, lat, lng, animated = false) {
    const latLng = [parseFloat(lat), parseFloat(lng)]

    if (markers[masterId]) {
        if (animated) {
            animateMarkerTo(markers[masterId], latLng)
        } else {
            markers[masterId].setLatLng(latLng)
        }
        return
    }

    const icon = L.divIcon({
        className: 'master-marker',
        html: `<div style="background:#2563eb;width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:11px;">${escapeHtml(initialOf(master.name))}</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
    })

    const marker = L.marker(latLng, { icon }).addTo(map)

    const popupHtml = `
        <div style="min-width:180px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:4px;">${escapeHtml(master.name)}</div>
            <div style="color:#6b7280;font-size:12px;">${escapeHtml(master.phone)}</div>
            <div style="color:#6b7280;font-size:12px;margin-bottom:8px;">${escapeHtml(master.city?.name ?? '')}</div>
            <button
                onclick="window.__showTrajectory(${master.id})"
                style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;width:100%"
            >${t('masters.trajectory')}</button>
        </div>
    `
    marker.bindPopup(popupHtml)
    markers[masterId] = marker
}

function animateMarkerTo(marker, targetLatLng, duration = 1500) {
    const start = marker.getLatLng()
    const end = L.latLng(targetLatLng[0], targetLatLng[1])
    const startTime = performance.now()

    function step(now) {
        const t = Math.min(1, (now - startTime) / duration)
        const ease = 1 - Math.pow(1 - t, 3)
        const lat = start.lat + (end.lat - start.lat) * ease
        const lng = start.lng + (end.lng - start.lng) * ease
        marker.setLatLng([lat, lng])
        if (t < 1) { requestAnimationFrame(step) }
    }
    requestAnimationFrame(step)
}

async function toggleTrajectory(masterId) {
    if (activeTrajectories.value.has(masterId)) {
        trajectoryLayers[masterId]?.remove()
        delete trajectoryLayers[masterId]
        activeTrajectories.value.delete(masterId)
        return
    }

    const response = await fetch(route('masters.trajectory', masterId))
    const data = await response.json()
    if (!data.points?.length) { return }

    const latlngs = data.points.map((p) => [p.latitude, p.longitude])
    const polyline = L.polyline(latlngs, { color: '#2563eb', weight: 3, opacity: 0.8 }).addTo(map)

    L.circleMarker(latlngs[0], { radius: 6, color: '#16a34a', fillColor: '#22c55e', fillOpacity: 1 })
        .addTo(map).bindTooltip(data.points[0].recorded_at)

    const last = latlngs[latlngs.length - 1]
    L.circleMarker(last, { radius: 6, color: '#dc2626', fillColor: '#ef4444', fillOpacity: 1 })
        .addTo(map).bindTooltip(data.points[data.points.length - 1].recorded_at)

    trajectoryLayers[masterId] = polyline
    activeTrajectories.value.add(masterId)
    map.fitBounds(polyline.getBounds(), { padding: [40, 40] })
}

function initialOf(name) {
    return (name?.trim()?.charAt(0) ?? '?').toUpperCase()
}

function escapeHtml(value) {
    const div = document.createElement('div')
    div.textContent = String(value ?? '')
    return div.innerHTML
}
</script>

<template>
    <AdminLayout :title="t('masters.map')">
        <div class="flex flex-col gap-4 h-full">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('masters.map') }}
                </h1>
                <Link
                    :href="route('masters.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ t('masters.title') }}
                </Link>
            </div>

            <div class="flex flex-wrap items-center gap-4 rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-slate-800">
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-400">
                    <span class="h-3 w-3 rounded-full bg-blue-600" />
                    {{ t('masters.last_seen') }}
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-400">
                    <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-green-500" />
                    Live
                </div>
                <p class="ml-auto text-xs text-gray-400 dark:text-slate-500">
                    {{ masters.length }} {{ t('masters.active') }}
                </p>
            </div>

            <div class="flex-1 overflow-hidden rounded-xl shadow-sm min-h-0">
                <div ref="mapContainer" class="h-full w-full" />
            </div>
        </div>
    </AdminLayout>
</template>
