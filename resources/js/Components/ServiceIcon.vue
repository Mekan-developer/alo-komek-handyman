<script setup>
/**
 * Renders a monochrome icon from an SVG URL using a CSS mask painted with
 * `currentColor`. Works for both preset (public/icons/services) and uploaded
 * custom icons, themes automatically via the parent's text color, and never
 * executes the SVG (mask-image can't run scripts) — so custom uploads are safe.
 * Size is controlled by the parent via width/height utility classes.
 */
const props = defineProps({
    url: { type: String, required: true },
})
</script>

<template>
    <span
        class="inline-block flex-shrink-0 bg-current"
        :style="{
            maskImage: `url('${url}')`,
            WebkitMaskImage: `url('${url}')`,
            maskRepeat: 'no-repeat',
            WebkitMaskRepeat: 'no-repeat',
            maskPosition: 'center',
            WebkitMaskPosition: 'center',
            maskSize: 'contain',
            WebkitMaskSize: 'contain',
        }"
        aria-hidden="true"
    />
</template>
