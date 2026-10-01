<script setup>
/**
 * Renders a monochrome icon from an SVG URL using a CSS mask painted with
 * `currentColor`. Works for both preset (public/icons/services) and uploaded
 * custom icons, themes automatically via the parent's text color, and never
 * executes the SVG (mask-image can't run scripts) — so custom uploads are safe.
 * Raster images (category "image" icons, WebP) are drawn as-is with <img>,
 * since a mask would flatten them into a solid shape.
 * Size is controlled by the parent via width/height utility classes.
 */
import { computed } from 'vue'

const props = defineProps({
    url: { type: String, required: true },
    // Forces the mask mode for URLs without a .svg extension (e.g. blob: previews).
    svg: { type: Boolean, default: null },
})

const isSvg = computed(() => props.svg ?? /\.svg(\?|#|$)/i.test(props.url))
</script>

<template>
    <span
        v-if="isSvg"
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
    <img v-else :src="url" alt="" class="inline-block flex-shrink-0 object-contain" aria-hidden="true" />
</template>
