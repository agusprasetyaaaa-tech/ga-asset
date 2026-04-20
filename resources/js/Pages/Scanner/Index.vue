<script setup>
/**
 * Scanner Page - Standardized UI
 * Barcode scanning via device camera using html5-qrcode.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const scanning = ref(false);
const scanResult = ref(null);
const scanError = ref('');
const manualCode = ref('');
const loading = ref(false);

let html5QrCode = null;

const startScanner = async () => {
    scanError.value = '';
    scanResult.value = null;

    try {
        const { Html5Qrcode } = await import('html5-qrcode');

        html5QrCode = new Html5Qrcode('scanner-reader');
        scanning.value = true;

        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 300, height: 150 },
            },
            onScanSuccess,
            () => {} 
        );
    } catch (err) {
        scanError.value = 'Tidak dapat mengakses kamera. Pastikan Anda memberikan izin akses kamera.';
        scanning.value = false;
    }
};

const stopScanner = async () => {
    if (html5QrCode) {
        try {
            await html5QrCode.stop();
        } catch (e) {
            // ignore
        }
    }
    scanning.value = false;
};

const onScanSuccess = async (decodedText) => {
    await stopScanner();
    await lookupCode(decodedText);
};

const lookupCode = async (code) => {
    if (!code.trim()) return;
    loading.value = true;
    scanError.value = '';
    scanResult.value = null;

    try {
        const response = await axios.post(route('scanner.lookup'), { code: code.trim() });
        scanResult.value = response.data;
    } catch (err) {
        if (err.response?.status === 404) {
            scanError.value = `Asset tidak ditemukan untuk kode: "${code}"`;
        } else {
            scanError.value = 'Terjadi kesalahan saat mencari asset.';
        }
    } finally {
        loading.value = false;
    }
};

const submitManual = () => {
    lookupCode(manualCode.value);
};

const resetScanner = () => {
    scanResult.value = null;
    scanError.value = '';
    manualCode.value = '';
};

const formatCurrency = (val) => {
    if (!val) return '-';
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR', 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(val);
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <Head title="Scan Barcode" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Scanner Barcode</h2>
                <p class="text-xs text-gray-400 font-medium">Scan QR Code atau Barcode Aset untuk Melihat Detail</p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Scanner Card -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Scan via Kamera</h3>
                        </div>

                        <!-- Scanner Area -->
                        <div id="scanner-reader" class="w-full rounded-2xl overflow-hidden bg-gray-900 shadow-inner"
                            :class="{ 'min-h-[300px]': scanning }"></div>

                        <div class="mt-6 flex gap-3">
                            <button v-if="!scanning" @click="startScanner"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition-all active:scale-95 uppercase tracking-wider">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Mulai Scan
                            </button>
                            <button v-else @click="stopScanner"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-rose-500 px-6 py-3 text-xs font-bold text-white hover:bg-rose-600 shadow-sm transition-all active:scale-95 uppercase tracking-wider">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                                Berhenti
                            </button>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="flex items-center px-8">
                        <div class="flex-1 border-t border-gray-100"></div>
                        <span class="mx-4 text-[10px] font-bold text-gray-300 uppercase tracking-widest italic">atau</span>
                        <div class="flex-1 border-t border-gray-100"></div>
                    </div>

                    <!-- Manual Input -->
                    <div class="p-8 pt-6">
                         <div class="flex items-center gap-3 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center">
                                <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Input Manual</h3>
                        </div>
                        <form @submit.prevent="submitManual" class="flex gap-3">
                            <input v-model="manualCode" type="text" required
                                class="flex-1 rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all text-sm"
                                placeholder="Kode barcode atau SN..." />
                            <button type="submit" :disabled="loading"
                                class="rounded-lg bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-all active:scale-95 uppercase tracking-wider">
                                {{ loading ? '...' : 'Cari' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Error -->
                <div v-if="scanError" class="rounded-xl bg-rose-50 border border-rose-100 p-5 text-xs text-rose-700 shadow-sm transition-all animate-bounce-short">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        <span class="font-bold">{{ scanError }}</span>
                    </div>
                    <button @click="resetScanner" class="mt-3 text-[10px] font-black uppercase tracking-widest text-rose-600 hover:text-rose-800 bg-white px-3 py-1.5 rounded-lg border border-rose-100 transition-all">
                        Coba Lagi
                    </button>
                </div>

                <!-- Result Card -->
                <div v-if="scanResult?.found" class="rounded-xl bg-white shadow-xl shadow-emerald-500/5 border border-emerald-100 overflow-hidden transition-all animate-fade-in-up">
                    <div class="bg-emerald-600 px-6 py-4">
                        <p class="text-xs font-bold text-white flex items-center gap-2 uppercase tracking-widest">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            Asset Ditemukan
                        </p>
                    </div>
                    <div class="p-8">
                        <div class="flex items-start justify-between mb-8 pb-6 border-b border-gray-50">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 truncate leading-tight mb-2">{{ scanResult.asset.name }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-1 rounded font-bold uppercase tracking-tight">{{ scanResult.asset.category || '-' }}</span>
                                    <span class="text-[10px] bg-emerald-50 text-emerald-600 px-2 py-1 rounded font-bold uppercase tracking-tight font-mono">{{ scanResult.asset.serial_number || '-' }}</span>
                                </div>
                            </div>
                            <StatusBadge :status="scanResult.asset.status" />
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-xs mb-8">
                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Kode Asset</p>
                                <p class="font-mono font-bold text-gray-900 truncate tracking-tighter text-sm">{{ scanResult.asset.barcode_code }}</p>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Lokasi</p>
                                <p class="font-bold text-gray-900 truncate text-sm">{{ scanResult.asset.location?.name || '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Pemegang</p>
                                <p class="font-bold text-gray-900 truncate text-sm">{{ scanResult.asset.current_holder || '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Harga Beli</p>
                                <p class="font-bold text-gray-900 text-sm tabular-nums">{{ formatCurrency(scanResult.asset.purchase_price) }}</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <Link :href="route('assets.show', scanResult.asset.id)"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-4 text-xs font-bold text-white hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all active:scale-95 uppercase tracking-widest">
                                Buka Detail
                            </Link>
                            <button @click="resetScanner"
                                class="inline-flex items-center justify-center px-6 py-4 rounded-xl border border-gray-200 text-xs font-bold text-gray-400 hover:bg-gray-50 transition-all active:scale-95 uppercase tracking-widest">
                                Kembali
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="rounded-xl bg-white shadow-sm border border-gray-100 p-12 text-center">
                    <div class="inline-flex h-12 w-12 animate-spin rounded-full border-4 border-gray-100 border-t-emerald-600 mb-4"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mencari Data Aset...</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
