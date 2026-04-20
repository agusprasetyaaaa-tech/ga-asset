<script setup>
/**
 * BarcodeDisplay Component
 * Renders a barcode SVG on-the-fly from the backend endpoint.
 * Falls back to displaying the barcode code as text.
 */
const props = defineProps({
    assetId: {
        type: [Number, String],
        required: true,
    },
    code: {
        type: String,
        required: true,
    },
    showCode: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="flex flex-col items-center gap-1">
        <img
            :src="route('assets.barcode', assetId)"
            :alt="`Barcode: ${code}`"
            class="h-16 w-auto"
        />
        <span
            v-if="showCode"
            class="font-mono text-xs text-gray-500 tracking-wider"
        >
            {{ code }}
        </span>
    </div>
</template>
