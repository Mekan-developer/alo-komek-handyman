<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    orderId: { type: Number, required: true },
    currentPrice: { type: [String, Number, null], default: null },
})

const emit = defineEmits(['close'])

const form = useForm({ final_price: '' })

watch(() => props.show, (val) => {
    if (val) {
        form.final_price = props.currentPrice ?? ''
        form.clearErrors()
    }
})

function submit() {
    form.post(route('orders.set-price', props.orderId), {
        onSuccess: () => emit('close'),
    })
}
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ t('orders.modals.price_title') }}
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
            <div class="flex-1 space-y-3 overflow-y-auto px-6 py-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    {{ t('orders.fields.final_price') }}
                </label>
                <input
                    v-model="form.final_price"
                    type="number"
                    min="0"
                    step="0.01"
                    autofocus
                    :placeholder="t('orders.modals.price_placeholder')"
                    class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white"
                    :class="{ 'border-red-400': form.errors.final_price }"
                />
                <p v-if="form.errors.final_price" class="text-xs text-red-500">{{ form.errors.final_price }}</p>
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
                    {{ form.processing ? '...' : t('layout.actions.update') }}
                </button>
            </div>
        </form>
        </div>
    </Modal>
</template>
