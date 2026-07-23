<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class: 'rte-content min-h-64 focus:outline-none',
        },
    },
    onUpdate({ editor }) {
        emit('update:modelValue', editor.getHTML())
    },
})

watch(
    () => props.modelValue,
    (val) => {
        if (!editor.value) { return }
        if (editor.value.getHTML() === val) { return }
        editor.value.commands.setContent(val ?? '', false)
    },
)

onBeforeUnmount(() => {
    editor.value?.destroy()
})

function btn(active) {
    return [
        'rounded px-2.5 py-1.5 text-xs font-medium transition-colors',
        active
            ? 'bg-indigo-100 text-indigo-700'
            : 'text-gray-600 hover:bg-gray-200',
    ]
}
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-300">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-0.5 border-b border-gray-200 bg-gray-50 px-3 py-2">

            <button type="button" :class="btn(editor?.isActive('bold'))"
                @click="editor?.chain().focus().toggleBold().run()"
                title="Жирный">
                <strong>B</strong>
            </button>

            <button type="button" :class="btn(editor?.isActive('italic'))"
                @click="editor?.chain().focus().toggleItalic().run()"
                title="Курсив">
                <em>I</em>
            </button>

            <div class="mx-1.5 h-5 w-px bg-gray-300" />

            <button type="button" :class="btn(editor?.isActive('heading', { level: 2 }))"
                @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
                title="Заголовок 2">
                H2
            </button>

            <button type="button" :class="btn(editor?.isActive('heading', { level: 3 }))"
                @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
                title="Заголовок 3">
                H3
            </button>

            <div class="mx-1.5 h-5 w-px bg-gray-300" />

            <button type="button" :class="btn(editor?.isActive('bulletList'))"
                @click="editor?.chain().focus().toggleBulletList().run()"
                title="Маркированный список">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </button>

            <button type="button" :class="btn(editor?.isActive('orderedList'))"
                @click="editor?.chain().focus().toggleOrderedList().run()"
                title="Нумерованный список">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </button>

            <div class="mx-1.5 h-5 w-px bg-gray-300" />

            <button type="button" :class="btn(editor?.isActive('blockquote'))"
                @click="editor?.chain().focus().toggleBlockquote().run()"
                title="Цитата">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                </svg>
            </button>

            <button type="button" :class="btn(false)"
                @click="editor?.chain().focus().setHorizontalRule().run()"
                title="Разделитель">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5" />
                </svg>
            </button>

            <div class="mx-1.5 h-5 w-px bg-gray-300" />

            <button type="button" :class="btn(false)"
                :disabled="!editor?.can().undo()"
                @click="editor?.chain().focus().undo().run()"
                title="Отменить"
                class="disabled:opacity-40">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
            </button>

            <button type="button" :class="btn(false)"
                :disabled="!editor?.can().redo()"
                @click="editor?.chain().focus().redo().run()"
                title="Повторить"
                class="disabled:opacity-40">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3" />
                </svg>
            </button>
        </div>

        <!-- Content area -->
        <div class="bg-white px-5 py-4 text-gray-900">
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>

<style scoped>
:deep(.rte-content) {
    color: inherit;
    line-height: 1.65;
}
:deep(.rte-content p) {
    margin: 0.45rem 0;
}
:deep(.rte-content h2) {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 1rem 0 0.4rem;
}
:deep(.rte-content h3) {
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0.8rem 0 0.3rem;
}
:deep(.rte-content ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin: 0.4rem 0;
}
:deep(.rte-content ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin: 0.4rem 0;
}
:deep(.rte-content li) {
    margin: 0.2rem 0;
}
:deep(.rte-content blockquote) {
    border-left: 3px solid #6366f1;
    padding-left: 1rem;
    margin: 0.5rem 0;
    font-style: italic;
    opacity: 0.8;
}
:deep(.rte-content hr) {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 0.75rem 0;
}
:deep(.rte-content strong) {
    font-weight: 700;
}
:deep(.rte-content em) {
    font-style: italic;
}
/* Placeholder */
:deep(.rte-content p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>
