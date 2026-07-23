<script setup>
import { ref, computed, watch } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MasterFormModal from '@/Pages/Masters/Partials/MasterFormModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import Pagination from '@/Components/Pagination.vue'
import CityFilterSelect from '@/Components/CityFilterSelect.vue'
import { formatPhone } from '@/utils/formatPhone'

const { t } = useI18n()

const props = defineProps({
    masters: Object,
    oblasts: Array,
    categories: Array,
    paymentModels: Array,
    filters: { type: Object, default: () => ({}) },
})

// ── Modal state ───────────────────────────────────────────────────────────────
const showModal = ref(false)
const editingMaster = ref(null)

const form = useForm({
    city_id: null,
    name: '',
    phone: '',
    payment_model: null,
    payment_value: 0,
    monthly_salary: 0,
    access_expires_at: '',
    is_active: true,
    category_ids: [],
    photo: null,
})

function openCreate() {
    editingMaster.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEdit(master) {
    editingMaster.value = master
    form.city_id = master.city_id
    form.name = master.name
    form.phone = master.phone
    form.payment_model = master.payment_model
    form.payment_value = master.payment_value
    form.monthly_salary = master.monthly_salary
    form.access_expires_at = master.access_expires_at
        ? master.access_expires_at.replace(' ', 'T').slice(0, 16)
        : ''
    form.is_active = master.is_active
    form.category_ids = master.category_ids ? [...master.category_ids] : []
    form.photo = null
    form.clearErrors()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingMaster.value = null
    form.reset()
    form.clearErrors()
}

function submit() {
    // File uploads require multipart/POST — the update route is registered as POST.
    if (editingMaster.value) {
        form.post(route('masters.update', editingMaster.value.id), {
            onSuccess: closeModal,
        })
    } else {
        form.post(route('masters.store'), {
            onSuccess: closeModal,
        })
    }
}

const deleteTarget = ref(null)
const deleting = ref(false)

function destroy(master) {
    deleteTarget.value = master
}

function confirmDelete() {
    deleting.value = true
    router.delete(route('masters.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null },
        onFinish: () => { deleting.value = false },
    })
}

const resetBalanceTarget = ref(null)
const resettingBalance = ref(false)

function resetBalance(master) {
    resetBalanceTarget.value = master
}

function confirmResetBalance() {
    resettingBalance.value = true
    router.post(route('masters.reset-balance', resetBalanceTarget.value.id), {}, {
        onSuccess: () => { resetBalanceTarget.value = null },
        onFinish: () => { resettingBalance.value = false },
    })
}

// ── Filters ────────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
const cityFilter = ref(props.filters.city_id ? Number(props.filters.city_id) : null)

const activeFilters = computed(() => ({
    ...(search.value ? { search: search.value } : {}),
    ...(cityFilter.value ? { city_id: cityFilter.value } : {}),
}))

const hasActiveFilters = computed(() => Boolean(search.value || cityFilter.value))

function applyFilters() {
    router.get(route('masters.index'), activeFilters.value, {
        preserveState: true, preserveScroll: true, replace: true,
    })
}

function resetFilters() {
    search.value = ''
    cityFilter.value = null
}

watch(cityFilter, applyFilters)

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
})

// ── Pagination ────────────────────────────────────────────────────────────────
const masterList = computed(() => props.masters?.data ?? [])
const paginationMeta = computed(() => props.masters?.meta ?? null)
</script>

<template>
    <AdminLayout :title="t('masters.title')">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('masters.title') }}
                </h1>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('masters.map')"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                        </svg>
                        {{ t('masters.view_map') }}
                    </Link>
                    <button
                        @click="openCreate"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ t('masters.add') }}
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="t('masters.search')"
                        class="w-64 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    />
                </div>
                <CityFilterSelect
                    v-model="cityFilter"
                    :oblasts="oblasts"
                    :all-oblasts-label="t('masters.filters.all_oblasts')"
                    :all-cities-label="t('masters.filters.all_cities')"
                    select-class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                />
                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-sm hover:bg-gray-50 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition-colors"
                >
                    {{ t('masters.filters.reset') }}
                </button>
            </div>

            <!-- Table card -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.phone') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.city') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.rating') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.payment_model') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.access_expires_at') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.balance') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('masters.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-if="masterList.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                                    {{ t('masters.empty') }}
                                </td>
                            </tr>
                            <tr
                                v-for="(master, index) in masterList"
                                :key="master.id"
                                class="group cursor-default transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-slate-700"
                            >
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ index + 1 + ((paginationMeta?.current_page ?? 1) - 1) * (paginationMeta?.per_page ?? 15) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-9 flex-shrink-0 overflow-hidden rounded-md bg-gray-100 ring-1 ring-gray-200 dark:bg-slate-700 dark:ring-slate-600">
                                            <img
                                                v-if="master.photo_url"
                                                :src="master.photo_url"
                                                :alt="master.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="flex h-full w-full items-center justify-center text-gray-300 dark:text-slate-500">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-slate-300">
                                                {{ master.name }}
                                            </span>
                                            <div v-if="master.categories?.length" class="mt-1 flex flex-wrap gap-1">
                                                <span
                                                    v-for="cat in master.categories"
                                                    :key="cat.id"
                                                    class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                                                >
                                                    {{ cat.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-slate-400">
                                    {{ formatPhone(master.phone) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ master.city?.name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div v-if="master.reviews_count > 0" class="flex items-center gap-1">
                                        <svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.446a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.286-3.957a1 1 0 00-.363-1.118L2.02 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.958z" />
                                        </svg>
                                        <span class="font-medium text-gray-900 dark:text-slate-200">{{ master.reviews_avg_rating }}</span>
                                        <span class="text-xs text-gray-400 dark:text-slate-500">({{ master.reviews_count }})</span>
                                    </div>
                                    <span v-else class="text-gray-300 dark:text-slate-600">—</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ paymentModels.find(pm => pm.value === master.payment_model)?.label ?? master.payment_model }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        <span
                                            :class="master.is_active
                                                ? 'bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30'
                                                : 'bg-red-100 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        >
                                            <span
                                                :class="master.is_active ? 'bg-green-500 dark:bg-green-400' : 'bg-red-400'"
                                                class="h-1.5 w-1.5 rounded-full"
                                            />
                                            {{ master.is_active ? t('masters.active') : t('masters.inactive') }}
                                        </span>
                                        <span
                                            :class="master.is_available
                                                ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30'
                                                : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200 dark:bg-slate-700 dark:text-slate-400 dark:ring-slate-600'"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        >
                                            <span
                                                :class="master.is_available ? 'bg-blue-500 dark:bg-blue-400' : 'bg-gray-400 dark:bg-slate-500'"
                                                class="h-1.5 w-1.5 rounded-full"
                                            />
                                            {{ master.is_available ? t('masters.available') : t('masters.unavailable') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    <span v-if="master.access_expires_at">
                                        {{ master.access_expires_at }}
                                    </span>
                                    <span v-else class="text-gray-300 dark:text-slate-600">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-200">
                                            {{ Number(master.balance ?? 0).toFixed(2) }}
                                        </span>
                                        <button
                                            v-if="Number(master.balance) > 0"
                                            @click="resetBalance(master)"
                                            class="rounded px-2 py-0.5 text-xs font-medium text-orange-600 ring-1 ring-orange-300 hover:bg-orange-50 dark:text-orange-400 dark:ring-orange-500/40 dark:hover:bg-orange-500/10 transition-colors"
                                        >
                                            {{ t('masters.reset_balance') }}
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            @click="openEdit(master)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-all duration-150"
                                            title="Edit"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="destroy(master)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-all duration-150"
                                            title="Delete"
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

                <Pagination
                    v-if="paginationMeta"
                    :meta="paginationMeta"
                    route-name="masters.index"
                    :route-params="activeFilters"
                />
            </div>
        </div>

        <!-- Master form modal -->
        <MasterFormModal
            :show="showModal"
            :form="form"
            :editing="editingMaster"
            :oblasts="oblasts"
            :categories="categories"
            :payment-models="paymentModels"
            @close="closeModal"
            @submit="submit"
        />

        <ConfirmModal
            :show="deleteTarget !== null"
            :message="t('masters.delete_confirm')"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />

        <ConfirmModal
            :show="resetBalanceTarget !== null"
            :message="t('masters.reset_balance_confirm')"
            :processing="resettingBalance"
            :danger="false"
            @confirm="confirmResetBalance"
            @close="resetBalanceTarget = null"
        />
    </AdminLayout>
</template>
