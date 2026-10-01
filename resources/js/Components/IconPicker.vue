<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import ServiceIcon from '@/Components/ServiceIcon.vue'

const { t } = useI18n()

const props = defineProps({
    // Inertia form object; the picker mutates icon_type / icon / icon_file directly.
    form: { type: Object, required: true },
    // Grouped preset keys from config/service_icons.php: { group: [key, ...] }.
    iconGroups: { type: Object, default: () => ({}) },
    // URL of an already-saved icon (used to preview a custom upload when editing).
    existingIconUrl: { type: String, default: null },
    // icon_type of the already-saved icon, so its preview shows only in the matching tab.
    existingIconType: { type: String, default: null },
})

const TAB_BY_TYPE = { custom: 'upload', image: 'image' }
const IMAGE_ACCEPT = 'image/png,image/jpeg,image/webp,image/gif'

const tab = ref('preset') // 'preset' | 'upload' | 'image'
const search = ref('')
const dragging = ref(false)
const uploadPreview = ref(null) // object URL for a freshly chosen file

// Open the tab that matches the current selection.
watch(
    () => props.form.icon_type,
    (type) => { tab.value = TAB_BY_TYPE[type] ?? 'preset' },
    { immediate: true },
)

const presetUrl = (key) => `/icons/services/${key}.svg`

const filteredGroups = computed(() => {
    const term = search.value.trim().toLowerCase()
    const out = {}
    for (const [group, keys] of Object.entries(props.iconGroups)) {
        const matched = term ? keys.filter((k) => k.toLowerCase().includes(term)) : keys
        if (matched.length) {
            out[group] = matched
        }
    }
    return out
})

const hasResults = computed(() => Object.keys(filteredGroups.value).length > 0)

const isPresetSelected = (key) => props.form.icon_type === 'preset' && props.form.icon === key

function selectPreset(key) {
    clearPreview()
    props.form.icon_type = 'preset'
    props.form.icon = key
    props.form.icon_file = null
}

// type: 'custom' (SVG) | 'image' (png/jpg/webp/gif — shrunk server-side)
function pickFile(file, type) {
    if (!file) {
        return
    }
    clearPreview()
    props.form.icon_type = type
    props.form.icon_file = file
    props.form.icon = null
    uploadPreview.value = URL.createObjectURL(file)
}

function onInput(event, type) {
    pickFile(event.target.files?.[0], type)
    event.target.value = '' // allow re-selecting the same file
}

function onDrop(event, type) {
    dragging.value = false
    pickFile(event.dataTransfer?.files?.[0], type)
}

function clearIcon() {
    clearPreview()
    props.form.icon_type = null
    props.form.icon = null
    props.form.icon_file = null
}

function clearPreview() {
    if (uploadPreview.value) {
        URL.revokeObjectURL(uploadPreview.value)
        uploadPreview.value = null
    }
}

onBeforeUnmount(clearPreview)

// Upload preview for a tab: a freshly chosen file wins over the saved one.
function previewFor(type) {
    if (props.form.icon_type !== type) {
        return null
    }
    return uploadPreview.value || (props.existingIconType === type ? props.existingIconUrl : null)
}

const customPreviewUrl = computed(() => previewFor('custom'))
const imagePreviewUrl = computed(() => previewFor('image'))
const hasIcon = computed(() => props.form.icon_type !== null && props.form.icon_type !== undefined)

const tabBtn = (active) =>
    active
        ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'
</script>

<template>
    <div>
        <div class="mb-2 flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                {{ t('categories.icon') }}
            </label>
            <button
                v-if="hasIcon"
                type="button"
                @click="clearIcon"
                class="text-xs font-medium text-gray-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 transition-colors"
            >
                {{ t('categories.icon_remove') }}
            </button>
        </div>

        <!-- Tabs -->
        <div class="mb-3 flex gap-4 border-b border-gray-200 dark:border-slate-700">
            <button
                type="button"
                @click="tab = 'preset'"
                :class="tabBtn(tab === 'preset')"
                class="-mb-px border-b-2 px-1 pb-2 text-sm font-medium transition-colors"
            >
                {{ t('categories.icon_tab_preset') }}
            </button>
            <button
                type="button"
                @click="tab = 'upload'"
                :class="tabBtn(tab === 'upload')"
                class="-mb-px border-b-2 px-1 pb-2 text-sm font-medium transition-colors"
            >
                {{ t('categories.icon_tab_upload') }}
            </button>
            <button
                type="button"
                @click="tab = 'image'"
                :class="tabBtn(tab === 'image')"
                class="-mb-px border-b-2 px-1 pb-2 text-sm font-medium transition-colors"
            >
                {{ t('categories.icon_tab_image') }}
            </button>
        </div>

        <!-- Preset set -->
        <div v-show="tab === 'preset'">
            <input
                v-model="search"
                type="text"
                :placeholder="t('categories.icon_search')"
                class="mb-3 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white dark:placeholder-slate-500"
            />

            <div class="max-h-56 space-y-3 overflow-y-auto pr-1">
                <div v-for="(keys, group) in filteredGroups" :key="group">
                    <p class="mb-1.5 text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-slate-500">
                        {{ t(`categories.icon_group_${group}`) }}
                    </p>
                    <div class="grid grid-cols-6 gap-2 sm:grid-cols-8">
                        <button
                            v-for="key in keys"
                            :key="key"
                            type="button"
                            @click="selectPreset(key)"
                            :title="key"
                            :class="isPresetSelected(key)
                                ? 'border-blue-500 bg-blue-50 text-blue-600 dark:border-blue-400 dark:bg-blue-500/15 dark:text-blue-400'
                                : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700/40'"
                            class="flex aspect-square items-center justify-center rounded-lg border transition-all"
                        >
                            <ServiceIcon :url="presetUrl(key)" class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <p v-if="!hasResults" class="py-6 text-center text-sm text-gray-400 dark:text-slate-500">
                    {{ t('categories.icon_search_empty') }}
                </p>
            </div>
        </div>

        <!-- Upload SVG -->
        <div v-show="tab === 'upload'">
            <label
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop($event, 'custom')"
                :class="dragging
                    ? 'border-blue-400 bg-blue-50 dark:border-blue-500/70 dark:bg-blue-500/10'
                    : 'border-gray-300 hover:border-gray-400 dark:border-slate-600 dark:hover:border-slate-500'"
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors"
            >
                <span
                    v-if="customPreviewUrl"
                    class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-200"
                >
                    <ServiceIcon :url="customPreviewUrl" svg class="h-7 w-7" />
                </span>
                <svg v-else class="h-7 w-7 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-slate-300">
                    {{ t('categories.icon_upload_cta') }}
                </span>
                <span class="text-xs text-gray-400 dark:text-slate-500">
                    {{ t('categories.icon_upload_hint') }}
                </span>
                <input type="file" accept=".svg,image/svg+xml" class="hidden" @change="onInput($event, 'custom')" />
            </label>
        </div>

        <!-- Upload raster image -->
        <div v-show="tab === 'image'">
            <label
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop($event, 'image')"
                :class="dragging
                    ? 'border-blue-400 bg-blue-50 dark:border-blue-500/70 dark:bg-blue-500/10'
                    : 'border-gray-300 hover:border-gray-400 dark:border-slate-600 dark:hover:border-slate-500'"
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors"
            >
                <img
                    v-if="imagePreviewUrl"
                    :src="imagePreviewUrl"
                    alt=""
                    class="h-16 max-w-[8rem] rounded-lg object-contain"
                />
                <svg v-else class="h-7 w-7 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zm10.5-11.25h.008v.008h-.008V9.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-slate-300">
                    {{ t('categories.icon_image_cta') }}
                </span>
                <span class="text-xs text-gray-400 dark:text-slate-500">
                    {{ t('categories.icon_image_hint') }}
                </span>
                <input type="file" :accept="IMAGE_ACCEPT" class="hidden" @change="onInput($event, 'image')" />
            </label>
        </div>
    </div>
</template>
