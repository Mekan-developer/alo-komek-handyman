<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    oblasts: { type: Array, default: () => [] },
    allOblastsLabel: { type: String, required: true },
    allCitiesLabel: { type: String, required: true },
    selectClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const selectedOblastId = ref(null)

// Пока велаят не выбран — показываем города всех велаятов, как раньше.
const cities = computed(() => {
    if (!selectedOblastId.value) {
        return props.oblasts.flatMap((o) => o.cities ?? [])
    }
    return props.oblasts.find((o) => o.id === selectedOblastId.value)?.cities ?? []
})

// Подавляет реакцию watch'а на собственный сброс города при смене велаята,
// иначе только что выбранный велаят тут же затирается обратно в null.
let resettingCity = false

watch(
    () => props.modelValue,
    (id) => {
        if (resettingCity) {
            resettingCity = false
            return
        }
        if (!id) {
            selectedOblastId.value = null
            return
        }
        for (const oblast of props.oblasts) {
            if (oblast.cities?.some((c) => c.id === id)) {
                selectedOblastId.value = oblast.id
                return
            }
        }
    },
    { immediate: true },
)

function onOblastChange() {
    resettingCity = true
    emit('update:modelValue', null)
}

const cityValue = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val || null),
})
</script>

<template>
    <div class="flex items-center gap-3">
        <select
            v-model="selectedOblastId"
            @change="onOblastChange"
            :class="selectClass"
        >
            <option :value="null">{{ allOblastsLabel }}</option>
            <option v-for="o in oblasts" :key="o.id" :value="o.id">{{ o.name }}</option>
        </select>
        <select v-model="cityValue" :class="selectClass">
            <option :value="null">{{ allCitiesLabel }}</option>
            <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
    </div>
</template>
