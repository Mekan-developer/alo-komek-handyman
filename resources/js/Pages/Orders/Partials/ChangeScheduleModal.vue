<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import { isTimeSlotAvailable, localTodayIso, formatTimeSlotLabel } from '@/utils/orderSchedule'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    order: { type: Object, required: true },
    timeSlots: { type: Array, default: () => [] },
    urgencyFee: { type: Number, default: 20 },
})

const emit = defineEmits(['close'])

const form = useForm({
    preferred_date: '',
    time_slot: null,
    is_urgent: false,
})

watch(() => props.show, (val) => {
    if (val) {
        form.is_urgent = !!props.order.is_urgent
        form.preferred_date = props.order.preferred_date ?? (form.is_urgent ? '' : localTodayIso())
        form.time_slot = props.order.time_slot ?? null

        if (!form.is_urgent && form.preferred_date && form.preferred_date < localTodayIso()) {
            form.preferred_date = localTodayIso()
        }

        if (form.time_slot && !isTimeSlotAvailable(form.time_slot, form.preferred_date)) {
            form.time_slot = null
        }

        form.clearErrors()
    }
})

function onUrgentChange() {
    if (form.is_urgent) {
        form.preferred_date = ''
        form.time_slot = null
    } else if (!form.preferred_date) {
        form.preferred_date = localTodayIso()
    }
}

const availableTimeSlots = computed(() =>
    props.timeSlots.filter((slot) => isTimeSlotAvailable(slot, form.preferred_date))
)

watch(() => form.preferred_date, (date) => {
    if (date && date < localTodayIso()) {
        form.preferred_date = localTodayIso()
    }

    if (form.time_slot && !isTimeSlotAvailable(form.time_slot, form.preferred_date)) {
        form.time_slot = null
    }
})

function submit() {
    form.put(route('orders.update-schedule', props.order.id), {
        onSuccess: () => emit('close'),
    })
}

const inputClass = 'w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white dark:focus:bg-slate-700'
const errorInputClass = 'border-red-400 dark:border-red-500'
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-slate-300'
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <div class="flex h-full flex-col">
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ t('orders.create.change_schedule') }}
                </h2>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-slate-300 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="flex flex-1 flex-col">
                <div class="space-y-4 px-6 py-5">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/50 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                        <input
                            v-model="form.is_urgent"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            @change="onUrgentChange"
                        />
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-slate-100">
                                {{ t('orders.fields.is_urgent') }}
                            </span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-slate-400">
                                {{ t('orders.create.urgent_hint', { amount: urgencyFee }) }}
                            </span>
                        </span>
                    </label>

                    <template v-if="!form.is_urgent">
                        <div class="space-y-1">
                            <label :class="labelClass">{{ t('orders.fields.preferred_date') }} <span class="text-red-400">*</span></label>
                            <input
                                v-model="form.preferred_date"
                                type="date"
                                :min="localTodayIso()"
                                :class="[inputClass, form.errors.preferred_date ? errorInputClass : '']"
                            />
                            <InputError :message="form.errors.preferred_date" />
                        </div>

                        <div class="space-y-1.5">
                            <label :class="labelClass">{{ t('orders.fields.time_slot') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="form.time_slot = null"
                                    :class="[
                                        'rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                                        form.time_slot === null
                                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/20 dark:text-blue-300'
                                            : 'border-gray-300 text-gray-600 dark:border-slate-600 dark:text-slate-300',
                                    ]"
                                >
                                    {{ t('orders.create.time_flexible') }}
                                </button>
                                <button
                                    v-for="slot in availableTimeSlots"
                                    :key="slot"
                                    type="button"
                                    @click="form.time_slot = slot"
                                    :class="[
                                        'rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                                        form.time_slot === slot
                                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/20 dark:text-blue-300'
                                            : 'border-gray-300 text-gray-600 dark:border-slate-600 dark:text-slate-300',
                                    ]"
                                >
                                    {{ formatTimeSlotLabel(slot) }}
                                </button>
                            </div>
                            <InputError :message="form.errors.time_slot" />
                        </div>
                    </template>
                </div>

                <div class="flex shrink-0 justify-end gap-2 border-t border-gray-100 px-6 py-4 dark:border-slate-700">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
                    >
                        {{ t('layout.actions.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? '...' : t('layout.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
