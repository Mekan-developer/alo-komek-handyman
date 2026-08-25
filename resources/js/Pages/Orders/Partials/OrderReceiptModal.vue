<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'
import { formatPhone } from '@/utils/formatPhone'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    receipt: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const appName = import.meta.env.VITE_APP_NAME ?? 'Alo-komek'

const hasDiscount = computed(() => Number(props.receipt?.discount_percent ?? 0) > 0)

/** Чек печатается с запятой в качестве десятичного разделителя — как кассовая лента. */
function money(value) {
    return Number(value ?? 0).toFixed(2).replace('.', ',')
}

function printReceipt() {
    window.print()
}
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('close')">
        <div v-if="receipt" class="flex h-full flex-col">
            <!-- Заголовок модалки — на печать не идёт -->
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 print:hidden dark:border-slate-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ t('orders.receipt.title') }}
                    <span class="ml-1 font-mono text-sm text-gray-400">{{ receipt.number }}</span>
                </h2>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                    @click="emit('close')"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-100 p-4 dark:bg-slate-900">
                <!-- Лента чека: всегда белая — это лист бумаги, а не элемент интерфейса -->
                <div class="receipt-print mx-auto max-w-[80mm] bg-white px-5 py-6 font-mono text-[11px] leading-relaxed text-black shadow-sm">
                    <div class="flex flex-col items-center gap-1 pb-4 text-center">
                        <span class="text-sm font-bold uppercase tracking-widest">{{ appName }}</span>
                        <span class="text-[10px] uppercase tracking-wider">{{ t('orders.receipt.title') }}</span>
                    </div>

                    <div class="flex flex-col gap-0.5 border-t border-dashed border-black/40 pt-3">
                        <div class="flex justify-between gap-3">
                            <span>{{ t('orders.receipt.date') }}: {{ receipt.issued_date }}</span>
                            <span>{{ t('orders.receipt.number') }}: {{ receipt.number }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span>{{ t('orders.receipt.time') }}: {{ receipt.issued_time }}</span>
                            <span>№ {{ receipt.order_id }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-col gap-0.5 border-t border-dashed border-black/40 pt-3">
                        <div class="flex justify-between gap-3">
                            <span class="shrink-0">{{ t('orders.receipt.client') }}:</span>
                            <span class="text-right font-semibold">{{ receipt.client_name }}</span>
                        </div>
                        <div v-if="receipt.client_phone" class="flex justify-between gap-3">
                            <span class="shrink-0" />
                            <span class="text-right">{{ formatPhone(receipt.client_phone) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="shrink-0">{{ t('orders.receipt.master') }}:</span>
                            <span class="text-right font-semibold">{{ receipt.master_name ?? '—' }}</span>
                        </div>
                        <div v-if="receipt.master_phone" class="flex justify-between gap-3">
                            <span class="shrink-0" />
                            <span class="text-right">{{ formatPhone(receipt.master_phone) }}</span>
                        </div>
                        <div v-if="receipt.category_name" class="flex justify-between gap-3">
                            <span class="shrink-0">{{ t('orders.receipt.category') }}:</span>
                            <span class="text-right">{{ receipt.category_name }}</span>
                        </div>
                    </div>

                    <!-- Позиции -->
                    <div class="mt-3 border-t border-dashed border-black/40 pt-2">
                        <div class="flex justify-between gap-3 border-b border-black/60 pb-1 font-bold uppercase">
                            <span>{{ t('orders.receipt.item') }}</span>
                            <span>{{ t('orders.receipt.price') }}</span>
                        </div>

                        <div v-if="receipt.items?.length" class="flex flex-col">
                            <div
                                v-for="item in receipt.items"
                                :key="item.id"
                                class="flex justify-between gap-3 border-b border-dotted border-black/25 py-1.5"
                            >
                                <span class="min-w-0 break-words">{{ item.title }}</span>
                                <span class="shrink-0 tabular-nums">{{ money(item.price) }}</span>
                            </div>
                        </div>
                        <p v-else class="py-2 text-center text-[10px] italic">
                            {{ t('orders.receipt.no_items') }}
                        </p>
                    </div>

                    <!-- Итоги -->
                    <div class="mt-2 flex flex-col gap-0.5">
                        <div class="flex justify-between gap-3">
                            <span>{{ t('orders.receipt.subtotal') }}</span>
                            <span class="tabular-nums">{{ money(receipt.subtotal) }}</span>
                        </div>
                        <div v-if="hasDiscount" class="flex justify-between gap-3">
                            <span>{{ t('orders.receipt.discount') }} −{{ Number(receipt.discount_percent) }}%</span>
                            <span class="tabular-nums">−{{ money(receipt.discount_amount) }}</span>
                        </div>
                    </div>

                    <!-- Последняя строка — итоговая сумма -->
                    <div class="mt-2 flex items-baseline justify-between gap-3 border-t-2 border-double border-black pt-2 text-sm font-bold">
                        <span>{{ t('orders.receipt.total') }}:</span>
                        <span class="tabular-nums">{{ money(receipt.total) }} {{ receipt.currency }}</span>
                    </div>

                    <p class="mt-4 border-t border-dashed border-black/40 pt-3 text-center text-[10px]">
                        {{ t('orders.receipt.thanks') }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 justify-end gap-2 border-t border-gray-100 px-6 py-4 print:hidden dark:border-slate-700">
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
                    @click="emit('close')"
                >
                    {{ t('layout.actions.close') }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                    @click="printReceipt"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    {{ t('orders.actions.print_receipt') }}
                </button>
            </div>
        </div>
    </Modal>
</template>
