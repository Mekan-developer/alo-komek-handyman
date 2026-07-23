<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    form: { type: Object, required: true },
    editing: { type: Object, default: null },
})

const emit = defineEmits(['close', 'submit'])

// Only Administrator and Manager can be assigned. The Operator option is kept
// solely when editing an existing operator so their role still renders.
const roleOptions = computed(() => {
    const options = [
        { value: 'administrator', label: t('users.roles.administrator') },
        { value: 'manager', label: t('users.roles.manager') },
    ]

    if (props.editing?.role === 'operator') {
        options.push({ value: 'operator', label: t('users.roles.operator') })
    }

    return options
})
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="flex h-full flex-col">
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ editing ? t('users.edit') : t('users.add') }}
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

            <form @submit.prevent="emit('submit')" class="flex flex-1 flex-col overflow-hidden">
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <!-- Name -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('users.fields.name') }} <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-slate-500">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </span>
                            <input
                                v-model="form.name"
                                type="text"
                                autofocus
                                :placeholder="t('users.placeholders.name')"
                                :class="form.errors.name
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                                class="w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-4 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all"
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('users.fields.email') }} <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-slate-500">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <input
                                v-model="form.email"
                                type="email"
                                :placeholder="t('users.placeholders.email')"
                                :class="form.errors.email
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                                class="w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-4 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('users.fields.role') }} <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-slate-500">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <select
                                v-model="form.role"
                                :class="form.errors.role
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                                class="w-full appearance-none rounded-xl border bg-gray-50 py-3 pl-11 pr-10 text-sm text-gray-900 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:focus:bg-slate-700 transition-all"
                            >
                                <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 dark:text-slate-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </div>
                        <p v-if="form.errors.role" class="mt-1.5 text-xs text-red-500">{{ form.errors.role }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('users.fields.password') }}
                            <span v-if="!editing" class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-slate-500">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </span>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="t('users.placeholders.password')"
                                :class="form.errors.password
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                                class="w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-4 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all"
                            />
                        </div>
                        <p v-if="editing" class="mt-1 text-xs text-gray-400 dark:text-slate-500">
                            {{ t('users.password_hint') }}
                        </p>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('users.fields.password_confirmation') }}
                            <span v-if="!editing" class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-slate-500">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="t('users.placeholders.password_confirmation')"
                                :class="form.errors.password_confirmation
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                                class="w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-4 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all"
                            />
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
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
                        {{ form.processing ? '...' : (editing ? t('layout.actions.update') : t('layout.actions.save')) }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
