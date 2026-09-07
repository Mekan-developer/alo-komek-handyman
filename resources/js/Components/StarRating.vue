<script setup>
import { computed } from 'vue'

const props = defineProps({
    rating: { type: [Number, String], default: 0 },
    size: { type: String, default: 'md' },
    showValue: { type: Boolean, default: false },
})

const value = computed(() => Number(props.rating) || 0)

const sizeClass = computed(() => ({
    xs: 'h-3 w-3',
    sm: 'h-3.5 w-3.5',
    md: 'h-4 w-4',
    lg: 'h-5 w-5',
}[props.size] ?? 'h-4 w-4'))

const valueClass = computed(() => ({
    xs: 'text-xs',
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
}[props.size] ?? 'text-sm'))

/** Fill fraction per star (0…1), so a 4.5 average renders as half a star. */
const fills = computed(() =>
    [1, 2, 3, 4, 5].map((star) => Math.min(1, Math.max(0, value.value - star + 1))),
)
</script>

<template>
    <span class="inline-flex items-center gap-1">
        <span class="inline-flex items-center gap-0.5">
            <span
                v-for="(fill, index) in fills"
                :key="index"
                class="relative inline-block"
                :class="sizeClass"
            >
                <svg
                    class="absolute inset-0 text-gray-200 dark:text-slate-600"
                    :class="sizeClass"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.446a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.286-3.957a1 1 0 00-.363-1.118L2.02 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.958z" />
                </svg>
                <span
                    class="absolute inset-0 overflow-hidden"
                    :style="{ width: `${fill * 100}%` }"
                >
                    <svg
                        class="text-amber-400"
                        :class="sizeClass"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.446a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.286-3.957a1 1 0 00-.363-1.118L2.02 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.958z" />
                    </svg>
                </span>
            </span>
        </span>
        <span
            v-if="showValue"
            :class="valueClass"
            class="font-semibold text-gray-900 dark:text-slate-200"
        >
            {{ value.toFixed(1) }}
        </span>
    </span>
</template>
