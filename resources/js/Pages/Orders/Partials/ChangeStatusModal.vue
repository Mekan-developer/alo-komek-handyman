<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    orderId: { type: Number, required: true },
    currentStatus: { type: String, required: true },
    statuses: { type: Array, default: () => [] },
    finalPrice: { type: [Number, String], default: null },
    masterPaymentModel: { type: String, default: null },
})

const emit = defineEmits(['close'])

const form = useForm({ status: '', cancel_reason: '' })

const warnNoPrice = computed(() =>
    form.status === 'completed'
    && ['percentage', 'salary_percentage'].includes(props.masterPaymentModel)
    && (props.finalPrice === null || props.finalPrice === '' || Number(props.finalPrice) === 0)
)

watch(() => props.show, (val) => {
    if (val) {
        form.reset()
        form.clearErrors()
    }
})

const allowedNext = computed(() => {
    const transitions = {
        pending: ['cancelled'],
        assigned: ['in_progress', 'cancelled'],
        in_progress: ['completed', 'cancelled'],
    }
    const allowed = transitions[props.currentStatus] ?? []
    return props.statuses.filter(s => allowed.includes(s.value))
})

function submit() {
    form.post(route('orders.update-status', props.orderId), {
        onSuccess: () => emit('close'),
    })
}
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ t('orders.modals.status_title') }}
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

        <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
            <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                <div class="space-y-2">
                    <button
                        v-for="s in allowedNext"
                        :key="s.value"
                        type="button"
                        @click="form.status = s.value"
                        :class="form.status === s.value
                            ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-500/10'
                            : 'border-gray-200 hover:border-blue-300 dark:border-slate-600 dark:hover:border-blue-500'"
                        class="flex w-full items-center gap-3 rounded-xl border bg-white p-3 text-left transition-all dark:bg-slate-700/30"
                    >
                        <span class="text-sm font-medium text-gray-900 dark:text-slate-200">{{ s.label }}</span>
                    </button>
                </div>

                <div v-if="form.status === 'cancelled'">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        {{ t('orders.modals.cancel_reason') }}
                    </label>
                    <textarea
                        v-model="form.cancel_reason"
                        rows="3"
                        class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white"
                    />
                </div>

                <div
                    v-if="warnNoPrice"
                    class="flex items-start gap-2.5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10"
                >
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-xs leading-relaxed text-amber-700 dark:text-amber-300">
                        {{ t('orders.modals.complete_no_price_warning') }}
                    </p>
                </div>

                <p v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</p>
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
                    :disabled="form.processing || !form.status"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                >
                    {{ form.processing ? '...' : t('layout.actions.update') }}
                </button>
            </div>
        </form>
        </div>
    </Modal>
</template>
