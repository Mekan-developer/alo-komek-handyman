<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
    mastersWithBalance: { type: Object, default: () => ({ data: [] }) },
    payouts: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    paymentModels: { type: Array, default: () => [] },
    canPayout: { type: Boolean, default: false },
})

const masters = computed(() => props.mastersWithBalance?.data ?? [])
const payoutList = computed(() => props.payouts?.data ?? [])
const currentPage = computed(() => props.payouts?.meta?.current_page ?? 1)
const lastPage = computed(() => props.payouts?.meta?.last_page ?? 1)

const modelLabel = (value) => props.paymentModels.find((m) => m.value === value)?.label ?? value

const money = (value) =>
    Number(value ?? 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const statCards = computed(() => [
    {
        label: t('payments.stats.total_paid'),
        value: `${money(props.stats.total_paid)} ${t('payments.currency')}`,
        icon: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75',
        bg: 'bg-emerald-50 dark:bg-emerald-500/10',
        color: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        label: t('payments.stats.pending_payouts'),
        value: `${money(props.stats.pending_payouts)} ${t('payments.currency')}`,
        icon: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        bg: 'bg-amber-50 dark:bg-amber-500/10',
        color: 'text-amber-600 dark:text-amber-400',
    },
    {
        label: t('payments.stats.masters_with_balance'),
        value: props.stats.masters_with_balance ?? 0,
        icon: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
        bg: 'bg-indigo-50 dark:bg-indigo-500/10',
        color: 'text-indigo-600 dark:text-indigo-400',
    },
])

const payoutTarget = ref(null)
const form = useForm({ amount: null, note: '' })

function openPayout(master) {
    payoutTarget.value = master
    form.reset()
    form.clearErrors()
    // Default to a full payout; the operator can lower it for a partial one.
    form.amount = Number(master.balance)
}

function setFullAmount() {
    if (payoutTarget.value) {
        form.amount = Number(payoutTarget.value.balance)
    }
}

function closePayout() {
    payoutTarget.value = null
}

function submitPayout() {
    if (!payoutTarget.value) {
        return
    }

    form.post(route('payments.payout', payoutTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            payoutTarget.value = null
        },
    })
}
</script>

<template>
    <Head :title="t('payments.title')" />

    <AdminLayout :title="t('payments.title')">
        <div class="space-y-6">

            <!-- Page header -->
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('payments.title') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ t('payments.subtitle') }}
                </p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    v-for="stat in statCards"
                    :key="stat.label"
                    class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60"
                >
                    <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', stat.bg]">
                        <svg :class="['h-5 w-5', stat.color]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="stat.icon" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ stat.label }}</p>
                    </div>
                </div>
            </div>

            <!-- Masters awaiting payout -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300">
                        {{ t('payments.pending_title') }}
                    </h2>
                </div>

                <div v-if="masters.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.master') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.city') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.model') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.balance') }}</th>
                                <th v-if="canPayout" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr
                                v-for="master in masters"
                                :key="master.id"
                                class="hover:bg-gray-50 dark:hover:bg-slate-700/40"
                            >
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-200">{{ master.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ master.city?.name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ modelLabel(master.payment_model) }}</td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ money(master.balance) }} {{ t('payments.currency') }}
                                </td>
                                <td v-if="canPayout" class="px-6 py-4 text-right">
                                    <button
                                        type="button"
                                        @click="openPayout(master)"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition-colors"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75" />
                                        </svg>
                                        {{ t('payments.payout_btn') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-500">
                    {{ t('payments.no_pending') }}
                </div>
            </div>

            <!-- Payout history -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-300">
                        {{ t('payments.history_title') }}
                    </h2>
                </div>

                <div v-if="payoutList.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.master') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.amount') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.model') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.paid_by') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">{{ t('payments.fields.note') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            <tr
                                v-for="payout in payoutList"
                                :key="payout.id"
                                class="hover:bg-gray-50 dark:hover:bg-slate-700/40"
                            >
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ payout.created_at }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-200">{{ payout.master_name }}</td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ money(payout.amount) }} {{ t('payments.currency') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ payout.payment_model_label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ payout.paid_by ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ payout.note ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="lastPage > 1" class="flex items-center justify-end gap-1 border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                        <Link
                            v-for="page in lastPage"
                            :key="page"
                            :href="route('payments.index', { page })"
                            preserve-scroll
                            :class="page === currentPage
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700'"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm font-medium transition-colors"
                        >
                            {{ page }}
                        </Link>
                    </div>
                </div>

                <div v-else class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-500">
                    {{ t('payments.no_history') }}
                </div>
            </div>
        </div>

        <!-- Payout modal (centered) -->
        <Modal :show="payoutTarget !== null" max-width="sm" centered @close="closePayout">
            <div class="flex h-full flex-col">
                <div class="flex flex-1 flex-col gap-4 p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ t('payments.modal.title') }}
                            </h3>
                            <p class="mt-1.5 text-sm text-gray-500 dark:text-slate-400">
                                {{ t('payments.modal.message', { name: payoutTarget?.name, amount: `${money(payoutTarget?.balance)} ${t('payments.currency')}` }) }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                                {{ t('payments.modal.amount') }}
                            </label>
                            <button
                                type="button"
                                @click="setFullAmount"
                                class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors"
                            >
                                {{ t('payments.modal.full') }}
                            </button>
                        </div>
                        <div class="relative">
                            <input
                                v-model.number="form.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="Number(payoutTarget?.balance ?? 0)"
                                class="w-full rounded-xl border bg-gray-50 px-4 py-3 pr-16 text-sm font-semibold text-gray-900 focus:bg-white focus:outline-none focus:ring-4 dark:bg-slate-700/50 dark:text-white"
                                :class="form.errors.amount
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                                    : 'border-gray-300 focus:border-emerald-500 focus:ring-emerald-500/20 dark:border-slate-600'"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm text-gray-400 dark:text-slate-500">
                                {{ t('payments.currency') }}
                            </span>
                        </div>
                        <p v-if="form.errors.amount" class="mt-1.5 text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('payments.modal.note') }}
                        </label>
                        <textarea
                            v-model="form.note"
                            rows="2"
                            :placeholder="t('payments.modal.note_placeholder')"
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white"
                        />
                        <p v-if="form.errors.note" class="mt-1.5 text-xs text-red-500">{{ form.errors.note }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-2 border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="closePayout"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-50 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                    >
                        {{ t('layout.confirm.cancel') }}
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="submitPayout"
                        class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? '...' : t('payments.payout_btn') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
