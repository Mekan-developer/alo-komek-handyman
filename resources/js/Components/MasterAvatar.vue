<script setup>
import { computed } from 'vue'
import { initials } from '@/utils/initials'

const props = defineProps({
    name: { type: String, default: '' },
    photoUrl: { type: String, default: null },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    rounded: {
        type: String,
        default: 'full',
        validator: (value) => ['full', 'md'].includes(value),
    },
})

const label = computed(() => initials(props.name))

const sizeClass = computed(() => ({
    sm: 'h-6 w-6 text-[10px]',
    md: 'h-10 w-10 text-sm',
    lg: 'h-12 w-12 text-sm',
}[props.size]))

const shapeClass = computed(() => (props.rounded === 'md' ? 'rounded-md' : 'rounded-full'))
</script>

<template>
    <div
        class="shrink-0 overflow-hidden bg-indigo-100 ring-1 ring-indigo-200/80 dark:bg-indigo-500/20 dark:ring-indigo-400/30"
        :class="[sizeClass, shapeClass]"
    >
        <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="name"
            class="h-full w-full object-cover"
        >
        <div
            v-else
            class="flex h-full w-full items-center justify-center font-semibold text-indigo-700 dark:text-indigo-300"
        >
            {{ label }}
        </div>
    </div>
</template>
