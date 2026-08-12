<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/stores/useNotificationStore'

const { t } = useI18n()
const notifications = useNotificationStore()

const props = defineProps({
    initialCodes: { type: Array, default: () => [] },
    showEmptyState: { type: Boolean, default: false },
})

const codes = ref([...props.initialCodes])
const copiedId = ref(null)

const hasCodes = computed(() => codes.value.length > 0)

async function fetchCodes() {
    try {
        const { data } = await window.axios.get(route('pending-otps.data'))
        codes.value = data.data ?? []
    } catch {
        codes.value = []
    }
}

async function dismiss(id) {
    try {
        await window.axios.delete(route('pending-otps.destroy', id))
        codes.value = codes.value.filter((item) => item.id !== id)
    } catch {
        notifications.error(t('pending_otps.dismiss_failed'))
    }
}

async function copy(item) {
    try {
        await navigator.clipboard.writeText(item.code)
        copiedId.value = item.id
        notifications.success(t('pending_otps.copied'))
        setTimeout(() => { copiedId.value = null }, 2000)
    } catch {
        // Clipboard is unavailable over plain HTTP — the code stays readable on screen.
    }
}

function countdown(seconds) {
    const total = Math.floor(Number(seconds) || 0)
    if (total <= 0) { return t('pending_otps.expired') }
    const minutes = Math.floor(total / 60)
    return `${minutes}:${String(total % 60).padStart(2, '0')}`
}

function tick() {
    codes.value = codes.value
        .map((item) => ({ ...item, expires_in_seconds: item.expires_in_seconds - 1 }))
        .filter((item) => item.expires_in_seconds > -30)
}

/** Reverb pushes parked codes instantly; the poll is the fallback when it is down. */
function handleBroadcast(payload) {
    if (codes.value.some((item) => item.id === payload.id)) { return }
    codes.value = [payload, ...codes.value.filter((item) => item.phone !== payload.phone)]
}

let pollInterval = null
let tickInterval = null

onMounted(() => {
    fetchCodes()
    pollInterval = setInterval(fetchCodes, 30_000)
    tickInterval = setInterval(tick, 1000)

    window.Echo?.private('admin.pending-otps').listen('.pending-otp.created', handleBroadcast)
})

onBeforeUnmount(() => {
    clearInterval(pollInterval)
    clearInterval(tickInterval)
    // Unbind only this listener — AdminLayout keeps the same channel for its badge.
    window.Echo?.private('admin.pending-otps').stopListening('.pending-otp.created', handleBroadcast)
})
</script>

<template>
    <div
        v-if="hasCodes"
        class="rounded-xl border border-amber-300 bg-amber-50 p-5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10"
    >
        <div class="mb-3 flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-500/20">
                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                    {{ t('pending_otps.panel_title') }}
                </h2>
                <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300/80">
                    {{ t('pending_otps.hint') }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-amber-200 dark:border-amber-500/20">
                        <th class="pb-2 pr-3 text-left text-xs font-medium text-amber-700 dark:text-amber-300/70">{{ t('pending_otps.phone') }}</th>
                        <th class="pb-2 pr-3 text-left text-xs font-medium text-amber-700 dark:text-amber-300/70">{{ t('pending_otps.recipient') }}</th>
                        <th class="pb-2 pr-3 text-left text-xs font-medium text-amber-700 dark:text-amber-300/70">{{ t('pending_otps.code') }}</th>
                        <th class="pb-2 pr-3 text-left text-xs font-medium text-amber-700 dark:text-amber-300/70">{{ t('pending_otps.expires_in') }}</th>
                        <th class="pb-2 text-right text-xs font-medium text-amber-700 dark:text-amber-300/70"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-amber-200/60 dark:divide-amber-500/10">
                    <tr v-for="item in codes" :key="item.id">
                        <td class="py-2.5 pr-3 font-medium text-amber-900 dark:text-amber-100">{{ item.phone }}</td>
                        <td class="py-2.5 pr-3 text-amber-800 dark:text-amber-200/90">
                            {{ t('pending_otps.recipients.' + item.recipient_type) }}
                            <span v-if="item.recipient_name" class="text-amber-600 dark:text-amber-300/70">· {{ item.recipient_name }}</span>
                        </td>
                        <td class="py-2.5 pr-3">
                            <button
                                type="button"
                                @click="copy(item)"
                                class="rounded-lg bg-amber-600 px-3 py-1 font-mono text-base font-bold tracking-[0.2em] text-white transition-colors hover:bg-amber-700"
                                :title="t('pending_otps.copy')"
                            >
                                {{ item.code }}
                            </button>
                            <span v-if="copiedId === item.id" class="ml-2 text-xs text-amber-700 dark:text-amber-300">
                                {{ t('pending_otps.copied') }}
                            </span>
                        </td>
                        <td
                            class="py-2.5 pr-3 tabular-nums"
                            :class="item.expires_in_seconds <= 0 ? 'text-red-600 dark:text-red-400' : 'text-amber-800 dark:text-amber-200/90'"
                        >
                            {{ countdown(item.expires_in_seconds) }}
                        </td>
                        <td class="py-2.5 text-right">
                            <button
                                type="button"
                                @click="dismiss(item.id)"
                                class="rounded-md border border-amber-300 px-2.5 py-1 text-xs font-medium text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-500/30 dark:text-amber-200 dark:hover:bg-amber-500/20"
                            >
                                {{ t('pending_otps.dismiss') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div
        v-else-if="showEmptyState"
        class="rounded-xl bg-white p-10 text-center shadow-sm dark:bg-slate-800"
    >
        <svg class="mx-auto h-10 w-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="mt-3 text-sm text-gray-500 dark:text-slate-400">{{ t('pending_otps.empty') }}</p>
    </div>
</template>
