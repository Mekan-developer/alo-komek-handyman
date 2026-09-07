<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import StarRating from '@/Components/StarRating.vue'
import { formatPhone } from '@/utils/formatPhone'

const { t } = useI18n()

const props = defineProps({
    review: { type: Object, required: true },
    showMaster: { type: Boolean, default: true },
})

const initials = computed(() => {
    const name = props.review.client?.name ?? props.review.order?.client_name ?? '?'

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('')
})

const clientName = computed(
    () => props.review.client?.name || props.review.order?.client_name || '—',
)

/** Low scores are what an admin scans for — make them visually loud. */
const isNegative = computed(() => props.review.rating <= 2)
</script>

<template>
    <div
        class="rounded-xl bg-white p-4 shadow-sm ring-1 transition-colors dark:bg-slate-800"
        :class="isNegative
            ? 'ring-red-200 dark:ring-red-500/30'
            : 'ring-gray-100 dark:ring-slate-700/60'"
    >
        <div class="flex items-start gap-3">
            <!-- Client avatar -->
            <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                :class="isNegative
                    ? 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300'
                    : 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300'"
            >
                {{ initials }}
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-200">
                            {{ clientName }}
                        </p>
                        <p
                            v-if="review.client?.phone"
                            class="text-xs text-gray-400 dark:text-slate-500"
                        >
                            {{ formatPhone(review.client.phone) }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <StarRating :rating="review.rating" size="sm" />
                        <span class="text-xs text-gray-400 dark:text-slate-500">
                            {{ review.created_at }}
                        </span>
                    </div>
                </div>

                <!-- Master (hidden when the card already lives under one master) -->
                <div
                    v-if="showMaster && review.master"
                    class="mt-2 flex items-center gap-2"
                >
                    <span class="h-6 w-6 shrink-0 overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200 dark:bg-slate-700 dark:ring-slate-600">
                        <img
                            v-if="review.master.photo_url"
                            :src="review.master.photo_url"
                            :alt="review.master.name"
                            class="h-full w-full object-cover"
                        />
                    </span>
                    <span class="text-xs font-medium text-gray-600 dark:text-slate-300">
                        {{ review.master.name }}
                    </span>
                </div>

                <!-- Comment -->
                <p
                    v-if="review.comment"
                    class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-slate-300"
                >
                    {{ review.comment }}
                </p>
                <p v-else class="mt-2 text-sm italic text-gray-300 dark:text-slate-600">
                    {{ t('reviews.no_comment') }}
                </p>

                <!-- Order link -->
                <div
                    v-if="review.order"
                    class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-2.5 dark:border-slate-700"
                >
                    <Link
                        :href="route('orders.show', review.order.id)"
                        class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:bg-slate-700/60 dark:text-slate-300 dark:hover:bg-blue-500/15 dark:hover:text-blue-300"
                    >
                        {{ t('reviews.order_no', { id: review.order.id }) }}
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </Link>
                    <span
                        v-if="review.order.category"
                        class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                    >
                        {{ review.order.category.name }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
