<script setup>
import { ref, computed, nextTick } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import OblastFormModal from '@/Pages/Oblasts/Partials/OblastFormModal.vue'
import CityFormModal from '@/Pages/Cities/Partials/CityFormModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const { t } = useI18n()

const props = defineProps({
    oblasts: Array,
    cities: Object,
})

// Persist selected oblast across Inertia page reloads (preserveState)
const selectedOblastId = ref(null)

const selectedOblast = computed(() =>
    (props.oblasts ?? []).find(o => o.id === selectedOblastId.value) ?? null
)

const allCities = computed(() => props.cities?.data ?? [])

const filteredCities = computed(() =>
    selectedOblastId.value
        ? allCities.value.filter(c => c.oblast_id === selectedOblastId.value)
        : []
)

function selectOblast(oblast) {
    selectedOblastId.value = oblast.id
}

// ── Oblast form ────────────────────────────────────────────────────────────────
const showOblastModal = ref(false)
const editingOblast = ref(null)

const oblastForm = useForm({
    name: '',
    is_active: true,
})

function openCreateOblast() {
    editingOblast.value = null
    oblastForm.reset()
    oblastForm.clearErrors()
    showOblastModal.value = true
}

function openEditOblast(oblast) {
    editingOblast.value = oblast
    oblastForm.name = oblast.name
    oblastForm.is_active = oblast.is_active
    oblastForm.clearErrors()
    showOblastModal.value = true
}

function closeOblastModal() {
    showOblastModal.value = false
    editingOblast.value = null
    oblastForm.reset()
    oblastForm.clearErrors()
}

function submitOblast() {
    if (editingOblast.value) {
        oblastForm.put(route('oblasts.update', editingOblast.value.id), {
            preserveState: true,
            onSuccess: closeOblastModal,
        })
    } else {
        oblastForm.post(route('oblasts.store'), {
            preserveState: true,
            onSuccess: closeOblastModal,
        })
    }
}

const deleteOblastTarget = ref(null)
const deletingOblast = ref(false)

function destroyOblast(oblast) {
    deleteOblastTarget.value = oblast
}

function confirmDestroyOblast() {
    const oblast = deleteOblastTarget.value
    const wasSelected = selectedOblastId.value === oblast.id
    deletingOblast.value = true
    router.delete(route('oblasts.destroy', oblast.id), {
        preserveState: true,
        onSuccess: () => {
            if (wasSelected) { selectedOblastId.value = null }
            deleteOblastTarget.value = null
        },
        onFinish: () => { deletingOblast.value = false },
    })
}

// ── City form ──────────────────────────────────────────────────────────────────
const showCityModal = ref(false)
const editingCity = ref(null)

const cityForm = useForm({
    name: '',
    oblast_id: null,
    is_active: true,
})

function openCreateCity() {
    editingCity.value = null
    cityForm.reset()
    cityForm.oblast_id = selectedOblastId.value
    cityForm.clearErrors()
    showCityModal.value = true
}

function openEditCity(city) {
    editingCity.value = city
    cityForm.name = city.name
    cityForm.oblast_id = city.oblast_id ?? null
    cityForm.is_active = city.is_active
    cityForm.clearErrors()
    showCityModal.value = true
}

function closeCityModal() {
    showCityModal.value = false
    editingCity.value = null
    cityForm.reset()
    cityForm.clearErrors()
}

function submitCity() {
    if (editingCity.value) {
        cityForm.put(route('cities.update', editingCity.value.id), {
            preserveState: true,
            onSuccess: closeCityModal,
        })
    } else {
        cityForm.post(route('cities.store'), {
            preserveState: true,
            onSuccess: closeCityModal,
        })
    }
}

const deleteCityTarget = ref(null)
const deletingCity = ref(false)

function destroyCity(city) {
    deleteCityTarget.value = city
}

function confirmDestroyCity() {
    deletingCity.value = true
    router.delete(route('cities.destroy', deleteCityTarget.value.id), {
        preserveState: true,
        onSuccess: () => { deleteCityTarget.value = null },
        onFinish: () => { deletingCity.value = false },
    })
}
</script>

<template>
    <AdminLayout :title="t('oblasts.title')">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- ── Left: Oblasts ────────────────────────────────────────────── -->
            <div class="space-y-3">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('oblasts.title') }}
                    </h2>
                    <button
                        @click="openCreateOblast"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 transition-colors"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ t('oblasts.add') }}
                    </button>
                </div>

                <!-- Oblast cards -->
                <div
                    v-for="oblast in oblasts"
                    :key="oblast.id"
                    @click="selectOblast(oblast)"
                    :class="selectedOblastId === oblast.id
                        ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20'
                        : 'bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700/60'"
                    class="group cursor-pointer rounded-xl p-4 shadow-sm transition-all duration-150"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ oblast.name }}
                            </p>
                            <span
                                :class="oblast.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                                    : 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400'"
                                class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                <span
                                    :class="oblast.is_active ? 'bg-green-500' : 'bg-red-400'"
                                    class="h-1.5 w-1.5 rounded-full"
                                />
                                {{ oblast.is_active ? t('oblasts.active') : t('oblasts.inactive') }}
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                @click.stop="openEditOblast(oblast)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-colors"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                            </button>
                            <button
                                @click.stop="destroyOblast(oblast)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-colors"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="!oblasts?.length" class="rounded-xl bg-white px-4 py-6 text-center text-sm text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                    {{ t('oblasts.empty') }}
                </p>
            </div>

            <!-- ── Right: Cities of selected oblast ────────────────────────── -->
            <div class="lg:col-span-2">

                <!-- Placeholder — nothing selected -->
                <div
                    v-if="!selectedOblast"
                    class="flex h-full min-h-[320px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 text-center dark:border-slate-700"
                >
                    <svg class="mb-3 h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    <p class="text-sm font-medium text-gray-400 dark:text-slate-500">
                        {{ t('oblasts.select_to_view_cities') }}
                    </p>
                </div>

                <!-- Cities table -->
                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ t('cities.title') }}
                            <span class="ml-1.5 text-sm font-normal text-gray-500 dark:text-slate-400">
                                — {{ selectedOblast.name }}
                            </span>
                        </h2>
                        <button
                            @click="openCreateCity"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 transition-colors"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ t('cities.add') }}
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">#</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('cities.name') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('cities.status') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('cities.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                <tr v-if="filteredCities.length === 0">
                                    <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-slate-500">
                                        {{ t('cities.empty') }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="(city, index) in filteredCities"
                                    :key="city.id"
                                    class="group transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-slate-700"
                                >
                                    <td class="px-5 py-3.5 text-sm text-gray-400 dark:text-slate-500">{{ index + 1 }}</td>
                                    <td class="px-5 py-3.5 text-sm font-medium text-gray-900 dark:text-slate-200">{{ city.name }}</td>
                                    <td class="px-5 py-3.5">
                                        <span
                                            :class="city.is_active
                                                ? 'bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30'
                                                : 'bg-red-100 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        >
                                            <span :class="city.is_active ? 'bg-green-500 dark:bg-green-400' : 'bg-red-400'" class="h-1.5 w-1.5 rounded-full" />
                                            {{ city.is_active ? t('cities.active') : t('cities.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                @click="openEditCity(city)"
                                                class="rounded-lg p-2 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-colors"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="destroyCity(city)"
                                                class="rounded-lg p-2 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-colors"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <OblastFormModal
            :show="showOblastModal"
            :form="oblastForm"
            :editing="editingOblast"
            @close="closeOblastModal"
            @submit="submitOblast"
        />

        <CityFormModal
            :show="showCityModal"
            :form="cityForm"
            :editing="editingCity"
            :oblasts="oblasts ?? []"
            @close="closeCityModal"
            @submit="submitCity"
        />

        <ConfirmModal
            :show="deleteOblastTarget !== null"
            :message="t('oblasts.delete_confirm')"
            :processing="deletingOblast"
            @confirm="confirmDestroyOblast"
            @close="deleteOblastTarget = null"
        />

        <ConfirmModal
            :show="deleteCityTarget !== null"
            :message="t('cities.delete_confirm')"
            :processing="deletingCity"
            @confirm="confirmDestroyCity"
            @close="deleteCityTarget = null"
        />
    </AdminLayout>
</template>
