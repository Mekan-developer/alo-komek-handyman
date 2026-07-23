<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ClientFormModal from '@/Pages/Clients/Partials/ClientFormModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import Pagination from '@/Components/Pagination.vue'
import { formatPhone } from '@/utils/formatPhone'

const { t } = useI18n()

const props = defineProps({
    clients: { type: Object, default: null },
    oblasts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

// ── Filters ───────────────────────────────────────────────────────────────────
const selectedOblastId = ref(props.filters.oblast_id ? Number(props.filters.oblast_id) : null)
const selectedCityId = ref(props.filters.city_id ? Number(props.filters.city_id) : null)

const selectedOblast = computed(() =>
    props.oblasts.find((o) => o.id === selectedOblastId.value) ?? null,
)

const showCityFilter = computed(() =>
    selectedOblast.value !== null && selectedOblast.value.cities.length > 1,
)

const oblastCities = computed(() => selectedOblast.value?.cities ?? [])

function applyFilters() {
    router.get(
        route('clients.index'),
        {
            ...(selectedOblastId.value ? { oblast_id: selectedOblastId.value } : {}),
            ...(selectedCityId.value ? { city_id: selectedCityId.value } : {}),
        },
        { preserveScroll: true, preserveState: true },
    )
}

function onOblastChange() {
    selectedCityId.value = null
    applyFilters()
}

function onCityChange() {
    applyFilters()
}

function resetFilters() {
    selectedOblastId.value = null
    selectedCityId.value = null
    router.get(route('clients.index'), {}, { preserveScroll: true, preserveState: true })
}

const showModal = ref(false)
const editingClient = ref(null)

const form = useForm({
    city_id: null,
    name: '',
    phone: '',
})

function openCreate() {
    editingClient.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEdit(client) {
    editingClient.value = client
    form.city_id = client.city_id
    form.name = client.name
    form.phone = client.phone
    form.clearErrors()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingClient.value = null
    form.reset()
    form.clearErrors()
}

function submit() {
    if (editingClient.value) {
        form.put(route('clients.update', editingClient.value.id), {
            onSuccess: closeModal,
        })
    } else {
        form.post(route('clients.store'), {
            onSuccess: closeModal,
        })
    }
}

const deleteTarget = ref(null)
const deleting = ref(false)

function destroy(client) {
    deleteTarget.value = client
}

function confirmDelete() {
    deleting.value = true
    router.delete(route('clients.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null },
        onFinish: () => { deleting.value = false },
    })
}

const blockTarget = ref(null)
const blocking = ref(false)

function toggleBlock(client) {
    blockTarget.value = client
}

function confirmToggleBlock() {
    blocking.value = true
    router.post(route('clients.toggle-block', blockTarget.value.id), {}, {
        onSuccess: () => { blockTarget.value = null },
        onFinish: () => { blocking.value = false },
    })
}

const clientList = computed(() => props.clients?.data ?? [])
const paginationMeta = computed(() => props.clients?.meta ?? null)

const activeFilters = computed(() => ({
    ...(selectedOblastId.value ? { oblast_id: selectedOblastId.value } : {}),
    ...(selectedCityId.value ? { city_id: selectedCityId.value } : {}),
}))
</script>

<template>
    <Head :title="t('clients.title')" />

    <AdminLayout :title="t('clients.title')">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('clients.title') }}
                </h1>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('clients.add') }}
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <select
                    v-model="selectedOblastId"
                    @change="onOblastChange"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    <option :value="null">{{ t('clients.filters.all_oblasts') }}</option>
                    <option v-for="oblast in oblasts" :key="oblast.id" :value="oblast.id">
                        {{ oblast.name }}
                    </option>
                </select>

                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 -translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition-all duration-150"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-2"
                >
                    <select
                        v-if="showCityFilter"
                        v-model="selectedCityId"
                        @change="onCityChange"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        <option :value="null">{{ t('clients.filters.all_cities') }}</option>
                        <option v-for="city in oblastCities" :key="city.id" :value="city.id">
                            {{ city.name }}
                        </option>
                    </select>
                </Transition>

                <button
                    v-if="selectedOblastId"
                    @click="resetFilters"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-sm hover:bg-gray-50 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition-colors"
                >
                    {{ t('clients.filters.reset') }}
                </button>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('clients.fields.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('clients.fields.phone') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('clients.fields.city') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('clients.fields.orders_count') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('clients.fields.status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('clients.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-if="clientList.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                                    {{ t('clients.empty') }}
                                </td>
                            </tr>
                            <tr
                                v-for="(client, index) in clientList"
                                :key="client.id"
                                class="group cursor-default transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-slate-700"
                                :class="client.is_blocked ? 'opacity-60' : ''"
                            >
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ index + 1 + ((paginationMeta?.current_page ?? 1) - 1) * (paginationMeta?.per_page ?? 20) }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-200">
                                    {{ client.name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ formatPhone(client.phone) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ client.city?.name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ client.orders_count }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="client.is_blocked
                                            ? 'bg-red-100 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'
                                            : 'bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30'"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    >
                                        <span
                                            :class="client.is_blocked ? 'bg-red-400' : 'bg-green-500 dark:bg-green-400'"
                                            class="h-1.5 w-1.5 rounded-full"
                                        />
                                        {{ client.is_blocked ? t('clients.blocked') : t('clients.active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            @click="toggleBlock(client)"
                                            :title="client.is_blocked ? t('clients.unblock') : t('clients.block')"
                                            :class="client.is_blocked
                                                ? 'text-slate-400 hover:bg-green-100 hover:text-green-600 dark:hover:bg-green-500/15 dark:hover:text-green-400'
                                                : 'text-slate-400 hover:bg-amber-100 hover:text-amber-600 dark:hover:bg-amber-500/15 dark:hover:text-amber-400'"
                                            class="rounded-lg p-2 transition-all duration-150"
                                        >
                                            <svg v-if="client.is_blocked" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openEdit(client)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-all duration-150"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="destroy(client)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-all duration-150"
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
                    route-name="clients.index"
                    :route-params="activeFilters"
                />
            </div>
        </div>

        <ClientFormModal
            :show="showModal"
            :form="form"
            :editing="editingClient"
            :oblasts="oblasts"
            @close="closeModal"
            @submit="submit"
        />

        <ConfirmModal
            :show="deleteTarget !== null"
            :message="t('clients.delete_confirm')"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />

        <ConfirmModal
            :show="blockTarget !== null"
            :message="blockTarget ? t(blockTarget.is_blocked ? 'clients.unblock_confirm' : 'clients.block_confirm') : ''"
            :processing="blocking"
            :danger="false"
            @confirm="confirmToggleBlock"
            @close="blockTarget = null"
        />
    </AdminLayout>
</template>
