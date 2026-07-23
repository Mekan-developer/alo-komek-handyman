<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import PhoneInput from '@/Components/PhoneInput.vue'
import OblastCitySelect from '@/Components/OblastCitySelect.vue'
import CategoryPicker from '@/Components/CategoryPicker.vue'
import { loadMapStyle, suppressBlankIconWarnings } from '@/utils/loadMapStyle'
import 'leaflet/dist/leaflet.css'
import 'maplibre-gl/dist/maplibre-gl.css'

const { t } = useI18n()
const page = usePage()

const props = defineProps({
    show: { type: Boolean, required: true },
    oblasts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const TM_CENTER = [37.95, 58.38]

const form = useForm({
    client_id: null,
    client_name: '',
    client_phone: '',
    city_id: null,
    category_id: null,
    description: '',
    client_address: '',
    client_lat: '',
    client_lng: '',
    photos: [],
})

const hasLocation = computed(() => form.client_lat !== '' && form.client_lng !== '')

// ── Клиент: поиск / выбор / ручной ввод ───────────────────────────────────────
const clientSearch = ref('')
const showClientDropdown = ref(false)
const clientMode = ref('search') // 'search' | 'new'

const filteredClients = computed(() => {
    const q = clientSearch.value.trim().toLowerCase()
    const list = q
        ? props.clients.filter(c =>
            (c.name ?? '').toLowerCase().includes(q) || (c.phone ?? '').includes(q))
        : props.clients
    return list.slice(0, 8)
})

const selectedClient = computed(() =>
    props.clients.find(c => c.id === form.client_id) ?? null,
)

function selectClient(client) {
    form.client_id = client.id
    form.client_name = client.name ?? ''
    form.client_phone = client.phone ?? ''
    if (client.city_id) { form.city_id = client.city_id }
    clientSearch.value = ''
    showClientDropdown.value = false
}

function deselectClient() {
    form.client_id = null
    form.client_name = ''
    form.client_phone = ''
    clientMode.value = 'search'
}

function startNewClient() {
    form.client_id = null
    form.client_name = clientSearch.value.trim()
    form.client_phone = ''
    clientSearch.value = ''
    showClientDropdown.value = false
    clientMode.value = 'new'
}

function backToSearch() {
    clientMode.value = 'search'
    form.client_name = ''
    form.client_phone = ''
}

// ── Карта-пикер ───────────────────────────────────────────────────────────────
const mapContainer = ref(null)
let map = null
let L = null
let marker = null

function createCustomIcon() {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 44" width="32" height="44">
        <path d="M16 2C9.373 2 4 7.373 4 14c0 10.5 12 28 12 28s12-17.5 12-28C28 7.373 22.627 2 16 2z" fill="#3B82F6" stroke="#1D4ED8" stroke-width="1.5"/>
        <circle cx="16" cy="14" r="6" fill="white"/>
        <circle cx="16" cy="14" r="3.5" fill="#3B82F6"/>
    </svg>`
    return L.divIcon({
        html: svg,
        iconSize: [32, 44],
        iconAnchor: [16, 44],
        popupAnchor: [0, -44],
        className: '',
    })
}

async function initMap() {
    if (!mapContainer.value) { return }
    if (map) { map.invalidateSize(); return }

    L = (await import('leaflet')).default
    await import('@maplibre/maplibre-gl-leaflet')

    map = L.map(mapContainer.value, {
        minZoom: 7,
        maxZoom: 20,
        zoomControl: true,
        attributionControl: false,
    }).setView(TM_CENTER, 13)

    const baseLayer = L.maplibreGL({ style: await loadMapStyle(page.props.tilesStyleUrl) }).addTo(map)
    suppressBlankIconWarnings(baseLayer.getMaplibreMap?.())

    map.on('click', (e) => {
        form.client_lat = e.latlng.lat.toFixed(6)
        form.client_lng = e.latlng.lng.toFixed(6)
        placeMarker([e.latlng.lat, e.latlng.lng])
    })

    setTimeout(() => map?.invalidateSize(), 150)
}

function placeMarker(latLng) {
    if (!map || !L) { return }
    if (marker) {
        marker.setLatLng(latLng)
        return
    }
    marker = L.marker(latLng, { draggable: true, icon: createCustomIcon() }).addTo(map)
    marker.on('dragend', () => {
        const p = marker.getLatLng()
        form.client_lat = p.lat.toFixed(6)
        form.client_lng = p.lng.toFixed(6)
    })
}

function syncMarkerFromFields() {
    const lat = parseFloat(form.client_lat)
    const lng = parseFloat(form.client_lng)
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || !map) { return }
    placeMarker([lat, lng])
    map.setView([lat, lng], Math.max(map.getZoom(), 14))
}

function clearLocation() {
    form.client_lat = ''
    form.client_lng = ''
    if (marker && map) {
        map.removeLayer(marker)
        marker = null
    }
}

function geolocate() {
    if (!navigator.geolocation || !map) { return }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 16)
        },
        () => { /* silently ignore if denied */ },
        { timeout: 5000 },
    )
}

function destroyMap() {
    if (map) {
        map.remove()
        map = null
    }
    marker = null
}

// ── Фото ──────────────────────────────────────────────────────────────────────
const photoInput = ref(null)
const photoPreviews = ref([])

function onPhotosChange(e) {
    const files = Array.from(e.target.files).slice(0, 4)
    form.photos = files
    photoPreviews.value = files.map(f => URL.createObjectURL(f))
}

function removePhoto(index) {
    form.photos = form.photos.filter((_, i) => i !== index)
    photoPreviews.value = photoPreviews.value.filter((_, i) => i !== index)
    if (photoInput.value) { photoInput.value.value = '' }
}

// ── Жизненный цикл модалки ────────────────────────────────────────────────────
watch(() => props.show, async (val) => {
    if (val) {
        form.reset()
        form.clearErrors()
        form.photos = []
        photoPreviews.value = []
        clientSearch.value = ''
        showClientDropdown.value = false
        clientMode.value = 'search'
        await nextTick()
        initMap()
    } else {
        destroyMap()
    }
})

onBeforeUnmount(destroyMap)

function submit() {
    form.post(route('orders.store'), {
        forceFormData: true,
        onSuccess: () => emit('close'),
    })
}

const inputClass = 'w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white dark:focus:bg-slate-700'
const errorInputClass = 'border-red-400 dark:border-red-500'
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-slate-300'
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="emit('close')">
        <div class="flex h-full flex-col">
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ t('orders.modals.create_title') }}
                </h2>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-slate-300 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto">
                    <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">

                        <!-- Client section -->
                        <div class="sm:col-span-2">
                            <!-- Клиент выбран -->
                            <div
                                v-if="selectedClient"
                                class="flex items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50/60 px-4 py-2.5 dark:border-blue-500/40 dark:bg-blue-500/10"
                            >
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">
                                        {{ selectedClient.name || selectedClient.phone }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ selectedClient.phone }}</div>
                                </div>
                                <span class="hidden text-xs text-blue-600 dark:text-blue-300 sm:inline">
                                    {{ t('orders.create.selected_client') }}
                                </span>
                                <button
                                    type="button"
                                    @click="deselectClient"
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                                >
                                    {{ t('orders.create.deselect') }}
                                </button>
                            </div>

                            <!-- Режим поиска -->
                            <div v-else-if="clientMode === 'search'" class="relative">
                                <div class="flex gap-2">
                                    <input
                                        v-model="clientSearch"
                                        type="text"
                                        :placeholder="t('orders.create.search_client')"
                                        @focus="showClientDropdown = true"
                                        @input="showClientDropdown = true"
                                        :class="[inputClass, 'flex-1']"
                                    />
                                    <button
                                        type="button"
                                        @click="startNewClient"
                                        class="shrink-0 rounded-xl border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-600 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 dark:border-slate-600 dark:bg-slate-700/50 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:text-blue-400 transition-colors"
                                    >
                                        + {{ t('orders.create.new_client') }}
                                    </button>
                                </div>
                                <div
                                    v-if="showClientDropdown && clientSearch"
                                    class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-800"
                                >
                                    <button
                                        v-for="c in filteredClients"
                                        :key="c.id"
                                        type="button"
                                        @click="selectClient(c)"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left hover:bg-blue-50 dark:hover:bg-slate-700"
                                    >
                                        <span class="text-sm text-gray-900 dark:text-slate-200">{{ c.name || '—' }}</span>
                                        <span class="text-xs text-gray-500 dark:text-slate-400">{{ c.phone }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="startNewClient"
                                        class="flex w-full items-center gap-2 border-t border-gray-100 px-4 py-2.5 text-left text-sm text-blue-600 hover:bg-blue-50 dark:border-slate-700 dark:text-blue-400 dark:hover:bg-slate-700"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        {{ t('orders.create.create_named', { name: clientSearch }) }}
                                    </button>
                                </div>
                            </div>

                            <!-- Режим нового клиента -->
                            <div v-else class="space-y-1.5">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="space-y-1">
                                        <label :class="labelClass">
                                            {{ t('orders.fields.client_name') }} <span class="text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.client_name"
                                            type="text"
                                            :class="[inputClass, form.errors.client_name ? errorInputClass : '']"
                                        />
                                        <InputError :message="form.errors.client_name" />
                                    </div>
                                    <div class="space-y-1">
                                        <label :class="labelClass">
                                            {{ t('orders.fields.client_phone') }} <span class="text-red-400">*</span>
                                        </label>
                                        <PhoneInput
                                            v-model="form.client_phone"
                                            :has-error="!!form.errors.client_phone"
                                            size="sm"
                                        />
                                        <InputError :message="form.errors.client_phone" />
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="backToSearch"
                                    class="text-xs text-blue-500 hover:underline dark:text-blue-400"
                                >
                                    ← {{ t('orders.create.back_to_search') }}
                                </button>
                            </div>
                        </div>

                        <!-- City (велаят + город в один ряд) -->
                        <div class="space-y-1 sm:col-span-2">
                            <OblastCitySelect
                                v-model="form.city_id"
                                :oblasts="oblasts"
                                :has-error="!!form.errors.city_id"
                                horizontal
                                required
                            />
                            <InputError :message="form.errors.city_id" />
                        </div>

                        <!-- Address -->
                        <div class="space-y-1 sm:col-span-2">
                            <label :class="labelClass">{{ t('orders.fields.client_address') }}</label>
                            <input
                                v-model="form.client_address"
                                type="text"
                                :class="[inputClass, form.errors.client_address ? errorInputClass : '']"
                            />
                            <InputError :message="form.errors.client_address" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-1 sm:col-span-2">
                            <label :class="labelClass">{{ t('orders.fields.description') }} <span class="text-red-400">*</span></label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                :class="[inputClass, form.errors.description ? errorInputClass : '']"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Map picker -->
                        <div class="space-y-2 sm:col-span-2">
                            <label :class="labelClass">{{ t('orders.fields.location') }} <span class="text-red-400">*</span></label>

                            <!-- Map with overlays -->
                            <div class="relative h-80 overflow-hidden rounded-xl border border-gray-300 dark:border-slate-600">
                                <div ref="mapContainer" class="absolute inset-0" />

                                <!-- "Click to pick" hint chip -->
                                <Transition
                                    enter-active-class="transition-opacity duration-200"
                                    enter-from-class="opacity-0"
                                    enter-to-class="opacity-100"
                                    leave-active-class="transition-opacity duration-200"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <div
                                        v-if="!hasLocation"
                                        class="pointer-events-none absolute left-1/2 top-3 z-[1001] -translate-x-1/2"
                                    >
                                        <div class="flex items-center gap-1.5 whitespace-nowrap rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-gray-600 shadow-md backdrop-blur-sm dark:bg-slate-800/90 dark:text-slate-300">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                            </svg>
                                            {{ t('orders.create.pick_location') }}
                                        </div>
                                    </div>
                                </Transition>

                                <!-- Clear location button -->
                                <Transition
                                    enter-active-class="transition-all duration-200"
                                    enter-from-class="opacity-0 scale-90"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition-all duration-200"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-90"
                                >
                                    <button
                                        v-if="hasLocation"
                                        type="button"
                                        @click="clearLocation"
                                        class="absolute right-2.5 top-2.5 z-[1001] flex items-center gap-1.5 rounded-lg bg-white/95 px-2.5 py-1.5 text-xs font-medium text-red-600 shadow-md backdrop-blur-sm hover:bg-red-50 dark:bg-slate-800/95 dark:text-red-400 dark:hover:bg-slate-700 transition-colors"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        {{ t('orders.create.clear_location') }}
                                    </button>
                                </Transition>

                                <!-- Geolocation button -->
                                <button
                                    type="button"
                                    @click="geolocate"
                                    :title="t('orders.create.my_location')"
                                    class="absolute bottom-10 right-2.5 z-[1001] flex h-8 w-8 items-center justify-center rounded-lg bg-white/95 shadow-md backdrop-blur-sm hover:bg-white dark:bg-slate-800/95 dark:hover:bg-slate-800 transition-colors"
                                >
                                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="3" />
                                        <path stroke-linecap="round" d="M12 2v3M12 19v3M2 12h3M19 12h3" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Coordinates badge when location is set -->
                            <Transition
                                enter-active-class="transition-all duration-200"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="hasLocation"
                                    class="flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs dark:bg-blue-500/10"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0 text-blue-500 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                    </svg>
                                    <span class="font-mono text-blue-700 dark:text-blue-300">
                                        {{ form.client_lat }}, {{ form.client_lng }}
                                    </span>
                                </div>
                            </Transition>

                            <!-- Manual coordinate inputs -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <input
                                        v-model="form.client_lat"
                                        type="number"
                                        step="any"
                                        :placeholder="t('orders.fields.client_lat')"
                                        @change="syncMarkerFromFields"
                                        :class="[inputClass, form.errors.client_lat ? errorInputClass : '']"
                                    />
                                    <InputError :message="form.errors.client_lat" />
                                </div>
                                <div class="space-y-1">
                                    <input
                                        v-model="form.client_lng"
                                        type="number"
                                        step="any"
                                        :placeholder="t('orders.fields.client_lng')"
                                        @change="syncMarkerFromFields"
                                        :class="[inputClass, form.errors.client_lng ? errorInputClass : '']"
                                    />
                                    <InputError :message="form.errors.client_lng" />
                                </div>
                            </div>
                        </div>

                        <!-- Photos -->
                        <div class="space-y-2 sm:col-span-2">
                            <label :class="labelClass">{{ t('orders.fields.photos') }}</label>
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/*"
                                multiple
                                class="hidden"
                                @change="onPhotosChange"
                            />
                            <div class="flex flex-wrap gap-3">
                                <div
                                    v-for="(url, i) in photoPreviews"
                                    :key="i"
                                    class="relative h-20 w-20 overflow-hidden rounded-lg border border-gray-200 dark:border-slate-600"
                                >
                                    <img :src="url" class="h-full w-full object-cover" alt="" />
                                    <button
                                        type="button"
                                        @click="removePhoto(i)"
                                        class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80"
                                    >
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    v-if="photoPreviews.length < 4"
                                    type="button"
                                    @click="photoInput?.click()"
                                    class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 text-gray-400 hover:border-blue-400 hover:text-blue-500 dark:border-slate-600 dark:hover:border-blue-500 transition-colors"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    <span class="text-[10px]">{{ t('orders.create.add_photos') }}</span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ t('orders.create.photos_hint') }}</p>
                            <InputError :message="form.errors.photos" />
                        </div>

                        <!-- Category (ниже всех: группа-родитель → услуги-дети) -->
                        <div class="space-y-1 sm:col-span-2">
                            <CategoryPicker
                                v-model="form.category_id"
                                :categories="categories"
                                :has-error="!!form.errors.category_id"
                                required
                            />
                            <InputError :message="form.errors.category_id" />
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-2 border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                    >
                        {{ t('layout.actions.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? '...' : t('layout.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
