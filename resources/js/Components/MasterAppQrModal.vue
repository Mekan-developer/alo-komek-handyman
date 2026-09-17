<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import QRCode from 'qrcode'
import Modal from '@/Components/Modal.vue'
import { useNotificationStore } from '@/stores/useNotificationStore'

const props = defineProps({
    show: { type: Boolean, required: true },
    url: { type: String, default: null },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

const qrDataUrl = ref(null)
const generating = ref(false)

const hasUrl = computed(() => Boolean(props.url))

watch(
    () => [props.show, props.url],
    async ([show, url]) => {
        if (!show || !url) {
            qrDataUrl.value = null
            return
        }

        generating.value = true
        try {
            qrDataUrl.value = await QRCode.toDataURL(url, {
                width: 280,
                margin: 2,
                color: { dark: '#0f172a', light: '#ffffff' },
            })
        } catch {
            qrDataUrl.value = null
            notificationStore.error(t('layout.master_app_qr.generate_failed'))
        } finally {
            generating.value = false
        }
    },
    { immediate: true },
)

async function copyUrl() {
    if (!props.url) {
        return
    }

    try {
        await navigator.clipboard.writeText(props.url)
        notificationStore.success(t('layout.master_app_qr.copied'))
    } catch {
        notificationStore.error(t('layout.master_app_qr.copy_failed'))
    }
}
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('close')">
        <div class="flex flex-col">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ t('layout.master_app_qr.title') }}
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">
                        {{ t('layout.master_app_qr.hint') }}
                    </p>
                </div>
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

            <div class="flex flex-col items-center gap-4 px-6 py-6">
                <div
                    class="flex h-[280px] w-[280px] items-center justify-center rounded-2xl border border-gray-200 bg-white dark:border-slate-600"
                >
                    <img
                        v-if="qrDataUrl"
                        :src="qrDataUrl"
                        :alt="t('layout.master_app_qr.title')"
                        class="h-full w-full rounded-2xl"
                    >
                    <span v-else-if="generating" class="text-sm text-gray-400 dark:text-slate-500">
                        {{ t('layout.master_app_qr.generating') }}
                    </span>
                    <span v-else class="px-4 text-center text-sm text-gray-400 dark:text-slate-500">
                        {{ t('layout.master_app_qr.unavailable') }}
                    </span>
                </div>

                <p
                    v-if="hasUrl"
                    class="w-full break-all rounded-lg bg-gray-50 px-3 py-2 text-center font-mono text-[11px] text-gray-600 dark:bg-slate-900 dark:text-slate-300"
                >
                    {{ url }}
                </p>
            </div>

            <div class="flex flex-col gap-2 border-t border-gray-100 px-6 py-4 dark:border-slate-700 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
                    @click="emit('close')"
                >
                    {{ t('layout.actions.close') }}
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    :disabled="!hasUrl"
                    @click="copyUrl"
                >
                    {{ t('layout.master_app_qr.copy') }}
                </button>
                <a
                    v-if="hasUrl"
                    :href="url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                >
                    {{ t('layout.master_app_qr.download') }}
                </a>
            </div>
        </div>
    </Modal>
</template>
