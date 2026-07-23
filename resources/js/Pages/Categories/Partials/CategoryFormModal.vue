<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'
import IconPicker from '@/Components/IconPicker.vue'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    form: { type: Object, required: true },
    editing: { type: Object, default: null },
    parentCategories: { type: Array, default: () => [] },
    iconGroups: { type: Object, default: () => ({}) },
})

const existingIconUrl = computed(() => props.editing?.icon_url ?? null)

const emit = defineEmits(['close', 'submit'])

const isChildType = ref(false)

watch(() => props.show, (opened) => {
    if (opened) {
        isChildType.value = props.form.parent_id !== null
    }
})

function setType(child) {
    isChildType.value = child
    if (!child) {
        props.form.parent_id = null
    }
}

const inputBase = 'w-full rounded-xl border bg-gray-50 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all'
const inputNormal = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500'
const inputError = 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="flex h-full flex-col">
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <div class="flex items-center gap-2.5">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ editing ? t('categories.edit') : t('categories.add') }}
                </h2>
                <span
                    v-if="!editing"
                    :class="isChildType
                        ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300'
                        : 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300'"
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                >
                    {{ isChildType ? t('categories.type_child') : t('categories.type_root') }}
                </span>
            </div>
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

        <!-- Body -->
        <form @submit.prevent="emit('submit')" class="flex flex-1 flex-col overflow-hidden">
            <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">

                <!-- Type selector (только при создании) -->
                <div v-if="!editing">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-slate-500">
                        Тип категории
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Корневая -->
                        <button
                            type="button"
                            @click="setType(false)"
                            :class="!isChildType
                                ? 'border-blue-500 bg-blue-50 dark:border-blue-500/70 dark:bg-blue-500/10'
                                : 'border-gray-200 bg-gray-50 hover:border-gray-300 dark:border-slate-600 dark:bg-slate-700/40 dark:hover:border-slate-500'"
                            class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                        >
                            <span
                                :class="!isChildType
                                    ? 'bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400'
                                    : 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500'"
                                class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg transition-colors"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                            </span>
                            <div>
                                <p
                                    :class="!isChildType ? 'text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-slate-400'"
                                    class="text-sm font-semibold"
                                >
                                    {{ t('categories.type_root') }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">
                                    {{ t('categories.type_root_desc') }}
                                </p>
                            </div>
                        </button>

                        <!-- Подкатегория -->
                        <button
                            type="button"
                            @click="setType(true)"
                            :class="isChildType
                                ? 'border-purple-500 bg-purple-50 dark:border-purple-500/70 dark:bg-purple-500/10'
                                : 'border-gray-200 bg-gray-50 hover:border-gray-300 dark:border-slate-600 dark:bg-slate-700/40 dark:hover:border-slate-500'"
                            class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                        >
                            <span
                                :class="isChildType
                                    ? 'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400'
                                    : 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500'"
                                class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg transition-colors"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                            </span>
                            <div>
                                <p
                                    :class="isChildType ? 'text-purple-700 dark:text-purple-300' : 'text-gray-600 dark:text-slate-400'"
                                    class="text-sm font-semibold"
                                >
                                    {{ t('categories.type_child') }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">
                                    {{ t('categories.type_child_desc') }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Родительская категория (только для дочерней или при редактировании) -->
                <div v-if="isChildType || editing">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('categories.parent') }}
                        <span v-if="isChildType && !editing" class="text-red-400">*</span>
                    </label>
                    <select
                        v-model="form.parent_id"
                        :class="[inputBase, form.errors.parent_id ? inputError : inputNormal]"
                    >
                        <option :value="null">{{ t('categories.parent_none') }}</option>
                        <option
                            v-for="cat in parentCategories"
                            :key="cat.id"
                            :value="cat.id"
                            :disabled="editing && cat.id === editing.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.parent_id" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.parent_id }}
                    </p>
                </div>

                <!-- Название (рус.) -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('categories.name_ru') }} <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="form.name_ru"
                        type="text"
                        :placeholder="t('categories.name_ru_placeholder')"
                        autofocus
                        :class="[inputBase, form.errors.name_ru ? inputError : inputNormal]"
                    />
                    <p v-if="form.errors.name_ru" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.name_ru }}
                    </p>
                </div>

                <!-- Название (туркм.) -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('categories.name_tk') }} <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="form.name_tk"
                        type="text"
                        :placeholder="t('categories.name_tk_placeholder')"
                        :class="[inputBase, form.errors.name_tk ? inputError : inputNormal]"
                    />
                    <p v-if="form.errors.name_tk" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.name_tk }}
                    </p>
                </div>

                <!-- Иконка -->
                <div>
                    <IconPicker
                        :form="form"
                        :icon-groups="iconGroups"
                        :existing-icon-url="existingIconUrl"
                    />
                    <p v-if="form.errors.icon || form.errors.icon_file" class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        {{ form.errors.icon || form.errors.icon_file }}
                    </p>
                </div>

                <!-- Статус -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('categories.status') }}
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
                                {{ form.is_active ? t('categories.active') : t('categories.inactive') }}
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

            <!-- Footer -->
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
                    :class="isChildType && !editing ? 'bg-purple-600 hover:bg-purple-700' : 'bg-blue-600 hover:bg-blue-700'"
                    class="rounded-lg px-5 py-2 text-sm font-medium text-white disabled:opacity-50 transition-colors"
                >
                    {{ form.processing ? '...' : (editing ? t('layout.actions.update') : t('layout.actions.save')) }}
                </button>
            </div>
        </form>
        </div>
    </Modal>
</template>
