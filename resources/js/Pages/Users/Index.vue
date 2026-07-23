<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserFormModal from '@/Pages/Users/Partials/UserFormModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const { t } = useI18n()

const props = defineProps({
    users: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
})

// ── Filters ───────────────────────────────────────────────────────────────────
const selectedRole = ref(props.filters.role ?? null)

function applyFilters() {
    router.get(
        route('users.index'),
        { ...(selectedRole.value ? { role: selectedRole.value } : {}) },
        { preserveScroll: true, preserveState: true },
    )
}

function resetFilters() {
    selectedRole.value = null
    router.get(route('users.index'), {}, { preserveScroll: true, preserveState: true })
}

// ── Modal / Form ──────────────────────────────────────────────────────────────
const showModal = ref(false)
const editingUser = ref(null)

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'manager',
})

function openCreate() {
    editingUser.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEdit(user) {
    editingUser.value = user
    form.name = user.name
    form.email = user.email
    form.role = user.role
    form.password = ''
    form.password_confirmation = ''
    form.clearErrors()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingUser.value = null
    form.reset()
    form.clearErrors()
}

function submit() {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: closeModal,
        })
    } else {
        form.post(route('users.store'), {
            onSuccess: closeModal,
        })
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref(null)
const deleting = ref(false)

function destroy(user) {
    deleteTarget.value = user
}

function confirmDelete() {
    deleting.value = true
    router.delete(route('users.destroy', deleteTarget.value.id), {
        onSuccess: () => { deleteTarget.value = null },
        onFinish: () => { deleting.value = false },
    })
}

// ── Pagination ────────────────────────────────────────────────────────────────
const currentPage = computed(() => props.users?.current_page ?? 1)
const lastPage = computed(() => props.users?.last_page ?? 1)
const userList = computed(() => props.users?.data ?? [])
</script>

<template>
    <Head :title="t('users.title')" />

    <AdminLayout :title="t('users.title')">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('users.title') }}
                </h1>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('users.add') }}
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <select
                    v-model="selectedRole"
                    @change="applyFilters"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    <option :value="null">{{ t('users.filters.all_roles') }}</option>
                    <option value="administrator">{{ t('users.roles.administrator') }}</option>
                    <option value="manager">{{ t('users.roles.manager') }}</option>
                    <option value="operator">{{ t('users.roles.operator') }}</option>
                </select>

                <button
                    v-if="selectedRole"
                    @click="resetFilters"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-sm hover:bg-gray-50 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200 transition-colors"
                >
                    {{ t('users.filters.reset') }}
                </button>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('users.fields.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('users.fields.email') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('users.fields.role') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('users.fields.created_at') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('users.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr v-if="userList.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                                    {{ t('users.empty') }}
                                </td>
                            </tr>
                            <tr
                                v-for="(user, index) in userList"
                                :key="user.id"
                                class="group cursor-default transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-slate-700"
                                :class="user.is_current ? 'bg-blue-50/30 dark:bg-blue-900/10' : ''"
                            >
                                <td class="px-6 py-4 text-sm text-gray-400 dark:text-slate-500">
                                    {{ index + 1 + (currentPage - 1) * 20 }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-200">
                                    {{ user.name }}
                                    <span
                                        v-if="user.is_current"
                                        class="ml-1.5 text-xs text-slate-400 dark:text-slate-500"
                                    >({{ t('layout.header.profile') }})</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="{
                                            'bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30': user.role_color === 'indigo',
                                            'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30': user.role_color === 'blue',
                                            'bg-green-100 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30': user.role_color === 'green',
                                        }"
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                                    >
                                        {{ user.role_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                    {{ user.created_at }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            @click="openEdit(user)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500/15 dark:hover:text-blue-400 transition-all duration-150"
                                            :title="t('users.edit')"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="user.can_delete"
                                            @click="destroy(user)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15 dark:hover:text-red-400 transition-all duration-150"
                                            :title="t('users.actions')"
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

                <div v-if="lastPage > 1" class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ t('users.title') }}</p>
                    <div class="flex gap-1">
                        <Link
                            v-for="page in lastPage"
                            :key="page"
                            :href="route('users.index', { page })"
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

        <UserFormModal
            :show="showModal"
            :form="form"
            :editing="editingUser"
            @close="closeModal"
            @submit="submit"
        />

        <ConfirmModal
            :show="deleteTarget !== null"
            :message="t('users.delete_confirm')"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />
    </AdminLayout>
</template>
