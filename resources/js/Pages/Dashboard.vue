<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import OrderStatusBadge from '@/Pages/Orders/Partials/OrderStatusBadge.vue'

const { t } = useI18n()

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            active_masters: 0,
            pending_orders: 0,
            total_cities: 0,
            completed_orders: 0,
            in_progress_orders: 0,
        }),
    },
    ordersByStatus: { type: Array, default: () => [] },
    recentOrders: { type: Array, default: () => [] },
})

const cards = [
    {
        key: 'total_orders',
        labelKey: 'dashboard.stats.total_orders',
        iconPath: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
        iconBg: 'bg-indigo-100 dark:bg-indigo-900/40',
        iconColor: 'text-indigo-600 dark:text-indigo-400',
    },
    {
        key: 'active_masters',
        labelKey: 'dashboard.stats.active_masters',
        iconPath: 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/40',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        key: 'pending_orders',
        labelKey: 'dashboard.stats.pending_orders',
        iconPath: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        iconBg: 'bg-amber-100 dark:bg-amber-900/40',
        iconColor: 'text-amber-600 dark:text-amber-400',
    },
    {
        key: 'total_cities',
        labelKey: 'dashboard.stats.total_cities',
        iconPath: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
        iconBg: 'bg-sky-100 dark:bg-sky-900/40',
        iconColor: 'text-sky-600 dark:text-sky-400',
    },
    {
        key: 'completed_orders',
        labelKey: 'dashboard.stats.completed_orders',
        iconPath: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        iconBg: 'bg-green-100 dark:bg-green-900/40',
        iconColor: 'text-green-600 dark:text-green-400',
    },
    {
        key: 'in_progress_orders',
        labelKey: 'dashboard.stats.in_progress_orders',
        iconPath: 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
        iconBg: 'bg-violet-100 dark:bg-violet-900/40',
        iconColor: 'text-violet-600 dark:text-violet-400',
    },
]

const statusBarColors = {
    yellow: 'bg-yellow-400',
    blue: 'bg-blue-500',
    indigo: 'bg-indigo-500',
    green: 'bg-green-500',
    red: 'bg-red-400',
}

const statusLabelColors = {
    yellow: 'text-yellow-600 dark:text-yellow-400',
    blue: 'text-blue-600 dark:text-blue-400',
    indigo: 'text-indigo-600 dark:text-indigo-400',
    green: 'text-green-600 dark:text-green-400',
    red: 'text-red-500 dark:text-red-400',
}

const totalOrders = computed(() => props.stats.total_orders || 1)
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AdminLayout :title="t('dashboard.title')">
        <div class="space-y-6">
            <!-- Stat cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="card in cards"
                    :key="card.key"
                    class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm dark:bg-slate-800"
                >
                    <div
                        :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-xl', card.iconBg]"
                    >
                        <svg
                            :class="['h-6 w-6', card.iconColor]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" :d="card.iconPath" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ stats[card.key] ?? 0 }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ t(card.labelKey) }}</p>
                    </div>
                </div>
            </div>

            <!-- Bottom row: status breakdown + recent orders -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Orders by status -->
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-slate-800">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700 dark:text-slate-200">
                        {{ t('dashboard.orders_by_status') }}
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in ordersByStatus"
                            :key="item.status"
                            class="space-y-1"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span :class="['font-medium', statusLabelColors[item.color] ?? 'text-gray-600 dark:text-slate-300']">
                                    {{ t('orders.statuses.' + item.status) }}
                                </span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ item.count }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                                <div
                                    :class="['h-full rounded-full transition-all', statusBarColors[item.color] ?? 'bg-gray-400']"
                                    :style="{ width: totalOrders > 0 ? (item.count / totalOrders * 100) + '%' : '0%' }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent orders -->
                <div class="rounded-xl bg-white shadow-sm dark:bg-slate-800 lg:col-span-2">
                    <div class="flex items-center justify-between px-5 pt-5 pb-3">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200">
                            {{ t('dashboard.recent_orders') }}
                        </h2>
                        <Link
                            :href="route('orders.index')"
                            class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            {{ t('dashboard.view_all') }}
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-slate-700">
                                    <th class="px-5 pb-2 text-left text-xs font-medium text-gray-400 dark:text-slate-500">{{ t('dashboard.table.id') }}</th>
                                    <th class="px-3 pb-2 text-left text-xs font-medium text-gray-400 dark:text-slate-500">{{ t('dashboard.table.client') }}</th>
                                    <th class="px-3 pb-2 text-left text-xs font-medium text-gray-400 dark:text-slate-500">{{ t('dashboard.table.category') }}</th>
                                    <th class="px-3 pb-2 text-left text-xs font-medium text-gray-400 dark:text-slate-500">{{ t('dashboard.table.city') }}</th>
                                    <th class="px-3 pb-2 text-left text-xs font-medium text-gray-400 dark:text-slate-500">{{ t('dashboard.table.status') }}</th>
                                    <th class="px-5 pb-2 text-left text-xs font-medium text-gray-400 dark:text-slate-500">{{ t('dashboard.table.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/60">
                                <tr
                                    v-for="order in recentOrders"
                                    :key="order.id"
                                    class="hover:bg-gray-50 dark:hover:bg-slate-700/30"
                                >
                                    <td class="px-5 py-2.5 font-medium text-gray-900 dark:text-white">
                                        <Link
                                            :href="route('orders.show', order.id)"
                                            class="hover:text-indigo-600 dark:hover:text-indigo-400"
                                        >
                                            #{{ order.id }}
                                        </Link>
                                    </td>
                                    <td class="px-3 py-2.5 text-gray-700 dark:text-slate-300">{{ order.client_name }}</td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-slate-400">{{ order.category }}</td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-slate-400">{{ order.city }}</td>
                                    <td class="px-3 py-2.5">
                                        <OrderStatusBadge
                                            :status="order.status"
                                            :color="order.color"
                                            :label="t('orders.statuses.' + order.status)"
                                        />
                                    </td>
                                    <td class="px-5 py-2.5 text-gray-500 dark:text-slate-500">{{ order.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
