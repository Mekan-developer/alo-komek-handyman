<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import OrderStatusBadge from '@/Pages/Orders/Partials/OrderStatusBadge.vue'
import AssignMasterModal from '@/Pages/Orders/Partials/AssignMasterModal.vue'
import SetPriceModal from '@/Pages/Orders/Partials/SetPriceModal.vue'
import ChangeStatusModal from '@/Pages/Orders/Partials/ChangeStatusModal.vue'
import EditOrderModal from '@/Pages/Orders/Partials/EditOrderModal.vue'
import ImageLightbox from '@/Components/ImageLightbox.vue'
import { formatPhone } from '@/utils/formatPhone'
import { loadMapStyle, suppressBlankIconWarnings } from '@/utils/loadMapStyle'
import 'leaflet/dist/leaflet.css'
import 'maplibre-gl/dist/maplibre-gl.css'

const { t } = useI18n()
const page = usePage()

const props = defineProps({
    order: { type: Object, required: true },
    oblasts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    eligibleMasters: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
})

const showAssignModal = ref(false)
const showPriceModal = ref(false)
const showStatusModal = ref(false)
const showEditModal = ref(false)

const lightbox = ref({ show: false, images: [], index: 0 })

function openLightbox(images, index = 0) {
    lightbox.value = { show: true, images, index }
}

function taskPhoto(task, type) {
    const photos = type === 'before' ? task.before_photos : task.after_photos
    return photos?.[0] ?? null
}

const MAP_MODES = ['auto', 'light', 'grayscale', 'black'] //'dark', 'white',
const MAP_FILTERS = {
    light: '',
    dark: 'invert(1) hue-rotate(180deg) brightness(0.92) contrast(0.95)',
    white: 'grayscale(1) brightness(1.18) contrast(0.92)',
    grayscale: 'grayscale(1)',
    black: 'invert(1) grayscale(1) brightness(0.85) contrast(1.1)',
}
const MAP_MODE_STORAGE_KEY = 'masters-map-mode'
const currentMode = ref('auto')

const mapContainer = ref(null)
let map = null
let baseLayer = null
let themeObserver = null
let masterMarkerL = null
let trajectoryLine = null

const liveDistance = ref(null)
const liveEta = ref(null)

const isTracking = computed(() =>
    props.order.master && ['assigned', 'in_progress'].includes(props.order.status)
)

onMounted(async () => {
    const L = (await import('leaflet')).default
    await import('@maplibre/maplibre-gl-leaflet')

    const clientLat = parseFloat(props.order.client_lat)
    const clientLng = parseFloat(props.order.client_lng)

    map = L.map(mapContainer.value, {
        maxBounds: L.latLngBounds([[35.1, 52.5], [42.8, 66.7]]),
        maxBoundsViscosity: 1.0,
        minZoom: 5,
        attributionControl: false,
    }).setView([clientLat, clientLng], 14)

    baseLayer = L.maplibreGL({ style: await loadMapStyle(page.props.tilesStyleUrl) }).addTo(map)
    suppressBlankIconWarnings(baseLayer.getMaplibreMap?.())
    currentMode.value = localStorage.getItem(MAP_MODE_STORAGE_KEY) ?? 'auto'
    applyMapMode()
    baseLayer.getMaplibreMap?.()?.on('load', applyMapMode)
    watchTheme()
    setupMapControls(L)

    setTimeout(() => map?.invalidateSize(), 100)

    const allLatLngs = [[clientLat, clientLng]]

    const clientIcon = L.divIcon({
        className: 'custom-marker-client',
        html: '<div style="background:#22c55e;width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:13px;">К</div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
    })
    L.marker([clientLat, clientLng], { icon: clientIcon })
        .addTo(map)
        .bindPopup(`<b>${escapeHtml(props.order.client_name)}</b><br>${escapeHtml(formatPhone(props.order.client_phone))}`)

    if (props.order.master?.latest_location) {
        const masterLat = parseFloat(props.order.master.latest_location.latitude)
        const masterLng = parseFloat(props.order.master.latest_location.longitude)

        const masterIcon = L.divIcon({
            className: 'custom-marker-master',
            html: '<div style="background:#2563eb;width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:13px;">М</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        })

        masterMarkerL = L.marker([masterLat, masterLng], { icon: masterIcon })
            .addTo(map)
            .bindPopup(`<b>${escapeHtml(props.order.master.name)}</b><br>${escapeHtml(formatPhone(props.order.master.phone))}`)

        allLatLngs.push([masterLat, masterLng])
        updateDistanceEta(masterLat, masterLng, clientLat, clientLng)

        if (isTracking.value) {
            await fetchAndDrawTrajectory(L)
        }
    }

    // Кандидаты на замену показываем всегда, а не только пока мастер не назначен —
    // иначе после назначения их пины пропадают с карты и переназначить некого выбрать.
    props.eligibleMasters.forEach((m) => {
        if (!m.latest_location) { return }

        const lat = parseFloat(m.latest_location.latitude)
        const lng = parseFloat(m.latest_location.longitude)
        const distanceKm = haversineKm(clientLat, clientLng, lat, lng)

        const candidateIcon = L.divIcon({
            className: 'custom-marker-candidate',
            html: `<div style="background:#94a3b8;width:26px;height:26px;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:11px;">${escapeHtml(initialOf(m.name))}</div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13],
        })

        const popupHtml = `
            <div style="min-width:180px;">
                <div style="font-weight:600;font-size:13px;margin-bottom:2px;">${escapeHtml(m.name)}</div>
                <div style="color:#6b7280;font-size:12px;">${escapeHtml(formatPhone(m.phone))}</div>
                <div style="display:flex;align-items:center;gap:4px;color:#2563eb;font-size:12px;font-weight:500;margin:4px 0 8px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-7.5-7-12.5A7 7 0 0112 1a7 7 0 017 7.5C19 13.5 12 21 12 21z"/><circle cx="12" cy="8.5" r="2.5"/></svg>
                    ${formatDistance(distanceKm)}
                </div>
                <button
                    onclick="window.__assignFromMap(${m.id})"
                    style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:5px 10px;font-size:12px;cursor:pointer;width:100%"
                >${escapeHtml(t('orders.actions.assign_master'))}</button>
            </div>
        `

        L.marker([lat, lng], { icon: candidateIcon })
            .addTo(map)
            .bindPopup(popupHtml)

        allLatLngs.push([lat, lng])
    })

    if (allLatLngs.length > 1) {
        map.fitBounds(L.latLngBounds(allLatLngs), { padding: [80, 80] })
    }

    window.__assignFromMap = (masterId) => {
        router.post(route('orders.assign', props.order.id), { master_id: masterId })
    }

    if (isTracking.value && window.Echo && props.order.city?.id) {
        window.Echo.channel(`masters-map.${props.order.city.id}`)
            .listen('.master.location.updated', (payload) => {
                if (payload.master_id !== props.order.master.id) { return }

                const lat = parseFloat(payload.latitude)
                const lng = parseFloat(payload.longitude)

                masterMarkerL?.setLatLng([lat, lng])

                if (trajectoryLine) {
                    trajectoryLine.addLatLng([lat, lng])
                } else {
                    trajectoryLine = L.polyline([[lat, lng]], {
                        color: '#2563eb',
                        weight: 3,
                        opacity: 0.7,
                        dashArray: '8 5',
                    }).addTo(map)
                }

                updateDistanceEta(lat, lng, clientLat, clientLng)
            })
    }
})

onBeforeUnmount(() => {
    delete window.__assignFromMap
    if (props.order.city?.id) {
        window.Echo?.leave(`masters-map.${props.order.city.id}`)
    }
    themeObserver?.disconnect()
    if (map) {
        map.remove()
        map = null
    }
})

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

function watchTheme() {
    themeObserver = new MutationObserver(() => {
        if (currentMode.value === 'auto') { applyMapMode() }
    })
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
}

function setMapMode(mode) {
    currentMode.value = mode
    localStorage.setItem(MAP_MODE_STORAGE_KEY, mode)
    applyMapMode()
}

function cycleMapMode() {
    setMapMode(MAP_MODES[(MAP_MODES.indexOf(currentMode.value) + 1) % MAP_MODES.length])
}

function styleButtonTitle() {
    return `${t('masters.mode')}: ${t(`masters.modes.${currentMode.value}`)}`
}

function addButtonControl(L, position, buttons) {
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

function setupMapControls(L) {
    addButtonControl(L, 'topright', [
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

function initialOf(name) {
    return (name?.trim()?.charAt(0) ?? '?').toUpperCase()
}

function escapeHtml(value) {
    const div = document.createElement('div')
    div.textContent = String(value ?? '')
    return div.innerHTML
}

function haversineKm(lat1, lng1, lat2, lng2) {
    const toRad = (deg) => deg * Math.PI / 180
    const R = 6371
    const dLat = toRad(lat2 - lat1)
    const dLng = toRad(lng2 - lng1)
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2
    return 2 * R * Math.asin(Math.sqrt(a))
}

function formatDistance(km) {
    if (km < 1) { return `${Math.round(km * 1000)} м` }
    if (km < 10) { return `${km.toFixed(1)} км` }
    return `${Math.round(km)} км`
}

const ETA_SPEED_KMH = 60

function updateDistanceEta(masterLat, masterLng, clientLat, clientLng) {
    const km = haversineKm(masterLat, masterLng, clientLat, clientLng)
    liveDistance.value = km
    liveEta.value = Math.ceil((km / ETA_SPEED_KMH) * 60)
}

function formatEta(minutes) {
    if (minutes < 60) { return `~${minutes} мин` }
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m === 0 ? `~${h} ч` : `~${h} ч ${m} мин`
}

async function fetchAndDrawTrajectory(L) {
    try {
        const res = await fetch(route('orders.master-trajectory', props.order.id))
        const json = await res.json()
        const points = (json.points ?? []).map(p => [parseFloat(p.latitude), parseFloat(p.longitude)])

        if (points.length < 2) { return }

        trajectoryLine = L.polyline(points, {
            color: '#2563eb',
            weight: 3,
            opacity: 0.7,
            dashArray: '8 5',
        }).addTo(map)
    } catch {
        // silent — trajectory is optional
    }
}

const sortedEligibleMasters = computed(() => {
    if (!props.eligibleMasters?.length) { return [] }
    const clientLat = parseFloat(props.order.client_lat)
    const clientLng = parseFloat(props.order.client_lng)

    return [...props.eligibleMasters]
        .map((m) => ({
            ...m,
            distance_km: m.latest_location
                ? haversineKm(clientLat, clientLng, parseFloat(m.latest_location.latitude), parseFloat(m.latest_location.longitude))
                : null,
        }))
        .sort((a, b) => {
            if (a.distance_km === null) { return 1 }
            if (b.distance_km === null) { return -1 }
            return a.distance_km - b.distance_km
        })
})
</script>

<template>
    <AdminLayout :title="`${t('orders.show')} #${order.id}`">
        <!-- Full-bleed monitoring layout: cancel AdminLayout padding -->
        <div class="flex h-full flex-col overflow-hidden -m-4 lg:-m-6">

            <!-- Compact header -->
            <div class="flex shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800 lg:px-6">
                <Link
                    :href="route('orders.index')"
                    class="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-gray-100 dark:hover:bg-slate-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>

                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <h1 class="shrink-0 text-base font-semibold text-gray-900 dark:text-white">
                        {{ t('orders.show') }}
                        <span class="font-mono text-gray-400">#{{ order.id }}</span>
                    </h1>
                    <OrderStatusBadge :status="order.status" :label="order.status_label" :color="order.status_color" />
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="order.status === 'pending'"
                        @click="showEditModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        {{ t('orders.actions.edit') }}
                    </button>

                    <button
                        v-if="!['completed', 'cancelled'].includes(order.status)"
                        @click="showAssignModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                        </svg>
                        {{ order.master ? t('orders.actions.change_master') : t('orders.actions.assign_master') }}
                    </button>

                    <button
                        v-if="order.master && !['completed', 'cancelled'].includes(order.status)"
                        @click="showPriceModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        {{ t('orders.actions.set_price') }}
                    </button>

                    <button
                        v-if="!['completed', 'cancelled'].includes(order.status)"
                        @click="showStatusModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        {{ t('orders.actions.change_status') }}
                    </button>
                </div>
            </div>

            <!-- Body: sidebar + map -->
            <div class="flex min-h-0 flex-1 overflow-hidden">

                <!-- Left sidebar -->
                <aside class="w-72 shrink-0 overflow-y-auto border-r border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-900 lg:w-80">
                    <div class="space-y-3">

                        <!-- Client -->
                        <div class="rounded-xl bg-white shadow-sm dark:bg-slate-800">
                            <div class="border-b border-gray-100 px-4 py-2.5 dark:border-slate-700">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                    {{ t('orders.fields.client_name') }}
                                </h3>
                            </div>
                            <div class="px-4 py-3 text-sm">
                                <p class="font-medium text-gray-900 dark:text-slate-200">{{ order.client_name }}</p>
                                <a :href="`tel:${order.client_phone}`" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ formatPhone(order.client_phone) }}
                                </a>
                                <p v-if="order.client_address" class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                    {{ order.client_address }}
                                </p>
                            </div>
                        </div>

                        <!-- Order meta -->
                        <div class="rounded-xl bg-white shadow-sm dark:bg-slate-800">
                            <div class="space-y-2 px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-gray-500 dark:text-slate-400">{{ t('orders.fields.city') }}</span>
                                    <span class="font-medium text-gray-700 dark:text-slate-300">{{ order.city?.name }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-gray-500 dark:text-slate-400">{{ t('orders.fields.category') }}</span>
                                    <span class="truncate font-medium text-gray-700 dark:text-slate-300">{{ order.category?.name }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-gray-500 dark:text-slate-400">{{ t('orders.fields.created_at') }}</span>
                                    <span class="text-xs font-medium text-gray-700 dark:text-slate-300">{{ order.created_at }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-gray-500 dark:text-slate-400">{{ t('orders.fields.final_price') }}</span>
                                    <span v-if="order.final_price" class="font-mono font-semibold text-green-600 dark:text-green-400">
                                        {{ order.final_price }}
                                    </span>
                                    <span v-else class="text-gray-300 dark:text-slate-600">{{ t('orders.no_price') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Cancellation -->
                        <div v-if="order.status === 'cancelled'" class="rounded-xl border border-red-200 bg-red-50 shadow-sm dark:border-red-900/50 dark:bg-red-950/30">
                            <div class="flex items-center gap-2 border-b border-red-200/70 px-4 py-2.5 dark:border-red-900/50">
                                <svg class="h-4 w-4 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">
                                    {{ t('orders.fields.cancel_reason') }}
                                </h3>
                            </div>
                            <div class="px-4 py-3 text-sm">
                                <p v-if="order.cancel_reason" class="text-red-800 dark:text-red-200 whitespace-pre-wrap">
                                    {{ order.cancel_reason }}
                                </p>
                                <p v-else class="italic text-red-400 dark:text-red-500/80">
                                    {{ t('orders.no_reason') }}
                                </p>
                                <p v-if="order.cancelled_at" class="mt-2 text-xs text-red-500/80 dark:text-red-400/70">
                                    {{ t('orders.fields.cancelled_at') }}: {{ order.cancelled_at }}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="order.description" class="rounded-xl bg-white shadow-sm dark:bg-slate-800">
                            <div class="border-b border-gray-100 px-4 py-2.5 dark:border-slate-700">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                    {{ t('orders.fields.description') }}
                                </h3>
                            </div>
                            <div class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300 whitespace-pre-wrap">
                                {{ order.description }}
                            </div>
                        </div>

                        <!-- Master -->
                        <div class="rounded-xl bg-white shadow-sm dark:bg-slate-800">
                            <div class="border-b border-gray-100 px-4 py-2.5 dark:border-slate-700">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                    {{ t('orders.fields.master') }}
                                </h3>
                            </div>
                            <div class="px-4 py-3 text-sm">
                                <div v-if="order.master">
                                    <p class="font-medium text-gray-900 dark:text-slate-200">{{ order.master.name }}</p>
                                    <a :href="`tel:${order.master.phone}`" class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ formatPhone(order.master.phone) }}
                                    </a>
                                    <p v-if="order.assigned_at" class="mt-1 text-xs text-gray-400">
                                        {{ t('orders.fields.assigned_at') }}: {{ order.assigned_at }}
                                    </p>
                                    <p v-if="order.master_change_reason" class="mt-2 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ t('orders.fields.master_change_reason') }}: {{ order.master_change_reason }}
                                    </p>

                                    <div v-if="isTracking && liveDistance !== null" class="mt-3 rounded-lg bg-blue-50 px-3 py-2.5 dark:bg-blue-900/20">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center gap-1.5 text-blue-700 dark:text-blue-300">
                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                                    </svg>
                                                    <span class="text-sm font-bold">{{ formatDistance(liveDistance) }}</span>
                                                </div>
                                                <div class="h-3 w-px bg-blue-200 dark:bg-blue-700" />
                                                <div class="flex items-center gap-1.5 text-blue-700 dark:text-blue-300">
                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-sm font-bold">{{ formatEta(liveEta) }}</span>
                                                </div>
                                            </div>
                                            <span class="relative flex h-2 w-2">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75" />
                                                <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-500" />
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-blue-500 dark:text-blue-400">при скорости {{ ETA_SPEED_KMH }} км/ч</p>
                                    </div>
                                </div>
                                <p v-else class="text-gray-400 dark:text-slate-500">{{ t('orders.no_master') }}</p>
                            </div>
                        </div>

                        <!-- Photos -->
                        <div v-if="order.photos?.length" class="rounded-xl bg-white shadow-sm dark:bg-slate-800">
                            <div class="border-b border-gray-100 px-4 py-2.5 dark:border-slate-700">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                    {{ t('orders.fields.photos') }}
                                </h3>
                            </div>
                            <div class="grid grid-cols-3 gap-2 p-3">
                                <button
                                    v-for="(photo, idx) in order.photos"
                                    :key="photo.id"
                                    type="button"
                                    @click="openLightbox(order.photos, idx)"
                                    class="group relative aspect-square cursor-zoom-in overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-slate-700"
                                >
                                    <img :src="photo.url" :alt="`photo-${photo.id}`" class="h-full w-full object-cover transition-transform group-hover:scale-105" />
                                </button>
                            </div>
                        </div>

                        <!-- Tasks -->
                        <div v-if="order.tasks?.length" class="rounded-xl bg-white shadow-sm dark:bg-slate-800">
                            <div class="border-b border-gray-100 px-4 py-2.5 dark:border-slate-700">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                    {{ t('orders.fields.tasks') }}
                                </h3>
                            </div>
                            <div class="space-y-3 p-3">
                                <div
                                    v-for="task in order.tasks"
                                    :key="task.id"
                                    class="rounded-lg border border-gray-200 p-3 dark:border-slate-700"
                                >
                                    <p class="mb-2 text-xs font-medium text-gray-900 dark:text-slate-200">{{ task.title }}</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- Before -->
                                        <div>
                                            <p class="mb-1 text-xs text-gray-400">{{ t('orders.fields.before') }}</p>
                                            <button
                                                v-if="taskPhoto(task, 'before')"
                                                type="button"
                                                class="block w-full cursor-zoom-in overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-slate-600"
                                                @click="openLightbox([taskPhoto(task, 'before').url], 0)"
                                            >
                                                <img :src="taskPhoto(task, 'before').url" class="aspect-square w-full object-cover" />
                                            </button>
                                            <div v-else class="aspect-square rounded-lg bg-gray-100 dark:bg-slate-700" />
                                        </div>
                                        <!-- After -->
                                        <div>
                                            <p class="mb-1 text-xs text-gray-400">{{ t('orders.fields.after') }}</p>
                                            <button
                                                v-if="taskPhoto(task, 'after')"
                                                type="button"
                                                class="block w-full cursor-zoom-in overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-slate-600"
                                                @click="openLightbox([taskPhoto(task, 'after').url], 0)"
                                            >
                                                <img :src="taskPhoto(task, 'after').url" class="aspect-square w-full object-cover" />
                                            </button>
                                            <div v-else class="aspect-square rounded-lg bg-gray-100 dark:bg-slate-700" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </aside>

                <!-- Map panel: fills all remaining space -->
                <section class="relative flex-1 overflow-hidden">
                    <div ref="mapContainer" class="absolute inset-0" />

                    <!-- Live tracking overlay — top right -->
                    <div
                        v-if="isTracking && liveDistance !== null"
                        class="absolute right-4 top-4 z-[1001] flex items-center gap-3 rounded-xl bg-white/90 px-4 py-3 shadow-lg backdrop-blur-sm dark:bg-slate-800/90"
                    >
                        <div class="flex items-center gap-1.5 text-blue-700 dark:text-blue-300">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            <span class="text-sm font-bold">{{ formatDistance(liveDistance) }}</span>
                        </div>
                        <div class="h-4 w-px bg-gray-200 dark:bg-slate-600" />
                        <div class="flex items-center gap-1.5 text-blue-700 dark:text-blue-300">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-bold">{{ formatEta(liveEta) }}</span>
                        </div>
                        <span class="relative flex h-2.5 w-2.5 shrink-0">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75" />
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-blue-500" />
                        </span>
                    </div>

                    <!-- Legend — bottom left -->
                    <div class="absolute bottom-6 left-4 z-[1001] flex items-center gap-3 rounded-xl bg-white/90 px-3 py-2 text-xs shadow-md backdrop-blur-sm dark:bg-slate-800/90">
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-green-500 ring-2 ring-white dark:ring-slate-700" />
                            <span class="text-gray-600 dark:text-slate-300">{{ t('orders.fields.client_name') }}</span>
                        </div>
                        <div v-if="order.master" class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-blue-600 ring-2 ring-white dark:ring-slate-700" />
                            <span class="text-gray-600 dark:text-slate-300">{{ t('orders.fields.master') }}</span>
                        </div>
                        <div v-if="sortedEligibleMasters.length > 0" class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-slate-400 ring-2 ring-white dark:ring-slate-700" />
                            <span class="text-gray-600 dark:text-slate-300">Кандидаты</span>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <!-- Modals -->
        <EditOrderModal
            :show="showEditModal"
            :order="order"
            :oblasts="oblasts"
            :categories="categories"
            @close="showEditModal = false"
        />
        <AssignMasterModal
            :show="showAssignModal"
            :order-id="order.id"
            :masters="sortedEligibleMasters"
            :is-reassign="!!order.master"
            @close="showAssignModal = false"
        />
        <SetPriceModal
            :show="showPriceModal"
            :order-id="order.id"
            :current-price="order.final_price"
            @close="showPriceModal = false"
        />
        <ChangeStatusModal
            :show="showStatusModal"
            :order-id="order.id"
            :current-status="order.status"
            :statuses="statuses"
            :final-price="order.final_price"
            :master-payment-model="order.master?.payment_model"
            @close="showStatusModal = false"
        />

        <ImageLightbox
            :show="lightbox.show"
            :images="lightbox.images"
            :start-index="lightbox.index"
            @close="lightbox.show = false"
        />
    </AdminLayout>
</template>
