<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    orderId: { type: Number, required: true },
    masters: { type: Array, default: () => [] },
    // Заказу уже назначен мастер — значит это смена, а не первое назначение.
    isReassign: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const form = useForm({ master_id: null, change_reason: '' })
const search = ref('')
// Мастер, выбранный из списка и ожидающий подтверждения (только для смены мастера).
const pendingMaster = ref(null)

watch(() => props.show, (val) => {
    if (val) {
        form.reset()
        form.clearErrors()
        search.value = ''
        pendingMaster.value = null
    }
})

function pickMaster(m) {
    if (props.isReassign) {
        pendingMaster.value = m
        return
    }
    submit(m.id)
}

function cancelPending() {
    pendingMaster.value = null
    form.change_reason = ''
    form.clearErrors()
}

function confirmPending() {
    submit(pendingMaster.value.id)
}

function submit(masterId) {
    form.master_id = masterId
    form.post(route('orders.assign', props.orderId), {
        onSuccess: () => emit('close'),
    })
}

function filteredMasters() {
    if (!search.value) { return props.masters }
    const q = search.value.toLowerCase()
    return props.masters.filter(m =>
        m.name.toLowerCase().includes(q) || m.phone.includes(q),
    )
}

function formatDistance(km) {
    if (km === null || km === undefined) { return '' }
    if (km < 1) { return `${Math.round(km * 1000)} м` }
    if (km < 10) { return `${km.toFixed(1)} км` }
    return `${Math.round(km)} км`
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <div class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ pendingMaster ? t('orders.modals.confirm_master_change_title') : t('orders.modals.assign_title') }}
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

        <!-- Экран подтверждения — показывается после выбора мастера, только при смене -->
        <div v-if="pendingMaster" class="flex flex-1 flex-col overflow-hidden px-6 py-5">
            <div class="mb-4 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-500/30 dark:bg-blue-500/10">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                    {{ pendingMaster.name.trim().charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-200">{{ pendingMaster.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ pendingMaster.phone }}</p>
                </div>
            </div>

            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                {{ t('orders.modals.change_reason_label') }}
            </label>
            <textarea
                v-model="form.change_reason"
                rows="3"
                autofocus
                :placeholder="t('orders.modals.change_reason_placeholder')"
                class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white"
            />
            <p v-if="form.errors.change_reason" class="mt-1.5 text-xs text-red-500">{{ form.errors.change_reason }}</p>

            <div class="mt-auto flex shrink-0 justify-end gap-2 pt-5">
                <button
                    type="button"
                    @click="cancelPending"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                >
                    {{ t('orders.modals.back') }}
                </button>
                <button
                    type="button"
                    :disabled="form.processing"
                    @click="confirmPending"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                >
                    {{ form.processing ? '...' : t('orders.modals.confirm_master_change') }}
                </button>
            </div>
        </div>

        <!-- Список мастеров -->
        <div v-else class="flex flex-1 flex-col overflow-hidden px-6 py-5">
            <input
                v-model="search"
                type="text"
                :placeholder="t('orders.fields.master')"
                class="mb-3 w-full shrink-0 rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white"
            />

            <p v-if="masters.length === 0" class="rounded-lg bg-yellow-50 px-4 py-6 text-center text-sm text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-300">
                {{ t('orders.modals.no_eligible_masters') }}
            </p>

            <div v-else class="flex-1 space-y-2 overflow-y-auto">
                <button
                    v-for="m in filteredMasters()"
                    :key="m.id"
                    type="button"
                    :disabled="form.processing"
                    @click="pickMaster(m)"
                    class="flex w-full items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-3 text-left transition-all hover:border-blue-400 hover:bg-blue-50/40 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-700/50 dark:hover:border-blue-500 dark:hover:bg-slate-700"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-slate-200">{{ m.name }}</span>
                            <span
                                v-if="m.distance_km !== null && m.distance_km !== undefined"
                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"
                            >
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-7.5-7-12.5A7 7 0 0112 1a7 7 0 017 7.5C19 13.5 12 21 12 21z" />
                                    <circle cx="12" cy="8.5" r="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                {{ formatDistance(m.distance_km) }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-slate-700 dark:text-slate-400"
                            >—</span>
                        </div>
                        <div class="text-xs text-gray-500">{{ m.phone }}</div>
                        <div v-if="m.categories?.length" class="mt-1 flex flex-wrap gap-1">
                            <span
                                v-for="cat in m.categories"
                                :key="cat"
                                class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                            >{{ cat }}</span>
                        </div>
                    </div>
                    <a
                        :href="`tel:${m.phone}`"
                        @click.stop
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 text-green-600 transition-colors hover:bg-green-200 dark:bg-green-500/15 dark:text-green-400"
                        :title="t('orders.actions.call_master')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    </a>
                </button>
            </div>
        </div>
        </div>
    </Modal>
</template>
