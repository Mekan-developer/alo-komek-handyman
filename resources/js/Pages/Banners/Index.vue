<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BannerFormModal from '@/Pages/Banners/Partials/BannerFormModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const { t } = useI18n()

const props = defineProps({
    banners: Object,
})

// ── Modal state ───────────────────────────────────────────────────────────────
const showModal = ref(false)
const editingBanner = ref(null)

const form = useForm({
    image: null,
    url: '',
    is_active: true,
    sort_order: 0,
})

function openCreate() {
    editingBanner.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEdit(banner) {
    editingBanner.value = banner
    form.image = null
    form.url = banner.url ?? ''
    form.is_active = banner.is_active
    form.sort_order = banner.sort_order
    form.clearErrors()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingBanner.value = null
    form.reset()
    form.clearErrors()
}

function submit() {
    if (editingBanner.value) {
        form.post(route('banners.update', editingBanner.value.id), {
            onSuccess: closeModal,
        })
    } else {
        form.post(route('banners.store'), {
            onSuccess: closeModal,
        })
    }
}

// ── Toggle status ─────────────────────────────────────────────────────────────
function toggle(banner) {
    router.post(route('banners.toggle', banner.id))
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref(null)
const deleting = ref(false)

function destroy(banner) {
    deleteTarget.value = banner
}

function confirmDelete() {
    deleting.value = true
    router.delete(route('banners.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null },
        onFinish: () => { deleting.value = false },
    })
}

// ── Pagination ────────────────────────────────────────────────────────────────
const currentPage = computed(() => props.banners?.current_page ?? 1)
const lastPage = computed(() => props.banners?.last_page ?? 1)
const bannerList = computed(() => props.banners?.data ?? [])
</script>

<template>
    <AdminLayout :title="t('banners.title')">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('banners.title') }}
                </h1>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('banners.add') }}
                </button>
            </div>

            <!-- Table card -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    #
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('banners.image') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('banners.url') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('banners.status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('banners.created_at') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('banners.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-if="bannerList.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                                    {{ t('banners.empty') }}
                                </td>
                            </tr>
                            <tr
                                v-for="(banner, index) in bannerList"
                                :key="banner.id"
                                class="group transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-slate-700"
                            >
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ index + 1 + (currentPage - 1) * 15 }}
                                </td>

                                <!-- Banner thumbnail — 3:1 ratio -->
                                <td class="px-6 py-3">
                                    <a
                                        v-if="banner.url"
                                        :href="banner.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="block overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-slate-600"
                                        style="width: 180px; aspect-ratio: 3 / 1;"
                                    >
                                        <img
                                            :src="banner.image_url"
                                            :alt="`banner-${banner.id}`"
                                            class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                        />
                                    </a>
                                    <div
                                        v-else
                                        class="overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-slate-600"
                                        style="width: 180px; aspect-ratio: 3 / 1;"
                                    >
                                        <img
                                            :src="banner.image_url"
                                            :alt="`banner-${banner.id}`"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                </td>

                                <!-- URL -->
                                <td class="px-6 py-4 text-sm">
                                    <a
                                        v-if="banner.url"
                                        :href="banner.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="max-w-xs truncate text-blue-600 hover:underline dark:text-blue-400"
                                        style="display: block; max-width: 240px;"
                                    >
                                        {{ banner.url }}
                                    </a>
                                    <span v-else class="text-gray-400 dark:text-slate-500">
                                        {{ t('banners.no_url') }}
                                    </span>
                                </td>

                                <!-- Status toggle -->
                                <td class="px-6 py-4">
                                    <button
                                        type="button"
                                        @click="toggle(banner)"
                                        :class="banner.is_active
                                            ? 'bg-green-500 hover:bg-green-600'
                                            : 'bg-gray-300 hover:bg-gray-400 dark:bg-slate-500 dark:hover:bg-slate-400'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                                        :title="banner.is_active ? t('banners.active') : t('banners.inactive')"
                                    >
                                        <span
                                            :class="banner.is_active ? 'translate-x-5' : 'translate-x-0'"
                                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-300"
                                        />
                                    </button>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ banner.created_at }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            @click="openEdit(banner)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-all duration-150"
                                            :title="t('banners.edit')"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="destroy(banner)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-all duration-150"
                                            :title="t('banners.delete_confirm')"
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

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ t('banners.title') }}</p>
                    <div class="flex gap-1">
                        <Link
                            v-for="page in lastPage"
                            :key="page"
                            :href="route('banners.index', { page })"
                            :class="page === currentPage
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700'"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm font-medium transition-colors"
                        >
                            {{ page }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner form modal -->
        <BannerFormModal
            :show="showModal"
            :form="form"
            :editing="editingBanner"
            @close="closeModal"
            @submit="submit"
        />

        <ConfirmModal
            :show="deleteTarget !== null"
            :message="t('banners.delete_confirm')"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />
    </AdminLayout>
</template>
