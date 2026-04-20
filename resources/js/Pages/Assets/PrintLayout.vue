<script setup>
/**
 * A4 Print Layout for Asset Barcodes - Professional Version
 * Optimized for standard A4 paper size with premium styling.
 */
import { onMounted } from 'vue';

const props = defineProps({
    assets: Array,
});

onMounted(() => {
    // We provide a short delay to ensure QR codes are fully rendered
    console.log('Print preview ready for ' + props.assets.length + ' assets');
});

const exportToPDF = () => {
    const element = document.querySelector('.labels-grid');
    if (!window.html2pdf) {
        alert('Sedang menyiapkan sistem... Silakan coba sesaat lagi.');
        return;
    }
    const opt = {
        margin:       [10, 10],
        filename:     'katalog-barcode-aset.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    window.html2pdf().set(opt).from(element).save();
};

const triggerPrint = () => {
    window.print();
};

const closePreview = () => {
    window.close();
};
</script>

<template>
    <div class="print-container">
        <!-- Labels Grid -->
        <div class="labels-grid py-2">
            <div v-for="asset in assets" :key="asset.id" class="barcode-label">
                <div class="label-header">
                    <span class="app-name">Asset Management System</span>
                </div>
                
                <div class="label-body">
                    <div class="qr-code">
                        <img :src="route('assets.qr.standard', asset.id)" alt="QR" />
                    </div>
                    <div class="asset-info">
                        <div class="asset-name">{{ asset.name }}</div>
                        <div class="sn" v-if="asset.serial_number">SN: {{ asset.serial_number }}</div>
                        <div class="asset-code">{{ asset.asset_code || asset.barcode_code }}</div>
                    </div>
                </div>
                
                <div class="label-footer">
                    <span class="footer-text uppercase">Aset Milik {{ asset.asset_owner || 'PT. Interprima Indocom' }}</span>
                </div>
            </div>
        </div>

        <!-- UI Controls (Hidden on Print) -->
        <div class="print-controls no-print">
            <div class="mx-auto max-w-lg bg-white/90 backdrop-blur-md border border-gray-100 rounded-3xl shadow-2xl p-6 flex items-center justify-between gap-6">
                <div class="min-w-0">
                    <h3 class="text-sm font-black text-gray-900 leading-none">Pratinjau Kertas A4</h3>
                    <p class="text-[10px] text-gray-400 font-bold mt-1.5 leading-tight">Gunakan printer fisik atau simpan sebagai PDF.</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="exportToPDF" 
                        class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-[11px] font-black rounded-xl hover:bg-gray-50 transition-all flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        PDF
                    </button>
                    <button @click="triggerPrint" 
                        class="px-5 py-2.5 bg-emerald-600 text-white text-[11px] font-black rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all">
                        Cetak Sekarang
                    </button>
                    <button @click="closePreview" 
                        class="p-2.5 bg-gray-50 text-gray-400 rounded-xl hover:text-rose-500 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Script to load html2pdf -->
        <component :is="'script'" src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></component>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

/* A4 Page Configuration */
@page {
    size: A4;
    margin: 10mm;
}

:root {
    --primary: #10b981;
}

body {
    background: #f8fafc;
    margin: 0;
    padding: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.print-container {
    width: 100%;
    max-width: 190mm;
    margin: 0 auto;
    background: white;
    min-height: 277mm;
}

.labels-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4mm;
    width: 100%;
    padding: 2mm;
    box-sizing: border-box;
}

.barcode-label {
    border: 1.5px solid #000000;
    border-radius: 8px;
    padding: 2.5mm;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 32mm;
    overflow: hidden;
    break-inside: avoid;
}

.label-header {
    border-bottom: 0.8px solid #000000;
    padding-bottom: 1.5mm;
    display: flex;
    justify-content: center;
}

.app-name {
    font-size: 6.5px;
    font-weight: 900;
    color: #000000;
    letter-spacing: 0.5px;
}

.label-body {
    display: flex;
    align-items: center;
    gap: 3mm;
    flex: 1;
    padding: 1.5mm 0;
}

.qr-code img {
    width: 16mm;
    height: 16mm;
    display: block;
}

.asset-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1.5px;
    min-width: 0;
}

.asset-name {
    font-size: 7.5px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.sn {
    font-size: 7px;
    font-weight: 700;
    color: #000000;
}

.asset-code {
    font-size: 7.5px;
    font-weight: 800;
    color: #059669;
}

.label-footer {
    border-top: 0.8px solid #000000;
    padding-top: 1.5mm;
    display: flex;
    justify-content: center;
}

.footer-text {
    font-size: 6px;
    font-weight: 800;
    color: #000000;
    text-align: center;
}

/* Hide UI on Print */
@media print {
    body { background: white !important; font-family: sans-serif; }
    .no-print { display: none !important; }
    .print-container { margin: 0; padding: 0; width: 100%; max-width: 100%; border: none; box-shadow: none; }
    .barcode-label { border-color: #000; }
}

.print-controls {
    position: fixed;
    bottom: 32px;
    left: 0;
    right: 0;
    padding: 0 16px;
    z-index: 100;
}
</style>
