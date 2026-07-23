<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ServiceIcon from '@/Components/ServiceIcon.vue'

const { t } = useI18n()

const props = defineProps({
    modelValue: { type: [Number, Array], default: null },
    categories: { type: Array, default: () => [] },
    hasError: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    label: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const selectable = computed(() => {
    const list = []
    for (const root of props.categories) {
        if (root.children?.length) {
            list.push(...root.children)
        } else {
            list.push(root)
        }
    }
    return list
})

const selected = computed(() => selectable.value.find((c) => c.id === props.modelValue) ?? null)

const selectedCount = computed(() =>
    props.multiple ? (Array.isArray(props.modelValue) ? props.modelValue.length : 0) : 0,
)

function isSelected(id) {
    if (props.multiple) {
        return Array.isArray(props.modelValue) && props.modelValue.includes(id)
    }
    return id === props.modelValue
}

function select(id) {
    if (props.multiple) {
        const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
        const idx = current.indexOf(id)
        if (idx === -1) {
            current.push(id)
        } else {
            current.splice(idx, 1)
        }
        emit('update:modelValue', current)
    } else {
        emit('update:modelValue', id)
    }
}

function isAllChildrenSelected(root) {
    const current = Array.isArray(props.modelValue) ? props.modelValue : []
    return root.children.every((child) => current.includes(child.id))
}

function toggleAllChildren(root) {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const childIds = root.children.map((child) => child.id)

    const next = isAllChildrenSelected(root)
        ? current.filter((id) => !childIds.includes(id))
        : [...new Set([...current, ...childIds])]

    emit('update:modelValue', next)
}

const chipBase =
    'inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition-all'
const chipOn =
    'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/15 dark:text-blue-300'
const chipOff =
    'border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-600 dark:text-slate-300 dark:hover:border-blue-500/50'

const chipClass = (id) => [chipBase, isSelected(id) ? chipOn : chipOff]

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-slate-300'
</script>

<template>
    <div>
        <div class="mb-1.5 flex items-center justify-between">
            <label :class="labelClass">
                {{ label ?? t('orders.fields.category') }}
                <span v-if="required" class="text-red-400">*</span>
            </label>
            <span v-if="!multiple && selected" class="truncate text-xs font-medium text-blue-600 dark:text-blue-300">
                {{ selected.name }}
            </span>
            <span v-if="multiple && selectedCount > 0" class="text-xs font-medium text-blue-600 dark:text-blue-300">
                {{ selectedCount }} {{ t('layout.selected') }}
            </span>
        </div>

        <div
            class="max-h-72 space-y-4 overflow-y-auto rounded-xl border bg-gray-50/60 p-3 dark:bg-slate-700/30"
            :class="hasError
                ? 'border-red-400 dark:border-red-500'
                : 'border-gray-200 dark:border-slate-600'"
        >
            <p v-if="!categories.length" class="py-6 text-center text-sm text-gray-400 dark:text-slate-500">
                {{ t('orders.create.category_empty') }}
            </p>

            <div v-for="root in categories" :key="root.id" class="space-y-2">
                <!-- Группа-родитель с детьми: заголовок + чипы детей -->
                <template v-if="root.children?.length">
                    <label class="flex w-fit items-center gap-1.5 cursor-pointer select-none">
                        <input
                            v-if="multiple"
                            type="checkbox"
                            :checked="isAllChildrenSelected(root)"
                            @change="toggleAllChildren(root)"
                            class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500/20 dark:border-slate-500 dark:bg-slate-700"
                        />
                        <ServiceIcon
                            v-if="root.icon_url"
                            :url="root.icon_url"
                            class="h-3.5 w-3.5 text-gray-400 dark:text-slate-500"
                        />
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">
                            {{ root.name }}
                        </span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="child in root.children"
                            :key="child.id"
                            type="button"
                            @click="select(child.id)"
                            :class="chipClass(child.id)"
                        >
                            <ServiceIcon v-if="child.icon_url" :url="child.icon_url" class="h-4 w-4" />
                            {{ child.name }}
                        </button>
                    </div>
                </template>

                <!-- Родитель без детей — сам является выбираемой услугой -->
                <button
                    v-else
                    type="button"
                    @click="select(root.id)"
                    :class="chipClass(root.id)"
                >
                    <ServiceIcon v-if="root.icon_url" :url="root.icon_url" class="h-4 w-4" />
                    {{ root.name }}
                </button>
            </div>
        </div>
    </div>
</template>
