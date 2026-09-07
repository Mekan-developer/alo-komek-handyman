<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ReviewCard from '@/Components/ReviewCard.vue'
import StarRating from '@/Components/StarRating.vue'

const { t } = useI18n()

const props = defineProps({
    reviews: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    masters: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

// ── Filters ───────────────────────────────────────────────────────────────────
const masterId = ref(props.filters.master_id ? String(props.filters.master_id) : '')
const rating = ref(props.filters.rating ? String(props.filters.rating) : '')
const onlyWithComment = ref(Boolean(props.filters.only_with_comment))
const search = ref(props.filters.search ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

const activeFilters = computed(() => ({
    ...(masterId.value ? { master_id: masterId.value } : {}),
    ...(rating.value ? { rating: rating.value } : {}),
    ...(onlyWithComment.value ? { only_with_comment: 1 } : {}),
    ...(search.value ? { search: search.value } : {}),
    ...(dateFrom.value ? { date_from: dateFrom.value } : {}),
    ...(dateTo.value ? { date_to: dateTo.value } : {}),
}))

const hasActiveFilters = computed(() => Object.keys(activeFilters.value).length > 0)

function applyFilters() {
    router.get(route('reviews.index'), activeFilters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    masterId.value = ''
    rating.value = ''
    onlyWithComment.value = false
    search.value = ''
    dateFrom.value = ''
    dateTo.value = ''
}

/** Clicking a histogram bar (or the same bar again) toggles that star filter. */
function toggleRating(star) {
    rating.value = rating.value === String(star) ? '' : String(star)
}

function selectMaster(id) {
    masterId.value = masterId.value === String(id) ? '' : String(id)
}

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
})

watch([masterId, rating, onlyWithComment, dateFrom, dateTo], applyFilters)

// ── Derived data ──────────────────────────────────────────────────────────────
const reviewList = computed(() => props.reviews?.data ?? [])
const paginationMeta = computed(() => props.reviews?.meta ?? null)

const distribution = computed(() => {
    const dist = props.stats?.distribution ?? {}
    const max = Math.max(1, ...Object.values(dist).map(Number))

    return [5, 4, 3, 2, 1].map((star) => ({
        star,
        count: Number(dist[star] ?? 0),
        percent: (Number(dist[star] ?? 0) / max) * 100,
    }))
})

const statCards = computed(() => [
    {
        key: 'total',
        label: t('reviews.stats.total'),
        value: props.stats.total ?? 0,
        icon: 'M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z',
        bg: 'bg-blue-50 dark:bg-blue-500/10',
        color: 'text-blue-600 dark:text-blue-400',
    },
    {
        key: 'with_comment',
        label: t('reviews.stats.with_comment'),
        value: props.stats.with_comment ?? 0,
        icon: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.019z',
        bg: 'bg-indigo-50 dark:bg-indigo-500/10',
        color: 'text-indigo-600 dark:text-indigo-400',
    },
    {
        key: 'negative',
        label: t('reviews.stats.negative'),
        value: props.stats.negative ?? 0,
        icon: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
        bg: 'bg-red-50 dark:bg-red-500/10',
        color: 'text-red-600 dark:text-red-400',
    },
])
</script>

<template>
    <Head :title="t('reviews.title')" />

    <AdminLayout :title="t('reviews.title')">
        <div class="space-y-5">
            <!-- Page header -->
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ t('reviews.title') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ t('reviews.subtitle') }}
                </p>
            </div>

            <!-- Summary: average + histogram + counters -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Average -->
                <div class="flex flex-col items-center justify-center rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60">
                    <p class="text-4xl font-bold text-gray-900 dark:text-white">
                        {{ stats.average !== null && stats.average !== undefined ? Number(stats.average).toFixed(1) : '—' }}
                    </p>
                    <StarRating :rating="stats.average ?? 0" size="lg" class="mt-2" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                        {{ t('reviews.stats.average') }}
                    </p>
                </div>

                <!-- Histogram -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                        {{ t('reviews.distribution') }}
                    </p>
                    <div class="space-y-1.5">
                        <button
                            v-for="row in distribution"
                            :key="row.star"
                            type="button"
                            @click="toggleRating(row.star)"
                            class="flex w-full items-center gap-2 rounded-md px-1 py-0.5 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/50"
                            :class="rating === String(row.star) ? 'bg-amber-50 dark:bg-amber-500/10' : ''"
                        >
                            <span class="w-8 shrink-0 text-left text-xs font-medium text-gray-500 dark:text-slate-400">
                                {{ row.star }} ★
                            </span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                                <span
                                    class="block h-full rounded-full transition-all duration-300"
                                    :class="row.star <= 2 ? 'bg-red-400' : row.star === 3 ? 'bg-amber-400' : 'bg-emerald-400'"
                                    :style="{ width: `${row.percent}%` }"
                                />
                            </span>
                            <span class="w-8 shrink-0 text-right text-xs tabular-nums text-gray-500 dark:text-slate-400">
                                {{ row.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Counters -->
                <div class="grid grid-cols-1 gap-3">
                    <div
                        v-for="stat in statCards"
                        :key="stat.key"
                        class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60"
                    >
                        <span :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', stat.bg]">
                            <svg :class="['h-5 w-5', stat.color]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="stat.icon" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-lg font-bold leading-tight text-gray-900 dark:text-white">{{ stat.value }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60">
                <div class="relative min-w-[16rem] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="t('reviews.filters.search')"
                        class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
                    />
                </div>

                <select
                    v-model="masterId"
                    class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
                >
                    <option value="">{{ t('reviews.filters.all_masters') }}</option>
                    <option v-for="master in masters" :key="master.id" :value="String(master.id)">
                        {{ master.name }} — {{ master.reviews_avg_rating }} ★ ({{ master.reviews_count }})
                    </option>
                </select>

                <select
                    v-model="rating"
                    class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
                >
                    <option value="">{{ t('reviews.filters.all_ratings') }}</option>
                    <option v-for="star in [5, 4, 3, 2, 1]" :key="star" :value="String(star)">
                        {{ star }} ★
                    </option>
                </select>

                <input
                    v-model="dateFrom"
                    type="date"
                    :aria-label="t('reviews.filters.date_from')"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
                />
                <input
                    v-model="dateTo"
                    type="date"
                    :aria-label="t('reviews.filters.date_to')"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
                />

                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-slate-300">
                    <input
                        v-model="onlyWithComment"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-700"
                    />
                    {{ t('reviews.filters.only_with_comment') }}
                </label>

                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                >
                    {{ t('reviews.filters.reset') }}
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
                <!-- Master leaderboard -->
                <aside class="xl:col-span-1">
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60">
                        <div class="border-b border-gray-100 px-4 py-3 dark:border-slate-700">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                {{ t('reviews.filters.master') }}
                            </h2>
                        </div>
                        <div v-if="masters.length" class="max-h-[32rem] divide-y divide-gray-100 overflow-y-auto dark:divide-slate-700">
                            <button
                                v-for="master in masters"
                                :key="master.id"
                                type="button"
                                @click="selectMaster(master.id)"
                                class="flex w-full items-center justify-between gap-2 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/50"
                                :class="masterId === String(master.id) ? 'bg-blue-50 dark:bg-blue-500/10' : ''"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-slate-200">
                                        {{ master.name }}
                                    </span>
                                    <span class="mt-0.5 flex items-center gap-1.5">
                                        <StarRating :rating="master.reviews_avg_rating ?? 0" size="xs" />
                                        <span class="text-xs text-gray-400 dark:text-slate-500">
                                            {{ master.reviews_avg_rating }} · {{ master.reviews_count }}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </div>
                        <p v-else class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500">
                            {{ t('reviews.empty') }}
                        </p>
                    </div>
                </aside>

                <!-- Reviews list -->
                <div class="xl:col-span-3">
                    <div v-if="reviewList.length" class="grid grid-cols-1 gap-3 2xl:grid-cols-2">
                        <ReviewCard
                            v-for="review in reviewList"
                            :key="review.id"
                            :review="review"
                        />
                    </div>

                    <div
                        v-else
                        class="rounded-xl bg-white px-6 py-16 text-center text-sm text-gray-400 shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:text-slate-500 dark:ring-slate-700/60"
                    >
                        {{ hasActiveFilters ? t('reviews.empty_filtered') : t('reviews.empty') }}
                    </div>

                    <Pagination
                        v-if="paginationMeta"
                        :meta="paginationMeta"
                        route-name="reviews.index"
                        :route-params="activeFilters"
                        class="mt-3 rounded-xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-slate-800 dark:ring-slate-700/60"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
