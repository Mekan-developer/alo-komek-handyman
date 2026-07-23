<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
    show:     { type: Boolean, default: false },
    maxWidth: { type: String,  default: 'md' },
    closeable:{ type: Boolean, default: true },
    centered: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const maxWidthClass = computed(() => ({
    sm:  'max-w-sm',
    md:  'max-w-md',
    lg:  'max-w-lg',
    xl:  'max-w-xl',
    '2xl': 'max-w-2xl',
}[props.maxWidth] ?? 'max-w-md'))

function close() {
    if (props.closeable) {
        emit('close')
    }
}

function onKeydown(e) {
    if (e.key === 'Escape' && props.show) {
        close()
    }
}

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition name="modal-backdrop">
            <div
                v-if="show"
                class="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-md"
                @click="close"
            />
        </Transition>

        <!-- Centered dialog -->
        <Transition v-if="centered" name="modal-centered">
            <div
                v-if="show"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
            >
                <div
                    class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-800 dark:ring-1 dark:ring-slate-700"
                    :class="maxWidthClass"
                    @click.stop
                >
                    <slot />
                </div>
            </div>
        </Transition>

        <!-- Drawer — slides in from right -->
        <Transition v-else name="modal-panel">
            <div
                v-if="show"
                class="fixed inset-y-0 right-0 z-[99999] flex w-full flex-col overflow-hidden bg-white shadow-2xl dark:bg-slate-800 dark:ring-1 dark:ring-slate-700"
                :class="maxWidthClass"
            >
                <slot />
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
    transition: opacity 0.25s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}

/* Drawer animation */
.modal-panel-enter-active,
.modal-panel-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.modal-panel-enter-from,
.modal-panel-leave-to {
    transform: translateX(100%);
}

/* Centered dialog animation */
.modal-centered-enter-active,
.modal-centered-leave-active {
    transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.modal-centered-enter-from,
.modal-centered-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
</style>
