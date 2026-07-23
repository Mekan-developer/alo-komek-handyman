<script setup>
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useThemeStore } from '@/stores/useThemeStore'
import { useLocaleStore } from '@/stores/useLocaleStore'
import { useNotificationStore } from '@/stores/useNotificationStore'
import NotificationPanel from '@/Components/NotificationPanel.vue'

defineProps({
    title: {
        type: String,
        default: '',
    },
})

const { t, locale } = useI18n()
const themeStore = useThemeStore()
const localeStore = useLocaleStore()
const notificationStore = useNotificationStore()
const page = usePage()

const sidebarOpen = ref(false)
const userMenuOpen = ref(false)
const notificationPanelOpen = ref(false)
const notificationPanelRef = ref(null)

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0)

watch(() => localeStore.locale, (lang) => {
    locale.value = lang
}, { immediate: true })

watch(() => page.props.notification, (notification) => {
    if (notification?.message) {
        notificationStore.add(notification)
    }
}, { immediate: true })

const currentUserRole = computed(() => page.props.auth.user?.role ?? null)

const allNavItems = [
    {
        labelKey: 'layout.nav.dashboard',
        routeName: 'dashboard',
        roles: ['administrator', 'manager'],
        iconPath: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
    },
    {
        labelKey: 'layout.nav.oblasts',
        routeName: 'oblasts.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z',
    },
    {
        labelKey: 'layout.nav.categories',
        routeName: 'categories.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z',
    },
    {
        labelKey: 'layout.nav.masters',
        routeName: 'masters.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z',
    },
    {
        labelKey: 'layout.nav.clients',
        routeName: 'clients.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    },
    {
        labelKey: 'layout.nav.orders',
        routeName: 'orders.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
    },
    {
        labelKey: 'layout.nav.payments',
        routeName: 'payments.index',
        roles: ['administrator'],
        iconPath: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
    },
    {
        labelKey: 'layout.nav.banners',
        routeName: 'banners.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
    },
    {
        labelKey: 'layout.nav.users',
        routeName: 'users.index',
        roles: ['administrator'],
        iconPath: 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    },
    {
        labelKey: 'layout.nav.settings',
        routeName: 'settings.index',
        roles: ['administrator', 'manager'],
        iconPath: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
]

const navItems = computed(() =>
    allNavItems.filter(item => !item.roles || item.roles.includes(currentUserRole.value)),
)

function safeRoute(name) {
    try {
        return route(name)
    } catch {
        return '#'
    }
}

function isCurrentRoute(name) {
    try {
        const prefix = name.split('.')[0]
        return route().current(name) || route().current(`${prefix}.*`)
    } catch {
        return false
    }
}

function logout() {
    router.post(route('logout'))
}

const queueStatus = ref('checking')
const reverbStatus = ref('checking')
const wsStatus = ref('checking')
let statusPollInterval = null

// Echo client connection state — purely client-side, no HTTP needed
function updateWsStatus() {
    if (!window.Echo) {
        wsStatus.value = 'error'
        return
    }
    const state = window.Echo.connector.pusher.connection.state
    wsStatus.value = state === 'connected' ? 'ok' : (state === 'connecting' ? 'checking' : 'error')
}

// Queue + Reverb server reachability — one HTTP request covers both
async function fetchSystemStatus() {
    try {
        const { data } = await window.axios.get(route('system.status'))
        queueStatus.value = data.queue
        reverbStatus.value = data.websocket
    } catch {
        queueStatus.value = 'error'
        reverbStatus.value = 'error'
    }
}

const alarmAudio = new Audio('/sounds/alarm.mp3')
alarmAudio.volume = 0.5

function playAlarmSound() {
    alarmAudio.currentTime = 0
    alarmAudio.play().catch(() => {})
}

function handleNewOrder(payload) {
    const message = t('orders.notifications.new_order', {
        client: payload.client_name ?? '—',
        category: payload.category ?? '—',
    })
    notificationStore.info(message)
    playAlarmSound()

    router.reload({ only: ['unreadNotificationsCount'] })
    if (notificationPanelOpen.value) {
        notificationPanelRef.value?.fetchNotifications()
    }
}

function handleMasterAssigned(payload) {
    notificationStore.success(t('orders.notifications.master_assigned_broadcast', {
        order: `#${payload.order_id}`,
        master: payload.master_name ?? '—',
    }))
    playAlarmSound()
}

function handleOrderStatusChanged(payload) {
    notificationStore.info(t('orders.notifications.status_changed_broadcast', {
        order: `#${payload.order_id}`,
        status: payload.to_label ?? payload.to,
    }))
    playAlarmSound()
}

onMounted(() => {
    fetchSystemStatus()
    statusPollInterval = setInterval(fetchSystemStatus, 30_000)

    updateWsStatus()

    if (!window.Echo) { return }
    window.Echo.connector.pusher.connection.bind('state_change', updateWsStatus)
    window.Echo.channel('orders')
        .listen('.order.created', handleNewOrder)
        .listen('.master.assigned', handleMasterAssigned)
        .listen('.order.status.changed', handleOrderStatusChanged)
})

onBeforeUnmount(() => {
    clearInterval(statusPollInterval)
    window.Echo?.connector.pusher.connection.unbind('state_change', updateWsStatus)
    window.Echo?.leave('orders')
})
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-slate-900">

        <!-- Mobile overlay -->
        <Transition name="overlay">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-black/60 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-slate-800 transition-transform duration-300 ease-in-out lg:static lg:z-auto lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo -->
            <div class="flex h-16 shrink-0 items-center border-b border-slate-700 px-6">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-gray-200">
                        <image src="/icons/logo/alo_help.png" alt="Logo" class="h-auto w-16" />
                    </div>
                    <span class="text-lg font-bold text-white">Alo-kömek</span>
                </div>
            </div>

            <!-- Navigation links -->
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <Link
                    v-for="item in navItems"
                    :key="item.routeName"
                    :href="safeRoute(item.routeName)"
                    :class="[
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150',
                        isCurrentRoute(item.routeName)
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-slate-700 hover:text-white',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
                    </svg>
                    {{ t(item.labelKey) }}
                </Link>
            </nav>

            <!-- Bottom user area -->
            <div class="shrink-0 border-t border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">
                        {{ $page.props.auth.user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $page.props.auth.user?.name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $page.props.auth.user?.email }}</p>
                        <p class="truncate text-xs capitalize text-slate-500">{{ $page.props.auth.user?.role }}</p>
                    </div>
                    <button
                        @click="logout"
                        class="shrink-0 rounded-md p-1.5 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                        :title="t('layout.header.logout')"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

            <!-- Topbar -->
            <header class="flex h-16 shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4 dark:border-slate-700 dark:bg-slate-800 lg:px-6">

                <!-- Hamburger (mobile) -->
                <button
                    class="rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white lg:hidden"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Page title -->
                <h1 class="flex-1 text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h1>

                <!-- Controls -->
                <div class="flex items-center gap-2">

                    <!-- Bell notifications -->
                    <button
                        @click="notificationPanelOpen = true"
                        class="relative rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white"
                        title="Уведомления"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span
                            v-if="unreadCount > 0"
                            class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white"
                        >
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </span>
                    </button>

                    <!-- Dark mode toggle -->
                    <button
                        @click="themeStore.toggle"
                        class="rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white"
                        :title="t('layout.header.theme_toggle')"
                    >
                        <svg v-if="themeStore.isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>

                    <!-- Language switcher -->
                    <div class="flex items-center gap-0.5 rounded-md border border-gray-200 p-0.5 dark:border-slate-700">
                        <button
                            v-for="lang in ['ru', 'tk']"
                            :key="lang"
                            @click="localeStore.setLocale(lang)"
                            :class="[
                                'rounded px-2.5 py-1 text-xs font-semibold uppercase transition-colors',
                                localeStore.locale === lang
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white',
                            ]"
                        >
                            {{ lang }}
                        </button>
                    </div>

                    <!-- User dropdown -->
                    <div class="relative">
                        <button
                            @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                {{ $page.props.auth.user?.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <span class="hidden sm:block max-w-32 truncate">{{ $page.props.auth.user?.name }}</span>
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="userMenuOpen"
                                class="absolute right-0 top-full z-50 mt-1 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800"
                            >
                                <Link
                                    :href="route('profile.edit')"
                                    @click="userMenuOpen = false"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-700"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                    {{ t('layout.header.profile') }}
                                </Link>
                                <button
                                    @click="logout"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                    </svg>
                                    {{ t('layout.header.logout') }}
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </main>
        </div>

        <!-- Click-outside backdrop for user menu -->
        <div
            v-if="userMenuOpen"
            class="fixed inset-0 z-40"
            @click="userMenuOpen = false"
        />

        <!-- Notification slide panel -->
        <NotificationPanel
            ref="notificationPanelRef"
            :open="notificationPanelOpen"
            :unread-count="unreadCount"
            @close="notificationPanelOpen = false"
            @read="router.reload({ only: ['unreadNotificationsCount'] })"
        />

        <!-- Toast notifications -->
        <div class="pointer-events-none fixed right-4 top-4 z-50 flex flex-col gap-2">
            <TransitionGroup name="toast">
                <div
                    v-for="notification in notificationStore.notifications"
                    :key="notification.id"
                    class="pointer-events-auto flex min-w-80 max-w-sm items-start gap-3 rounded-lg px-4 py-3 text-white shadow-lg"
                    :class="{
                        'bg-green-500': notification.type === 'success',
                        'bg-red-500': notification.type === 'error',
                        'bg-yellow-500 text-gray-900': notification.type === 'warning',
                        'bg-blue-500': notification.type === 'info',
                    }"
                >
                    <p class="flex-1 text-sm font-medium">{{ notification.message }}</p>
                    <button
                        @click="notificationStore.remove(notification.id)"
                        class="shrink-0 rounded opacity-75 transition-opacity hover:opacity-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
    transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
    opacity: 0;
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.97);
}

.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
.toast-move {
    transition: transform 0.3s ease;
}
</style>
