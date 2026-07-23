<script setup>
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

defineProps({
    show: { type: Boolean, required: true },
    form: { type: Object, required: true },
    editing: { type: Object, default: null },
    oblasts: { type: Array, required: true },
})

const emit = defineEmits(['close', 'submit'])
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ editing ? t('regions.edit') : t('regions.add') }}
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
                <!-- Oblast select -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('regions.oblast') }}
                        <span class="text-red-400">*</span>
                    </label>
                    <select
                        v-model="form.oblast_id"
                        :class="form.errors.oblast_id
                            ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                        class="w-full rounded-xl border bg-gray-50 px-4 py-3 text-sm text-gray-900 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:focus:bg-slate-700 transition-all"
                    >
                        <option value="" disabled>{{ t('regions.oblast_placeholder') }}</option>
                        <option v-for="oblast in oblasts" :key="oblast.id" :value="oblast.id">
                            {{ oblast.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.oblast_id" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.oblast_id }}
                    </p>
                </div>

                <!-- Name -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('regions.name') }}
                        <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        :placeholder="t('regions.name_placeholder')"
                        :class="form.errors.name
                            ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'"
                        class="w-full rounded-xl border bg-gray-50 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Status toggle -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('regions.status') }}
                    </label>
                    <button
                        type="button"
                        @click="form.is_active = !form.is_active"
                        :class="form.is_active
                            ? 'border-green-200 bg-green-50 dark:border-green-800/60 dark:bg-green-900/20'
                            : 'border-gray-300 bg-gray-50 dark:border-slate-600 dark:bg-slate-700/40'"
                        class="flex w-full items-center justify-between rounded-xl border px-4 py-3 text-left shadow-sm transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-blue-500/20"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="form.is_active
                                    ? 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-400 dark:bg-slate-600 dark:text-slate-400'"
                                class="flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                            >
                                <svg v-if="form.is_active" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </span>
                            <span
                                :class="form.is_active ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-slate-400'"
                                class="text-sm font-medium"
                            >
                                {{ form.is_active ? t('regions.active') : t('regions.inactive') }}
                            </span>
                        </div>
                        <div
                            :class="form.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-500'"
                            class="relative inline-flex h-5 w-10 flex-shrink-0 rounded-full transition-colors duration-300"
                        >
                            <span
                                :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                                class="my-0.5 inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-300"
                            />
                        </div>
                    </button>
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
