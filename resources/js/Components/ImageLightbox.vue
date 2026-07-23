<script setup>
/**
 * Переиспользуемый просмотрщик изображений (лайтбокс).
 *
 * Открывает изображение на весь экран, позволяет листать галерею
 * стрелками / клавиатурой и выбирать кадр через миниатюры.
 *
 * @prop {Boolean} show      — открыт ли просмотрщик
 * @prop {Array}   images    — список url-строк ИЛИ объектов вида { url, ... }
 * @prop {Number}  startIndex — индекс кадра, с которого открыть
 * @emits close
 */
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    images: { type: Array, default: () => [] },
    startIndex: { type: Number, default: 0 },
})

const emit = defineEmits(['close'])

const current = ref(props.startIndex)

const urls = computed(() => props.images.map(i => (typeof i === 'string' ? i : i?.url)).filter(Boolean))
const total = computed(() => urls.value.length)
const currentUrl = computed(() => urls.value[current.value] ?? null)
const hasMultiple = computed(() => total.value > 1)

watch(() => props.show, (val) => {
    if (val) { current.value = clampIndex(props.startIndex) }
    document.body.style.overflow = val ? 'hidden' : ''
})

watch(() => props.startIndex, (val) => {
    current.value = clampIndex(val)
})

function clampIndex(i) {
    if (!total.value) { return 0 }
    return Math.min(Math.max(i, 0), total.value - 1)
}

function close() {
    emit('close')
}

function next() {
    if (total.value) { current.value = (current.value + 1) % total.value }
}

function prev() {
    if (total.value) { current.value = (current.value - 1 + total.value) % total.value }
}

function onKeydown(e) {
    if (!props.show) { return }
    if (e.key === 'Escape') { close() }
    else if (e.key === 'ArrowRight') { next() }
    else if (e.key === 'ArrowLeft') { prev() }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && currentUrl"
                class="fixed inset-0 z-[9999] flex flex-col"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="close" />

                <!-- Top bar -->
                <div class="relative z-10 flex items-center justify-between px-5 py-3 text-white">
                    <span v-if="hasMultiple" class="rounded-full bg-white/10 px-3 py-1 text-sm font-medium tabular-nums">
                        {{ current + 1 }} / {{ total }}
                    </span>
                    <span v-else />
                    <button
                        type="button"
                        @click="close"
                        class="rounded-full p-2 text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Main image -->
                <div class="relative z-10 flex min-h-0 flex-1 items-center justify-center px-4" @click.self="close">
                    <!-- Prev -->
                    <button
                        v-if="hasMultiple"
                        type="button"
                        @click.stop="prev"
                        class="absolute left-3 z-20 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:left-6"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <img
                            :key="currentUrl"
                            :src="currentUrl"
                            alt=""
                            class="max-h-full max-w-full rounded-lg object-contain shadow-2xl"
                        />
                    </Transition>

                    <!-- Next -->
                    <button
                        v-if="hasMultiple"
                        type="button"
                        @click.stop="next"
                        class="absolute right-3 z-20 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:right-6"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

                <!-- Thumbnails -->
                <div
                    v-if="hasMultiple"
                    class="relative z-10 flex shrink-0 items-center justify-center gap-2 overflow-x-auto px-4 py-4"
                >
                    <button
                        v-for="(url, i) in urls"
                        :key="i"
                        type="button"
                        @click="current = i"
                        :class="i === current
                            ? 'ring-2 ring-white opacity-100'
                            : 'opacity-50 hover:opacity-100'"
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-lg ring-1 ring-white/30 transition-all"
                    >
                        <img :src="url" alt="" class="h-full w-full object-cover" />
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
