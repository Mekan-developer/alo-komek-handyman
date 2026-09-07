<script setup>
import { ref, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'
import ReviewCard from '@/Components/ReviewCard.vue'
import StarRating from '@/Components/StarRating.vue'

const { t } = useI18n()

const props = defineProps({
    master: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const loading = ref(false)
const failed = ref(false)
const stats = ref({})
const reviews = ref([])

async function load(masterId) {
    loading.value = true
    failed.value = false

    try {
        const { data } = await window.axios.get(route('masters.reviews', masterId))
        stats.value = data.stats ?? {}
        reviews.value = data.reviews ?? []
    } catch {
        failed.value = true
    } finally {
        loading.value = false
    }
}

watch(() => props.master, (master) => {
    if (master) {
        stats.value = {}
        reviews.value = []
        load(master.id)
    }
}, { immediate: true })

const distribution = computed(() => {
    const dist = stats.value?.distribution ?? {}
    const max = Math.max(1, ...Object.values(dist).map(Number))

    return [5, 4, 3, 2, 1].map((star) => ({
        star,
        count: Number(dist[star] ?? 0),
        percent: (Number(dist[star] ?? 0) / max) * 100,
    }))
})
</script>

<template>
    <Modal :show="master !== null" max-width="xl" @close="emit('close')">
        <div class="flex h-full flex-col">
            <!-- Header -->
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ t('reviews.master_panel.title') }}
                    </h2>
                    <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-slate-400">
                        {{ master?.name }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                    :title="t('layout.actions.close')"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Summary -->
            <div v-if="!loading && !failed && stats.total > 0" class="shrink-0 border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                <div class="flex items-center gap-5">
                    <div class="shrink-0 text-center">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ stats.average !== null ? Number(stats.average).toFixed(1) : '—' }}
                        </p>
                        <StarRating :rating="stats.average ?? 0" size="sm" class="mt-1" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">
                            {{ stats.total }}
                        </p>
                    </div>
                    <div class="flex-1 space-y-1">
                        <div v-for="row in distribution" :key="row.star" class="flex items-center gap-2">
                            <span class="w-8 shrink-0 text-xs font-medium text-gray-500 dark:text-slate-400">
                                {{ row.star }} ★
                            </span>
                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                                <span
                                    class="block h-full rounded-full"
                                    :class="row.star <= 2 ? 'bg-red-400' : row.star === 3 ? 'bg-amber-400' : 'bg-emerald-400'"
                                    :style="{ width: `${row.percent}%` }"
                                />
                            </span>
                            <span class="w-6 shrink-0 text-right text-xs tabular-nums text-gray-400 dark:text-slate-500">
                                {{ row.count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto bg-gray-50 px-6 py-4 dark:bg-slate-900/40">
                <!-- Skeleton -->
                <div v-if="loading" class="space-y-3">
                    <div
                        v-for="n in 3"
                        :key="n"
                        class="animate-pulse rounded-xl bg-white p-4 shadow-sm dark:bg-slate-800"
                    >
                        <div class="flex items-start gap-3">
                            <div class="h-9 w-9 shrink-0 rounded-full bg-gray-200 dark:bg-slate-700" />
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-1/3 rounded bg-gray-200 dark:bg-slate-700" />
                                <div class="h-3 w-full rounded bg-gray-200 dark:bg-slate-700" />
                                <div class="h-3 w-2/3 rounded bg-gray-200 dark:bg-slate-700" />
                            </div>
                        </div>
                    </div>
                </div>

                <p v-else-if="failed" class="py-12 text-center text-sm text-red-500">
                    {{ t('reviews.master_panel.error') }}
                </p>

                <p v-else-if="!reviews.length" class="py-12 text-center text-sm text-gray-400 dark:text-slate-500">
                    {{ t('reviews.master_panel.empty') }}
                </p>

                <div v-else class="space-y-3">
                    <ReviewCard
                        v-for="review in reviews"
                        :key="review.id"
                        :review="review"
                        :show-master="false"
                    />
                </div>
            </div>

            <!-- Footer -->
            <div class="flex shrink-0 justify-between gap-2 border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                <Link
                    v-if="master"
                    :href="route('reviews.index', { master_id: master.id })"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                >
                    {{ t('reviews.master_panel.show_all') }}
                </Link>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    {{ t('layout.actions.close') }}
                </button>
            </div>
        </div>
    </Modal>
</template>
