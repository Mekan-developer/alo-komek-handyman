<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import PhoneInput from '@/Components/PhoneInput.vue'
import CategoryPicker from '@/Components/CategoryPicker.vue'
import { isTimeSlotAvailable, localTodayIso, formatTimeSlotLabel } from '@/utils/orderSchedule'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, required: true },
    order: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    timeSlots: { type: Array, default: () => [] },
    urgencyFee: { type: Number, default: 20 },
})

const emit = defineEmits(['close'])

const form = useForm({
    category_id: null,
    client_name: '',
    client_phone: '',
    description: '',
    preferred_date: '',
    time_slot: null,
    is_urgent: false,
    client_address: '',
    client_lat: '',
    client_lng: '',
})

watch(() => props.show, (val) => {
    if (val) {
        form.category_id = props.order.category?.id ?? null
        form.client_name = props.order.client_name ?? ''
        form.client_phone = props.order.client_phone ?? ''
        form.description = props.order.description ?? ''
        form.is_urgent = !!props.order.is_urgent
        form.preferred_date = props.order.preferred_date ?? ''
        form.time_slot = props.order.time_slot ?? null
        form.client_address = props.order.client_address ?? ''
        form.client_lat = props.order.client_lat ?? ''
        form.client_lng = props.order.client_lng ?? ''

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
    form.put(route('orders.update', props.order.id), {
        onSuccess: () => emit('close'),
    })
}

const inputClass = 'w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white dark:focus:bg-slate-700'
const errorInputClass = 'border-red-400 dark:border-red-500'
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-slate-300'
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="emit('close')">
        <div class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ t('orders.modals.edit_title') }}
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

        <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
            <div class="flex-1 overflow-y-auto">
            <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">

                <div class="space-y-1">
                    <label :class="labelClass">{{ t('orders.fields.client_name') }}</label>
                    <input
                        v-model="form.client_name"
                        type="text"
                        :class="[inputClass, form.errors.client_name ? errorInputClass : '']"
                    />
                    <InputError :message="form.errors.client_name" />
                </div>

                <div class="space-y-1">
                    <label :class="labelClass">{{ t('orders.fields.client_phone') }}</label>
                    <PhoneInput
                        v-model="form.client_phone"
                        :has-error="!!form.errors.client_phone"
                        size="sm"
                    />
                    <InputError :message="form.errors.client_phone" />
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label :class="labelClass">{{ t('orders.fields.client_address') }}</label>
                    <input
                        v-model="form.client_address"
                        type="text"
                        :class="[inputClass, form.errors.client_address ? errorInputClass : '']"
                    />
                    <InputError :message="form.errors.client_address" />
                </div>

                <div class="space-y-1">
                    <label :class="labelClass">{{ t('orders.fields.client_lat') }}</label>
                    <input
                        v-model="form.client_lat"
                        type="number"
                        step="any"
                        :class="[inputClass, form.errors.client_lat ? errorInputClass : '']"
                    />
                    <InputError :message="form.errors.client_lat" />
                </div>

                <div class="space-y-1">
                    <label :class="labelClass">{{ t('orders.fields.client_lng') }}</label>
                    <input
                        v-model="form.client_lng"
                        type="number"
                        step="any"
                        :class="[inputClass, form.errors.client_lng ? errorInputClass : '']"
                    />
                    <InputError :message="form.errors.client_lng" />
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label :class="labelClass">{{ t('orders.fields.description') }}</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        :class="[inputClass, form.errors.description ? errorInputClass : '']"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="space-y-3 sm:col-span-2 rounded-xl border border-gray-200 p-4 dark:border-slate-700">
                    <div class="text-sm font-medium text-gray-800 dark:text-slate-200">
                        {{ t('orders.create.schedule_section') }}
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/50 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                        <input
                            v-model="form.is_urgent"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-amber-600"
                            @change="onUrgentChange"
                        />
                        <span class="text-sm text-gray-800 dark:text-slate-200">
                            {{ t('orders.fields.is_urgent') }}
                            <span class="block text-xs text-gray-500">{{ t('orders.create.urgent_hint', { amount: urgencyFee }) }}</span>
                        </span>
                    </label>
                    <template v-if="!form.is_urgent">
                        <div class="space-y-1">
                            <label :class="labelClass">{{ t('orders.fields.preferred_date') }}</label>
                            <input
                                v-model="form.preferred_date"
                                type="date"
                                :min="localTodayIso()"
                                :class="[inputClass, form.errors.preferred_date ? errorInputClass : '']"
                            />
                            <InputError :message="form.errors.preferred_date" />
                        </div>
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
                    </template>
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <CategoryPicker
                        v-model="form.category_id"
                        :categories="categories"
                        :has-error="!!form.errors.category_id"
                        required
                    />
                    <InputError :message="form.errors.category_id" />
                </div>
            </div>
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
                    {{ form.processing ? '...' : t('layout.actions.update') }}
                </button>
            </div>
        </form>
        </div>
    </Modal>
</template>
