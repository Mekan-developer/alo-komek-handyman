<script setup>
defineProps({
    status: { type: String, required: true },
    label: { type: String, required: true },
    color: { type: String, default: 'gray' },
})

const colorClasses = {
    yellow: 'bg-yellow-100 text-yellow-700 ring-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-300 dark:ring-yellow-500/30',
    blue: 'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30',
    indigo: 'bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30',
    green: 'bg-green-100 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30',
    red: 'bg-red-100 text-red-600 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30',
    gray: 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600',
}

const dotClasses = {
    yellow: 'bg-yellow-500 dark:bg-yellow-400',
    blue: 'bg-blue-500 dark:bg-blue-400',
    indigo: 'bg-indigo-500 dark:bg-indigo-400',
    green: 'bg-green-500 dark:bg-green-400',
    red: 'bg-red-400',
    gray: 'bg-gray-400',
}
</script>

<template>
    <span
        :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1', colorClasses[color] ?? colorClasses.gray]"
    >
        <span :class="['h-1.5 w-1.5 rounded-full', dotClasses[color] ?? dotClasses.gray]" />
        {{ label }}
    </span>
</template>
