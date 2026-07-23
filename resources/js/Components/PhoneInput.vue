<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    hasError: { type: Boolean, default: false },
    placeholder: { type: String, default: '62 72-44-94' },
    size: { type: String, default: 'md' }, // 'md' = py-3, 'sm' = py-2.5
})

const emit = defineEmits(['update:modelValue'])

function extractDigits(val) {
    const str = String(val ?? '')
    const raw = str.startsWith('+993') ? str.slice(4) : str
    return raw.replace(/\D/g, '').slice(0, 8)
}

function formatDigits(digits) {
    if (digits.length <= 2) return digits
    if (digits.length <= 4) return `${digits.slice(0, 2)} ${digits.slice(2)}`
    if (digits.length <= 6) return `${digits.slice(0, 2)} ${digits.slice(2, 4)}-${digits.slice(4)}`
    return `${digits.slice(0, 2)} ${digits.slice(2, 4)}-${digits.slice(4, 6)}-${digits.slice(6)}`
}

const displayValue = computed(() => formatDigits(extractDigits(props.modelValue)))

function onInput(e) {
    const digits = e.target.value.replace(/\D/g, '').slice(0, 8)
    const formatted = formatDigits(digits)
    if (e.target.value !== formatted) {
        e.target.value = formatted
    }
    emit('update:modelValue', digits.length > 0 ? `+993${digits}` : '')
}
</script>

<template>
    <div
        class="flex overflow-hidden rounded-xl border shadow-sm transition-all focus-within:ring-4"
        :class="hasError
            ? 'border-red-400 focus-within:border-red-400 focus-within:ring-red-400/20 dark:border-red-500'
            : 'border-gray-300 focus-within:border-blue-500 focus-within:ring-blue-500/20 dark:border-slate-600 dark:focus-within:border-blue-500'"
    >
        <span class="flex select-none items-center border-r border-inherit bg-gray-100 px-3 text-sm font-semibold text-gray-500 dark:bg-slate-700 dark:text-slate-400">
            +993
        </span>
        <input
            type="text"
            inputmode="numeric"
            :value="displayValue"
            :placeholder="placeholder"
            maxlength="11"
            :class="[
                'min-w-0 flex-1 bg-gray-50 px-3 text-sm text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500 dark:focus:bg-slate-700 transition-all',
                size === 'sm' ? 'py-2.5' : 'py-3',
            ]"
            @input="onInput"
        />
    </div>
</template>
