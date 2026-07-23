<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const { t } = useI18n()

const props = defineProps({
    masterAppRules: { type: String, default: '' },
    clientAppRules: { type: String, default: '' },
})

const form = useForm({
    master_app_rules: props.masterAppRules ?? '',
    client_app_rules: props.clientAppRules ?? '',
})

// ── App cards ──────────────────────────────────────────────────────────────
const MASTER_ID = 'rte-master'
const CLIENT_ID = 'rte-client'

const masterOn      = ref(true)
const clientOn      = ref(true)
const masterEditing = ref(false)
const clientEditing = ref(false)
const masterSaved   = ref(false)
const clientSaved   = ref(false)
const masterLastSaved = ref('14:32')
const clientLastSaved = ref('09:18')
const masterEmpty   = ref(!props.masterAppRules)
const clientEmpty   = ref(!props.clientAppRules)

function execCmd(id, cmd, val) {
    const el = document.getElementById(id)
    if (!el) { return }
    el.focus()
    document.execCommand(cmd, false, val ?? null)
}

function clearEditor(id) {
    const el = document.getElementById(id)
    if (!el) { return }
    el.innerHTML = ''
    if (id === MASTER_ID) { masterEmpty.value = true } else { clientEmpty.value = true }
}

function onInput(which) {
    const el = document.getElementById(which === 'master' ? MASTER_ID : CLIENT_ID)
    const isEmpty = !el || el.innerText.trim() === ''
    if (which === 'master') { masterEmpty.value = isEmpty } else { clientEmpty.value = isEmpty }
}

function toggleEdit(which) {
    if (which === 'master') {
        masterEditing.value = !masterEditing.value
        if (masterEditing.value) { nextTick(() => document.getElementById(MASTER_ID)?.focus()) }
    } else {
        clientEditing.value = !clientEditing.value
        if (clientEditing.value) { nextTick(() => document.getElementById(CLIENT_ID)?.focus()) }
    }
}

function save(which) {
    const el = document.getElementById(which === 'master' ? MASTER_ID : CLIENT_ID)
    if (which === 'master') { form.master_app_rules = el?.innerHTML ?? '' }
    else { form.client_app_rules = el?.innerHTML ?? '' }

    form.put(route('settings.update'), {
        preserveScroll: true,
        onSuccess() {
            const now  = new Date()
            const time = `${now.getHours()}:${String(now.getMinutes()).padStart(2, '0')}`
            if (which === 'master') {
                masterEditing.value = false
                masterSaved.value   = true
                masterLastSaved.value = time
                setTimeout(() => { masterSaved.value = false }, 2500)
            } else {
                clientEditing.value = false
                clientSaved.value   = true
                clientLastSaved.value = time
                setTimeout(() => { clientSaved.value = false }, 2500)
            }
        },
    })
}

// ── Monitoring ─────────────────────────────────────────────────────────────
const queueStatus    = ref('ok')
const reverbStatus   = ref('ok')
const wsStatus       = ref('ok')
const otpStatus      = ref('checking')
const queueCount     = ref(7)
const queueDone      = ref(1284)
const reverbChannels = ref(23)
const reverbPing     = ref(12)
const wsClients      = ref(41)
const wsMsgRate      = ref(18)
const otpClients     = ref(0)
const otpLastSent    = ref(null)
const nowTime        = ref('')

const systemOk = computed(() =>
    reverbStatus.value === 'ok' && wsStatus.value === 'ok' && otpStatus.value === 'ok'
)

function statusSt(s) {
    if (s === 'ok')    { return { bg: 'bg-green-500/10',   border: 'border-green-500/25',  dot: 'bg-green-500',  text: 'text-green-500',  label: t('settings.monitoring.active')   } }
    if (s === 'error') { return { bg: 'bg-red-500/10',     border: 'border-red-500/25',    dot: 'bg-red-500',    text: 'text-red-500',    label: t('settings.monitoring.inactive') } }
    return                     { bg: 'bg-yellow-400/10',   border: 'border-yellow-400/25', dot: 'bg-yellow-400', text: 'text-yellow-400', label: t('layout.services.checking')    }
}

const qSt = computed(() => statusSt(queueStatus.value))
const rSt = computed(() => statusSt(reverbStatus.value))
const wSt = computed(() => statusSt(wsStatus.value))
const oSt = computed(() => statusSt(otpStatus.value))

function tick() {
    const now = new Date()
    nowTime.value        = `${now.getHours()}:${String(now.getMinutes()).padStart(2, '0')}`
    queueCount.value     = Math.max(0, queueCount.value + (Math.random() > 0.6 ? 1 : -1))
    queueDone.value     += Math.floor(Math.random() * 3)
    reverbChannels.value = Math.max(1, reverbChannels.value + (Math.random() > 0.55 ? 1 : -1))
    reverbPing.value     = Math.max(5, Math.floor(reverbPing.value + (Math.random() - 0.5) * 6))
    wsClients.value      = Math.max(0, wsClients.value + (Math.random() > 0.5 ? 1 : -1))
    wsMsgRate.value      = Math.max(0, Math.floor(wsMsgRate.value + (Math.random() - 0.5) * 4))
}

async function fetchStatus() {
    try {
        const { data } = await window.axios.get(route('system.status'))
        queueStatus.value  = data.queue === 'ok' ? 'ok' : 'error'
        reverbStatus.value = data.websocket === 'ok' ? 'ok' : 'error'
        otpStatus.value    = data.otp_gateway?.status === 'ok' ? 'ok' : 'error'
        otpClients.value   = data.otp_gateway?.clients ?? 0
        otpLastSent.value  = data.otp_gateway?.last_sent ?? null
    } catch {
        queueStatus.value  = 'error'
        reverbStatus.value = 'error'
        otpStatus.value    = 'error'
    }
    if (window.Echo) {
        wsStatus.value = window.Echo.connector.pusher.connection.state === 'connected' ? 'ok' : 'error'
    }
}

function reconnect(which) {
    if (which === 'reverb') {
        reverbStatus.value = 'checking'
        setTimeout(fetchStatus, 1800)
    } else if (which === 'otp') {
        otpStatus.value = 'checking'
        setTimeout(fetchStatus, 1800)
    } else {
        wsStatus.value = 'checking'
        setTimeout(() => {
            if (window.Echo) {
                wsStatus.value = window.Echo.connector.pusher.connection.state === 'connected' ? 'ok' : 'error'
            }
        }, 1800)
    }
}

let metricsInterval = null
let statusInterval  = null

onMounted(() => {
    tick()
    fetchStatus()
    statusInterval  = setInterval(fetchStatus, 30_000)
    metricsInterval = setInterval(tick, 2500)

    nextTick(() => {
        const m = document.getElementById(MASTER_ID)
        if (m) { m.innerHTML = props.masterAppRules ?? '' }
        const c = document.getElementById(CLIENT_ID)
        if (c) { c.innerHTML = props.clientAppRules ?? '' }
    })
})

onBeforeUnmount(() => {
    clearInterval(metricsInterval)
    clearInterval(statusInterval)
})
</script>

<template>
    <AdminLayout :title="t('settings.title')">
        <div class="flex flex-col gap-8">

            <!-- ─── Monitoring Section ──────────────────────────────────────────── -->
            <section>
                <div class="mb-4 flex items-center gap-2.5">
                    <div class="h-[18px] w-[3px] shrink-0 rounded-sm" style="background:linear-gradient(to bottom,#22c55e,#16a34a)" />
                    <h2 class="text-[13px] font-semibold uppercase tracking-[0.5px] text-slate-400">
                        {{ t('settings.monitoring.title') }}
                    </h2>
                    <div
                        class="ml-auto flex items-center gap-1.5 rounded-full border px-2.5 py-1"
                        :class="systemOk ? 'border-green-500/20 bg-green-500/10' : 'border-red-500/25 bg-red-500/10'"
                    >
                        <div class="h-1.5 w-1.5 animate-pulse rounded-full" :class="systemOk ? 'bg-green-500' : 'bg-red-500'" />
                        <span class="text-[11.5px] font-medium" :class="systemOk ? 'text-green-500' : 'text-red-500'">
                            {{ systemOk ? t('settings.monitoring.all_ok') : t('settings.monitoring.issues') }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">

                    <!-- Queue -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-[18px] dark:border-white/[0.07] dark:bg-[#131729]">
                        <div class="mb-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[9px] border border-amber-500/20 bg-amber-500/10">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="1.8">
                                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                                        <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[13.5px] font-semibold text-gray-900 dark:text-slate-100">{{ t('settings.monitoring.queue') }}</div>
                                    <div class="mt-px text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.queue_worker') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 rounded-full border px-2.5 py-[3px]" :class="[qSt.bg, qSt.border]">
                                <div class="h-1.5 w-1.5 animate-pulse rounded-full" :class="qSt.dot" />
                                <span class="text-[11px] font-semibold" :class="qSt.text">{{ qSt.label }}</span>
                            </div>
                        </div>
                        <div class="mb-3.5 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.in_queue') }}</div>
                                <div class="text-xl font-bold leading-tight text-gray-900 dark:text-slate-100">{{ queueCount }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.processed') }}</div>
                                <div class="text-xl font-bold leading-tight text-green-500">{{ queueDone.toLocaleString('ru') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-[11px] text-gray-400 dark:text-slate-500">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            {{ t('settings.monitoring.updated') }} {{ nowTime }}
                        </div>
                    </div>

                    <!-- Reverb -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-[18px] dark:border-white/[0.07] dark:bg-[#131729]">
                        <div class="mb-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[9px] border border-violet-500/20 bg-violet-500/10">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.8">
                                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[13.5px] font-semibold text-gray-900 dark:text-slate-100">Reverb</div>
                                    <div class="mt-px text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.broadcasting') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 rounded-full border px-2.5 py-[3px]" :class="[rSt.bg, rSt.border]">
                                <div class="h-1.5 w-1.5 animate-pulse rounded-full" :class="rSt.dot" />
                                <span class="text-[11px] font-semibold" :class="rSt.text">{{ rSt.label }}</span>
                            </div>
                        </div>
                        <div class="mb-3.5 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.channels') }}</div>
                                <div class="text-xl font-bold leading-tight text-gray-900 dark:text-slate-100">{{ reverbChannels }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.ping') }}</div>
                                <div class="text-xl font-bold leading-tight text-violet-400">{{ reverbPing }}ms</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.uptime') }}</div>
                            <button
                                type="button"
                                @click="reconnect('reverb')"
                                class="rounded-md border border-gray-200 bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500 transition-colors hover:bg-gray-200 dark:border-white/[0.08] dark:bg-white/5 dark:text-slate-400 dark:hover:bg-white/10"
                            >
                                {{ t('settings.monitoring.reconnect') }}
                            </button>
                        </div>
                    </div>

                    <!-- WebSocket -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-[18px] dark:border-white/[0.07] dark:bg-[#131729]">
                        <div class="mb-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[9px] border border-sky-500/20 bg-sky-500/10">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="1.8">
                                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                                        <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                                        <line x1="12" y1="20" x2="12.01" y2="20"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[13.5px] font-semibold text-gray-900 dark:text-slate-100">WebSocket</div>
                                    <div class="mt-px text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.ws_server') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 rounded-full border px-2.5 py-[3px]" :class="[wSt.bg, wSt.border]">
                                <div class="h-1.5 w-1.5 animate-pulse rounded-full" :class="wSt.dot" />
                                <span class="text-[11px] font-semibold" :class="wSt.text">{{ wSt.label }}</span>
                            </div>
                        </div>
                        <div class="mb-3.5 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.clients') }}</div>
                                <div class="text-xl font-bold leading-tight text-gray-900 dark:text-slate-100">{{ wsClients }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.msg_rate') }}</div>
                                <div class="text-xl font-bold leading-tight text-sky-400">{{ wsMsgRate }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.active_now') }}</div>
                            <button
                                type="button"
                                @click="reconnect('ws')"
                                class="rounded-md border border-gray-200 bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500 transition-colors hover:bg-gray-200 dark:border-white/[0.08] dark:bg-white/5 dark:text-slate-400 dark:hover:bg-white/10"
                            >
                                {{ t('settings.monitoring.reconnect') }}
                            </button>
                        </div>
                    </div>

                    <!-- OTP Gateway -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-[18px] dark:border-white/[0.07] dark:bg-[#131729]">
                        <div class="mb-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[9px] border border-emerald-500/20 bg-emerald-500/10">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="1.8">
                                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                                        <path d="M9 7h6M9 11h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[13.5px] font-semibold text-gray-900 dark:text-slate-100">{{ t('settings.monitoring.otp_gateway') }}</div>
                                    <div class="mt-px text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.gateway_bridge') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 rounded-full border px-2.5 py-[3px]" :class="[oSt.bg, oSt.border]">
                                <div class="h-1.5 w-1.5 animate-pulse rounded-full" :class="oSt.dot" />
                                <span class="text-[11px] font-semibold" :class="oSt.text">{{ oSt.label }}</span>
                            </div>
                        </div>
                        <div class="mb-3.5 grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.phones_connected') }}</div>
                                <div
                                    class="text-xl font-bold leading-tight"
                                    :class="otpClients > 0 ? 'text-emerald-400' : 'text-gray-900 dark:text-slate-100'"
                                >{{ otpClients }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-[9px] dark:bg-white/[0.04]">
                                <div class="mb-[3px] text-[11px] text-gray-400 dark:text-slate-500">{{ t('settings.monitoring.last_otp') }}</div>
                                <div class="text-xl font-bold leading-tight text-emerald-400">{{ otpLastSent ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-[11px] text-gray-400 dark:text-slate-500">:3000</div>
                            <button
                                type="button"
                                @click="reconnect('otp')"
                                class="rounded-md border border-gray-200 bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500 transition-colors hover:bg-gray-200 dark:border-white/[0.08] dark:bg-white/5 dark:text-slate-400 dark:hover:bg-white/10"
                            >
                                {{ t('settings.monitoring.reconnect') }}
                            </button>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ─── App Settings Section ──────────────────────────────────────── -->
            <section>
                <div class="mb-4 flex items-center gap-2.5">
                    <div class="h-[18px] w-[3px] shrink-0 rounded-sm" style="background:linear-gradient(to bottom,#6366f1,#8b5cf6)" />
                    <h2 class="text-[13px] font-semibold uppercase tracking-[0.5px] text-slate-400">
                        {{ t('settings.section_app') }}
                    </h2>
                </div>

                <div class="flex flex-col gap-4">

                    <!-- ── MASTER CARD ───────────────────────────────────────────── -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/[0.07] dark:bg-[#131729]">

                        <!-- Header -->
                        <div class="flex items-center gap-3.5 px-5 pb-3.5 pt-[18px]">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[11px] border border-indigo-500/20 bg-indigo-500/[0.15]">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="1.8">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[15px] font-semibold text-gray-900 dark:text-slate-100">{{ t('settings.master_app.title') }}</div>
                                <div class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">{{ t('settings.master_app.hint') }}</div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    @click="toggleEdit('master')"
                                    class="flex items-center gap-1.5 rounded-lg border px-3.5 py-[7px] text-[12.5px] font-medium transition-all"
                                    :class="masterEditing
                                        ? 'border-indigo-500/35 bg-indigo-500/15 text-indigo-400'
                                        : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400'"
                                >
                                    <svg v-if="masterEditing" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    {{ masterEditing ? t('settings.done') : t('settings.edit') }}
                                </button>
                                <button
                                    type="button"
                                    @click="masterOn = !masterOn"
                                    class="relative h-[23px] w-10 shrink-0 rounded-full border border-white/[0.06] transition-colors"
                                    :class="masterOn ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-white/[0.12]'"
                                >
                                    <span
                                        class="absolute top-[3px] h-[15px] w-[15px] rounded-full bg-white shadow-sm transition-all"
                                        :class="masterOn ? 'left-[21px]' : 'left-[3px]'"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Toolbar -->
                        <Transition name="toolbar">
                            <div v-if="masterEditing" class="px-5 pb-2.5">
                                <div class="flex flex-wrap items-center gap-0.5 rounded-xl border border-gray-200 bg-gray-50 px-1.5 py-[5px] dark:border-white/[0.07] dark:bg-white/[0.04]">
                                    <button @click="execCmd(MASTER_ID,'bold')"                    type="button" class="tb-btn font-bold">B</button>
                                    <button @click="execCmd(MASTER_ID,'italic')"                  type="button" class="tb-btn italic">I</button>
                                    <button @click="execCmd(MASTER_ID,'underline')"               type="button" class="tb-btn underline">U</button>
                                    <button @click="execCmd(MASTER_ID,'strikethrough')"           type="button" class="tb-btn line-through">S</button>
                                    <div class="mx-1 h-[18px] w-px bg-gray-200 dark:bg-white/10" />
                                    <button @click="execCmd(MASTER_ID,'formatBlock','H1')"        type="button" class="tb-btn !w-8 text-[11px] font-bold">H1</button>
                                    <button @click="execCmd(MASTER_ID,'formatBlock','H2')"        type="button" class="tb-btn !w-8 text-[11px] font-semibold">H2</button>
                                    <div class="mx-1 h-[18px] w-px bg-gray-200 dark:bg-white/10" />
                                    <button @click="execCmd(MASTER_ID,'insertUnorderedList')"     type="button" class="tb-btn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                                            <circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button @click="execCmd(MASTER_ID,'insertOrderedList')"       type="button" class="tb-btn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/>
                                            <path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>
                                        </svg>
                                    </button>
                                    <div class="mx-1 h-[18px] w-px bg-gray-200 dark:bg-white/10" />
                                    <button @click="execCmd(MASTER_ID,'formatBlock','BLOCKQUOTE')" type="button" class="tb-btn text-base font-bold leading-none">"</button>
                                    <div class="flex-1" />
                                    <button @click="clearEditor(MASTER_ID)" type="button" class="tb-btn !text-gray-400 hover:!text-red-500 dark:!text-slate-500 dark:hover:!text-red-400">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                            <path d="M10 11v6"/><path d="M14 11v6"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </Transition>

                        <!-- Editor -->
                        <div class="px-5 pb-4">
                            <div
                                :id="MASTER_ID"
                                :contenteditable="masterEditing ? 'true' : 'false'"
                                @input="onInput('master')"
                                :data-empty="masterEmpty ? 'true' : 'false'"
                                :data-placeholder="t('settings.master_app.placeholder')"
                                class="rte-content rounded-[9px] px-[15px] py-[13px] text-[13px] leading-[1.65] outline-none transition-all"
                                :class="[
                                    masterEditing
                                        ? 'min-h-[140px] cursor-text border border-indigo-500/40'
                                        : 'min-h-[60px] cursor-default border border-gray-200 dark:border-white/[0.07]',
                                    'bg-gray-50 text-gray-700 dark:bg-white/[0.03] dark:text-slate-300',
                                ]"
                            />
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between border-t border-gray-100 bg-indigo-500/[0.04] px-5 py-3 dark:border-white/[0.05]">
                            <span class="text-[11.5px] text-gray-400 dark:text-slate-500">
                                {{ masterSaved
                                    ? t('settings.saved_ok')
                                    : `${t('settings.last_saved')}: сегодня, ${masterLastSaved}` }}
                            </span>
                            <button
                                v-if="masterEditing"
                                type="button"
                                @click="save('master')"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-[18px] py-[7px] text-[12.5px] font-semibold text-white transition-opacity hover:bg-indigo-700 disabled:opacity-60"
                            >
                                {{ t('settings.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- ── CLIENT CARD ────────────────────────────────────────────── -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/[0.07] dark:bg-[#131729]">

                        <!-- Header -->
                        <div class="flex items-center gap-3.5 px-5 pb-3.5 pt-[18px]">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[11px] border border-emerald-500/20 bg-emerald-500/[0.12]">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="1.8">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[15px] font-semibold text-gray-900 dark:text-slate-100">{{ t('settings.client_app.title') }}</div>
                                <div class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">{{ t('settings.client_app.hint') }}</div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    @click="toggleEdit('client')"
                                    class="flex items-center gap-1.5 rounded-lg border px-3.5 py-[7px] text-[12.5px] font-medium transition-all"
                                    :class="clientEditing
                                        ? 'border-emerald-500/35 bg-emerald-500/15 text-emerald-400'
                                        : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400'"
                                >
                                    <svg v-if="clientEditing" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    {{ clientEditing ? t('settings.done') : t('settings.edit') }}
                                </button>
                                <button
                                    type="button"
                                    @click="clientOn = !clientOn"
                                    class="relative h-[23px] w-10 shrink-0 rounded-full border border-white/[0.06] transition-colors"
                                    :class="clientOn ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-white/[0.12]'"
                                >
                                    <span
                                        class="absolute top-[3px] h-[15px] w-[15px] rounded-full bg-white shadow-sm transition-all"
                                        :class="clientOn ? 'left-[21px]' : 'left-[3px]'"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Toolbar -->
                        <Transition name="toolbar">
                            <div v-if="clientEditing" class="px-5 pb-2.5">
                                <div class="flex flex-wrap items-center gap-0.5 rounded-xl border border-gray-200 bg-gray-50 px-1.5 py-[5px] dark:border-white/[0.07] dark:bg-white/[0.04]">
                                    <button @click="execCmd(CLIENT_ID,'bold')"                    type="button" class="tb-btn font-bold">B</button>
                                    <button @click="execCmd(CLIENT_ID,'italic')"                  type="button" class="tb-btn italic">I</button>
                                    <button @click="execCmd(CLIENT_ID,'underline')"               type="button" class="tb-btn underline">U</button>
                                    <button @click="execCmd(CLIENT_ID,'strikethrough')"           type="button" class="tb-btn line-through">S</button>
                                    <div class="mx-1 h-[18px] w-px bg-gray-200 dark:bg-white/10" />
                                    <button @click="execCmd(CLIENT_ID,'formatBlock','H1')"        type="button" class="tb-btn !w-8 text-[11px] font-bold">H1</button>
                                    <button @click="execCmd(CLIENT_ID,'formatBlock','H2')"        type="button" class="tb-btn !w-8 text-[11px] font-semibold">H2</button>
                                    <div class="mx-1 h-[18px] w-px bg-gray-200 dark:bg-white/10" />
                                    <button @click="execCmd(CLIENT_ID,'insertUnorderedList')"     type="button" class="tb-btn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                                            <circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button @click="execCmd(CLIENT_ID,'insertOrderedList')"       type="button" class="tb-btn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/>
                                            <path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>
                                        </svg>
                                    </button>
                                    <div class="mx-1 h-[18px] w-px bg-gray-200 dark:bg-white/10" />
                                    <button @click="execCmd(CLIENT_ID,'formatBlock','BLOCKQUOTE')" type="button" class="tb-btn text-base font-bold leading-none">"</button>
                                    <div class="flex-1" />
                                    <button @click="clearEditor(CLIENT_ID)" type="button" class="tb-btn !text-gray-400 hover:!text-red-500 dark:!text-slate-500 dark:hover:!text-red-400">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                            <path d="M10 11v6"/><path d="M14 11v6"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </Transition>

                        <!-- Editor -->
                        <div class="px-5 pb-4">
                            <div
                                :id="CLIENT_ID"
                                :contenteditable="clientEditing ? 'true' : 'false'"
                                @input="onInput('client')"
                                :data-empty="clientEmpty ? 'true' : 'false'"
                                :data-placeholder="t('settings.client_app.placeholder')"
                                class="rte-content rounded-[9px] px-[15px] py-[13px] text-[13px] leading-[1.65] outline-none transition-all"
                                :class="[
                                    clientEditing
                                        ? 'min-h-[140px] cursor-text border border-emerald-500/40'
                                        : 'min-h-[60px] cursor-default border border-gray-200 dark:border-white/[0.07]',
                                    'bg-gray-50 text-gray-700 dark:bg-white/[0.03] dark:text-slate-300',
                                ]"
                            />
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between border-t border-gray-100 bg-emerald-500/[0.04] px-5 py-3 dark:border-white/[0.05]">
                            <span class="text-[11.5px] text-gray-400 dark:text-slate-500">
                                {{ clientSaved
                                    ? t('settings.saved_ok')
                                    : `${t('settings.last_saved')}: сегодня, ${clientLastSaved}` }}
                            </span>
                            <button
                                v-if="clientEditing"
                                type="button"
                                @click="save('client')"
                                :disabled="form.processing"
                                class="rounded-lg bg-emerald-500 px-[18px] py-[7px] text-[12.5px] font-semibold text-white transition-opacity hover:bg-emerald-600 disabled:opacity-60"
                            >
                                {{ t('settings.save') }}
                            </button>
                        </div>
                    </div>

                </div>
            </section>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* Toolbar button base */
.tb-btn {
    width: 30px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: background-color 0.12s, color 0.12s;
}
.tb-btn:hover {
    background: rgba(0, 0, 0, 0.07);
    color: #111827;
}
:global(.dark) .tb-btn {
    color: #94a3b8;
}
:global(.dark) .tb-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

/* Contenteditable placeholder */
:deep(.rte-content[data-empty="true"]::before) {
    content: attr(data-placeholder);
    color: #94a3b8;
    pointer-events: none;
    float: left;
    height: 0;
}

/* Rich text content styling */
:deep(.rte-content h1)         { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
:deep(.rte-content h2)         { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
:deep(.rte-content ul)         { padding-left: 18px; list-style-type: disc; }
:deep(.rte-content ol)         { padding-left: 18px; list-style-type: decimal; }
:deep(.rte-content li)         { margin-bottom: 3px; font-size: 13px; }
:deep(.rte-content p)          { margin-bottom: 4px; }
:deep(.rte-content blockquote) { border-left: 3px solid #6366f1; padding-left: 12px; margin: 4px 0; font-style: italic; opacity: 0.85; }
:deep(.rte-content strong)     { font-weight: 700; }
:deep(.rte-content em)         { font-style: italic; }
:deep(.rte-content u)          { text-decoration: underline; }
:deep(.rte-content s)          { text-decoration: line-through; }

/* Toolbar slide-in animation */
.toolbar-enter-active,
.toolbar-leave-active {
    overflow: hidden;
    transition: opacity 0.15s ease, max-height 0.15s ease;
}
.toolbar-enter-from,
.toolbar-leave-to {
    opacity: 0;
    max-height: 0;
}
.toolbar-enter-to,
.toolbar-leave-from {
    opacity: 1;
    max-height: 60px;
}
</style>
