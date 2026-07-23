<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    unreadCount: {
        type: Number,
        default: 0,
    },
})

const emit = defineEmits(['close', 'read'])

const notifications = ref([])
const loading = ref(false)

watch(() => props.open, async (isOpen) => {
    if (isOpen) {
        await fetchNotifications()
    }
})

async function fetchNotifications() {
    loading.value = true
    try {
        const { data } = await axios.get(route('notifications.index'))
        notifications.value = data
    } finally {
        loading.value = false
    }
}

async function markRead(notification) {
    if (notification.read_at) { return }

    await axios.post(route('notifications.read', notification.id))
    notification.read_at = new Date().toISOString()

    emit('read')
    router.reload({ only: ['unreadNotificationsCount'] })
}

async function markAllRead() {
    await axios.post(route('notifications.read-all'))
    notifications.value.forEach(n => {
        n.read_at = n.read_at ?? new Date().toISOString()
    })

    emit('read')
    router.reload({ only: ['unreadNotificationsCount'] })
}

async function deleteNotification(notification) {
    await axios.delete(route('notifications.destroy', notification.id))
    notifications.value = notifications.value.filter(n => n.id !== notification.id)

    if (!notification.read_at) {
        emit('read')
        router.reload({ only: ['unreadNotificationsCount'] })
    }
}

async function deleteAll() {
    await axios.delete(route('notifications.destroy-all'))
    notifications.value = []

    emit('read')
    router.reload({ only: ['unreadNotificationsCount'] })
}

function formatTime(iso) {
    if (!iso) { return '' }
    const d = new Date(iso)
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function goToOrder(orderId) {
    emit('close')
    router.visit(route('orders.show', orderId))
}

function prepend(notification) {
    notifications.value.unshift(notification)
}

defineExpose({ prepend, fetchNotifications })
</script>

<template>
    <!-- Backdrop -->
    <Transition name="backdrop">
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-md"
            @click="$emit('close')"
        />
    </Transition>

    <!-- Panel -->
    <Transition name="panel">
        <div
            v-if="open"
            class="fixed inset-y-0 right-0 z-[99999] flex w-full max-w-sm flex-col bg-white shadow-2xl dark:bg-slate-800"
        >
            <!-- Header -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Уведомления</h2>
                    <span
                        v-if="unreadCount > 0"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-bold text-white"
                    >
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="rounded-md px-2.5 py-1.5 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30"
                    >
                        Прочитать все
                    </button>
                    <button
                        v-if="notifications.length > 0"
                        @click="deleteAll"
                        class="rounded-md px-2.5 py-1.5 text-xs font-medium text-red-500 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                    >
                        Удалить все
                    </button>
                    <button
                        @click="$emit('close')"
                        class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-white"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- List -->
            <div class="flex-1 overflow-y-auto">
                <!-- Loading -->
                <div v-if="loading" class="flex flex-col gap-3 p-4">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="h-16 animate-pulse rounded-lg bg-gray-100 dark:bg-slate-700"
                    />
                </div>

                <!-- Empty -->
                <div
                    v-else-if="notifications.length === 0"
                    class="flex flex-col items-center justify-center gap-3 py-20 text-center"
                >
                    <svg class="h-12 w-12 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <p class="text-sm text-gray-400 dark:text-slate-500">Уведомлений нет</p>
                </div>

                <!-- Notification items -->
                <TransitionGroup v-else name="notif" tag="div" class="divide-y divide-gray-100 dark:divide-slate-700">
                    <div
                        v-for="n in notifications"
                        :key="n.id"
                        class="group relative flex items-start gap-3 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/50"
                        :class="!n.read_at ? 'bg-indigo-50/60 dark:bg-indigo-900/10' : ''"
                    >
                        <!-- Unread dot -->
                        <div class="mt-1.5 shrink-0">
                            <div
                                class="h-2 w-2 rounded-full transition-colors"
                                :class="!n.read_at ? 'bg-indigo-500' : 'bg-transparent'"
                            />
                        </div>

                        <!-- Content — кликабельный -->
                        <button
                            class="min-w-0 flex-1 text-left"
                            @click="markRead(n); goToOrder(n.data.order_id)"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Новый заказ #{{ n.data.order_id }}
                                </p>
                                <time class="shrink-0 text-xs text-gray-400 dark:text-slate-500">
                                    {{ formatTime(n.created_at) }}
                                </time>
                            </div>
                            <p class="mt-0.5 text-sm text-gray-600 dark:text-slate-400">
                                {{ n.data.client_name }} · {{ n.data.category }}
                            </p>
                            <p v-if="n.data.city" class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">
                                {{ n.data.city }}<span v-if="n.data.address"> · {{ n.data.address }}</span>
                            </p>
                        </button>

                        <!-- Delete button -->
                        <button
                            @click.stop="deleteNotification(n)"
                            class="shrink-0 rounded-md p-1 text-gray-300 opacity-0 transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-slate-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                            title="Удалить"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.backdrop-enter-active,
.backdrop-leave-active {
    transition: opacity 0.25s ease;
}
.backdrop-enter-from,
.backdrop-leave-to {
    opacity: 0;
}

.panel-enter-active,
.panel-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.panel-enter-from,
.panel-leave-to {
    transform: translateX(100%);
}

.notif-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
    position: absolute;
    width: 100%;
}
.notif-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
.notif-move {
    transition: transform 0.2s ease;
}
</style>
