<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    meta: { type: Object, required: true },
    routeName: { type: String, required: true },
    routeParams: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const current = computed(() => props.meta?.current_page ?? 1)
const last = computed(() => props.meta?.last_page ?? 1)
const from = computed(() => props.meta?.from ?? 0)
const to = computed(() => props.meta?.to ?? 0)
const total = computed(() => props.meta?.total ?? 0)

function pageUrl(page) {
    return route(props.routeName, { ...props.routeParams, page })
}

// Windowed pages with '...' gaps: always show first 2, last 2, and ±1 around current.
// Single-page gaps (e.g. [..., 5, 7]) get filled instead of showing '...'.
const pages = computed(() => {
    const c = current.value
    const l = last.value

    if (l <= 7) {
        return Array.from({ length: l }, (_, i) => i + 1)
    }

    const set = new Set(
        [1, 2, Math.max(1, c - 1), c, Math.min(l, c + 1), l - 1, l].filter(
            (n) => n >= 1 && n <= l,
        ),
    )
    const sorted = [...set].sort((a, b) => a - b)

    const result = []
    let prev = 0

    for (const p of sorted) {
        if (p - prev === 2) {
            result.push(p - 1)
        } else if (p - prev > 2) {
            result.push('...')
        }
        result.push(p)
        prev = p
    }

    return result
})
</script>

<template>
    <div
        v-if="last > 1"
        class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-slate-700"
    >
        <p class="text-sm text-gray-500 dark:text-slate-400">
            {{ t('layout.pagination.showing', { from, to, total }) }}
        </p>

        <div class="flex items-center gap-1">
            <!-- Prev -->
            <Link
                v-if="current > 1"
                :href="pageUrl(current - 1)"
                class="inline-flex h-8 items-center rounded-md px-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                ←
            </Link>
            <span
                v-else
                class="inline-flex h-8 cursor-not-allowed items-center rounded-md px-2.5 text-sm font-medium text-gray-300 dark:text-slate-600"
            >
                ←
            </span>

            <!-- Pages -->
            <template v-for="(page, idx) in pages" :key="idx">
                <span
                    v-if="page === '...'"
                    class="inline-flex h-8 w-7 items-center justify-center text-sm text-gray-400 dark:text-slate-500"
                >
                    …
                </span>
                <Link
                    v-else
                    :href="pageUrl(page)"
                    :class="
                        page === current
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700'
                    "
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm font-medium transition-colors"
                >
                    {{ page }}
                </Link>
            </template>

            <!-- Next -->
            <Link
                v-if="current < last"
                :href="pageUrl(current + 1)"
                class="inline-flex h-8 items-center rounded-md px-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                →
            </Link>
            <span
                v-else
                class="inline-flex h-8 cursor-not-allowed items-center rounded-md px-2.5 text-sm font-medium text-gray-300 dark:text-slate-600"
            >
                →
            </span>
        </div>
    </div>
</template>
