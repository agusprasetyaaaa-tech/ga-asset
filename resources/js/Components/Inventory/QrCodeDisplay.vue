<script setup>
/**
 * QrCodeDisplay Component - Clean & Professional
 * Shows QR codes based on the asset_code (barcode identifier).
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
});
</script>

<template>
    <div class="grid grid-cols-1 gap-6 w-full">
        <!-- 2D Standard QR -->
        <div class="flex flex-col items-center p-6 bg-gray-50 rounded-xl border border-gray-200">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Standard QR 2D</span>
            <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                <img
                    :src="route('assets.qr.standard', assetId)"
                    :alt="`QR Standard: ${code}`"
                    class="w-32 h-32 md:w-36 md:h-36 object-contain"
                />
            </div>
            <span class="mt-4 font-mono text-xs font-bold text-gray-900">
                {{ code }}
            </span>
            <span class="text-[9px] text-gray-400 mt-0.5">Kode Asset / Barcode ID</span>
        </div>

        <!-- Stylized QR -->
        <div class="flex flex-col items-center p-6 bg-gray-50 rounded-xl border border-gray-200">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-4">Stylized QR</span>
            <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
                <img
                    :src="route('assets.qr.stylized', assetId)"
                    :alt="`QR Stylized: ${code}`"
                    class="w-32 h-32 md:w-36 md:h-36 object-contain"
                />
            </div>
            <a :href="route('assets.qr.stylized', assetId)" target="_blank" 
                class="mt-4 text-[10px] font-bold text-emerald-600 hover:underline">
                Download SVG File
            </a>
        </div>
    </div>
</template>
