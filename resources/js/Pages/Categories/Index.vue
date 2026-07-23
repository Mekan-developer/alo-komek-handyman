<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CategoryFormModal from '@/Pages/Categories/Partials/CategoryFormModal.vue'
import CategoryContentModal from '@/Pages/Categories/Partials/CategoryContentModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import ServiceIcon from '@/Components/ServiceIcon.vue'
import Pagination from '@/Components/Pagination.vue'

const { t } = useI18n()

const props = defineProps({
    categories: Object,
    parentCategories: Array,
    iconGroups: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
})

// ── Modal state ───────────────────────────────────────────────────────────────
const showModal = ref(false)
const editingCategory = ref(null)

const form = useForm({
    name_ru: '',
    name_tk: '',
    is_active: true,
    parent_id: null,
    icon_type: null,
    icon: null,
    icon_file: null,
})

function openCreate() {
    editingCategory.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openCreateChild(parent) {
    editingCategory.value = null
    form.reset()
    form.clearErrors()
    form.parent_id = parent.id
    showModal.value = true
}

function openEdit(category) {
    editingCategory.value = category
    form.name_ru = category.name_ru
    form.name_tk = category.name_tk
    form.is_active = category.is_active
    form.parent_id = category.parent_id
    form.icon_type = category.icon_type
    // Only preset keys are valid `icon` values; a custom icon keeps its file
    // server-side (previewed via existingIconUrl), so send null here.
    form.icon = category.icon_type === 'preset' ? category.icon : null
    form.icon_file = null
    form.clearErrors()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingCategory.value = null
    form.reset()
    form.clearErrors()
}

function submit() {
    const isEdit = Boolean(editingCategory.value)

    // Icons may be uploaded as files; multipart can't ride on PUT, so we always
    // POST and spoof the method for updates (Laravel reads `_method`).
    form.transform((data) => (isEdit ? { ...data, _method: 'put' } : data))

    const url = isEdit
        ? route('categories.update', editingCategory.value.id)
        : route('categories.store')

    form.post(url, {
        forceFormData: true,
        onSuccess: closeModal,
    })
}

// ── Content modal ─────────────────────────────────────────────────────────────
const showContentModal = ref(false)
const contentCategory = ref(null)

function openContent(category) {
    contentCategory.value = category
    showContentModal.value = true
}

function closeContentModal() {
    showContentModal.value = false
    contentCategory.value = null
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref(null)
const deleting = ref(false)

function destroy(category) {
    deleteTarget.value = category
}

function confirmDelete() {
    deleting.value = true
    router.delete(route('categories.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null },
        onFinish: () => { deleting.value = false },
    })
}

// ── Search ────────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('categories.index'), { search: search.value || undefined }, {
            preserveState: true, preserveScroll: true, replace: true,
        })
    }, 350)
})

// ── Pagination ────────────────────────────────────────────────────────────────
const categoryList = computed(() => props.categories?.data ?? [])
const paginationMeta = computed(() => props.categories?.meta ?? null)

</script>

<template>
    <AdminLayout :title="t('categories.title')">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('categories.title') }}
                </h1>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            v-model="search"
                            type="search"
                            :placeholder="t('categories.search')"
                            class="w-56 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>
                    <button
                        @click="openCreate"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ t('categories.add') }}
                    </button>
                </div>
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
                                    {{ t('categories.name') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('categories.parent') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('categories.status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('categories.created_at') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                    {{ t('categories.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-if="categoryList.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                                    {{ t('categories.empty') }}
                                </td>
                            </tr>
                            <tr
                                v-for="(category, index) in categoryList"
                                :key="category.id"
                                :class="category.parent_id
                                    ? 'bg-gray-50/50 dark:bg-slate-800/50'
                                    : ''"
                                class="group cursor-default transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-slate-700"
                            >
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ index + 1 + ((paginationMeta?.current_page ?? 1) - 1) * (paginationMeta?.per_page ?? 15) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <!-- Иконка категории; если не задана — fallback: папка (корень) / вложение (подкатегория) -->
                                        <span
                                            :class="[
                                                category.parent_id ? 'ml-4' : '',
                                                category.parent_id
                                                    ? 'bg-purple-100 text-purple-500 dark:bg-purple-500/20 dark:text-purple-400'
                                                    : 'bg-blue-100 text-blue-500 dark:bg-blue-500/20 dark:text-blue-400',
                                            ]"
                                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md"
                                        >
                                            <ServiceIcon v-if="category.icon_url" :url="category.icon_url" class="h-4 w-4" />
                                            <svg v-else-if="!category.parent_id" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                            </svg>
                                            <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h3.75" />
                                            </svg>
                                        </span>
                                        <span
                                            :class="category.parent_id
                                                ? 'text-sm text-gray-600 dark:text-slate-400'
                                                : 'text-sm font-semibold text-gray-900 dark:text-slate-200'"
                                        >
                                            {{ category.name }}
                                        </span>
                                        <span
                                            v-if="category.parent_id"
                                            class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/20 dark:text-purple-300"
                                        >
                                            {{ t('categories.type_child') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    <span v-if="category.parent" class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                        </svg>
                                        {{ category.parent.name }}
                                    </span>
                                    <span v-else class="text-gray-300 dark:text-slate-600">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="category.is_active
                                            ? 'bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30'
                                            : 'bg-red-100 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    >
                                        <span
                                            :class="category.is_active ? 'bg-green-500 dark:bg-green-400' : 'bg-red-400'"
                                            class="h-1.5 w-1.5 rounded-full"
                                        />
                                        {{ category.is_active ? t('categories.active') : t('categories.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ category.created_at }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Кнопка "Добавить подкатегорию" только для корневых -->
                                        <button
                                            v-if="!category.parent_id"
                                            @click="openCreateChild(category)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-purple-100 hover:text-purple-600 dark:hover:bg-purple-500/15 dark:hover:text-purple-400 transition-all duration-150"
                                            :title="t('categories.add_child')"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>

                                        <!-- Кнопка "Контент" только для подкатегорий -->
                                        <button
                                            v-if="category.parent_id"
                                            @click="openContent(category)"
                                            :class="category.content
                                                ? 'text-indigo-500 hover:bg-indigo-100 hover:text-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-500/15 dark:hover:text-indigo-300'
                                                : 'text-slate-400 hover:bg-indigo-100 hover:text-indigo-600 dark:hover:bg-indigo-500/15 dark:hover:text-indigo-400'"
                                            class="rounded-lg p-2 transition-all duration-150"
                                            :title="category.content ? t('categories.content_edit') : t('categories.content_add')"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openEdit(category)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-all duration-150"
                                            :title="t('categories.edit')"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="destroy(category)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-all duration-150"
                                            :title="t('categories.delete_confirm')"
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
                    route-name="categories.index"
                    :route-params="search ? { search } : {}"
                />
            </div>
        </div>

        <!-- Category form modal (create & edit) -->
        <CategoryFormModal
            :show="showModal"
            :form="form"
            :editing="editingCategory"
            :parent-categories="parentCategories"
            :icon-groups="iconGroups"
            @close="closeModal"
            @submit="submit"
        />

        <CategoryContentModal
            :show="showContentModal"
            :category="contentCategory"
            @close="closeContentModal"
        />

        <ConfirmModal
            :show="deleteTarget !== null"
            :message="t('categories.delete_confirm')"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />
    </AdminLayout>
</template>
