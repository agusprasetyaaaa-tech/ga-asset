<script setup>
/**
 * Scanner Page - Professional Responsive UI
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

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};

const formatDateFull = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
};

const conditionLabel = (c) => {
    const map = { baik: 'Baik', cukup_baik: 'Cukup Baik', kurang_baik: 'Kurang Baik', rusak: 'Rusak' };
    return map[c] || c || '-';
};

const conditionColor = (c) => {
    const map = {
        baik: 'text-emerald-700 bg-emerald-50 border-emerald-200',
        cukup_baik: 'text-blue-700 bg-blue-50 border-blue-200',
        kurang_baik: 'text-amber-700 bg-amber-50 border-amber-200',
        rusak: 'text-red-700 bg-red-50 border-red-200',
    };
    return map[c] || 'text-gray-700 bg-gray-50 border-gray-200';
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
                <p class="text-xs text-gray-500 mt-1">Scan QR Code atau Barcode Aset untuk Melihat Detail</p>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Main Grid: side-by-side on lg, stacked on mobile -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                    <!-- Left Column: Scanner + Manual Input -->
                    <div class="lg:col-span-3 space-y-5">

                        <!-- Camera Scanner Card -->
                        <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 sm:px-6 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-800">Scan via Kamera</h3>
                                        <p class="text-[10px] text-gray-400 font-medium hidden sm:block">Arahkan kamera ke QR Code / Barcode aset</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 sm:p-6">
                                <!-- Scanner Area -->
                                <div id="scanner-reader" class="w-full rounded-xl overflow-hidden bg-gray-900 shadow-inner"
                                    :class="{ 'min-h-[240px] sm:min-h-[300px]': scanning }"></div>

                                <div class="mt-4 flex gap-3">
                                    <button v-if="!scanning" @click="startScanner"
                                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition-all duration-200 active:scale-[0.97]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        Mulai Scan
                                    </button>
                                    <button v-else @click="stopScanner"
                                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-rose-500 px-5 py-3 text-xs font-bold text-white hover:bg-rose-600 shadow-sm transition-all duration-200 active:scale-[0.97]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                                        Berhenti
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Input Card -->
                        <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 sm:px-6 border-b border-gray-50 bg-gradient-to-r from-blue-50/40 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-800">Input Manual</h3>
                                        <p class="text-[10px] text-gray-400 font-medium hidden sm:block">Ketik kode barcode atau serial number secara manual</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5 sm:p-6">
                                <form @submit.prevent="submitManual" class="flex flex-col sm:flex-row gap-3">
                                    <input v-model="manualCode" type="text" required
                                        class="flex-1 rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all text-sm"
                                        placeholder="Kode barcode atau SN..." />
                                    <button type="submit" :disabled="loading"
                                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-all duration-200 active:scale-[0.97] whitespace-nowrap">
                                        {{ loading ? 'Mencari...' : 'Cari Asset' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Tips / Instructions -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Quick Guide Card -->
                        <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 sm:px-6 border-b border-gray-50">
                                <h3 class="text-sm font-bold text-gray-800">Panduan Cepat</h3>
                            </div>
                            <div class="p-5 sm:p-6 space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="h-7 w-7 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-emerald-600">1</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Aktifkan Kamera</p>
                                        <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">Klik "Mulai Scan" dan izinkan akses kamera pada browser Anda.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="h-7 w-7 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-emerald-600">2</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Arahkan ke Barcode</p>
                                        <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">Posisikan QR Code atau barcode aset di area kotak scan.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="h-7 w-7 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-emerald-600">3</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Lihat Detail Aset</p>
                                        <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">Data aset akan tampil otomatis setelah barcode berhasil terbaca.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tips Card -->
                        <div class="rounded-xl bg-gradient-to-br from-amber-50 to-orange-50/50 shadow-sm border border-amber-100/60 overflow-hidden">
                            <div class="p-5 sm:p-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                                    <h4 class="text-xs font-bold text-amber-700 uppercase tracking-wider">Tips</h4>
                                </div>
                                <ul class="space-y-2 text-xs text-amber-700/80 leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-amber-400 mt-0.5">•</span>
                                        Pastikan pencahayaan cukup untuk hasil scan optimal
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-amber-400 mt-0.5">•</span>
                                        Gunakan input manual jika kamera tidak tersedia
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-amber-400 mt-0.5">•</span>
                                        Support barcode 1D (Code 128) dan 2D (QR Code)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Alert (full width below) -->
                <div v-if="scanError" class="mt-6 rounded-xl bg-rose-50 border border-rose-100 p-5 shadow-sm transition-all">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-rose-700">{{ scanError }}</p>
                            <button @click="resetScanner" class="mt-3 text-xs font-bold text-rose-600 hover:text-rose-800 bg-white px-4 py-2 rounded-lg border border-rose-100 transition-all duration-200 active:scale-[0.97]">
                                Coba Lagi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="mt-6 rounded-xl bg-white shadow-sm border border-gray-100 p-10 sm:p-12 text-center">
                    <div class="inline-flex h-10 w-10 animate-spin rounded-full border-4 border-gray-100 border-t-emerald-600 mb-4"></div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Mencari Data Aset...</p>
                </div>

                <!-- Result Card (full width below) -->
                <div v-if="scanResult?.found" class="mt-6 space-y-5">

                    <!-- Header Banner -->
                    <div class="rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 py-4 sm:px-6 shadow-lg shadow-emerald-500/10">
                        <p class="text-sm font-bold text-white flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            Asset Ditemukan
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                        <!-- Main Info Card (Left 2 cols) -->
                        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <!-- Asset Header with Photo -->
                            <div class="p-5 sm:p-6">
                                <div class="flex items-start gap-4">
                                    <!-- Photo or Initial -->
                                    <div v-if="scanResult.asset.photo" class="h-16 w-16 sm:h-20 sm:w-20 rounded-xl border border-gray-200 overflow-hidden shrink-0">
                                        <img :src="`/storage/${scanResult.asset.photo}`" :alt="scanResult.asset.name" class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else class="h-16 w-16 sm:h-20 sm:w-20 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 flex items-center justify-center text-2xl sm:text-3xl font-bold text-emerald-600 shrink-0">
                                        {{ scanResult.asset.name?.charAt(0) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                            <StatusBadge :status="scanResult.asset.status" />
                                            <span v-if="scanResult.asset.condition"
                                                class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full border"
                                                :class="conditionColor(scanResult.asset.condition)">
                                                {{ conditionLabel(scanResult.asset.condition) }}
                                            </span>
                                        </div>
                                        <h3 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight mb-1">{{ scanResult.asset.name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            <span v-if="scanResult.asset.brand" class="font-semibold">{{ scanResult.asset.brand }}</span>
                                            <span v-if="scanResult.asset.brand && scanResult.asset.model_type"> — </span>
                                            <span v-if="scanResult.asset.model_type">{{ scanResult.asset.model_type }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Grid -->
                            <div class="border-t border-gray-100 px-5 sm:px-6 py-5">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kode Asset</span>
                                        <span class="text-sm font-bold text-emerald-700 font-mono truncate">{{ scanResult.asset.asset_code || scanResult.asset.barcode_code }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Serial Number</span>
                                        <span class="text-xs font-bold text-gray-900 font-mono italic truncate">{{ scanResult.asset.serial_number || '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kategori</span>
                                        <span class="text-sm font-semibold text-gray-900 truncate">
                                            {{ scanResult.asset.subcategory?.category?.name || '-' }}
                                            <span v-if="scanResult.asset.subcategory" class="text-gray-400"> / </span>
                                            {{ scanResult.asset.subcategory?.name || '' }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Lokasi</span>
                                        <span class="text-sm font-bold text-gray-900 truncate">{{ scanResult.asset.location?.name || '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Departemen</span>
                                        <span class="text-sm font-semibold text-gray-900 truncate">{{ scanResult.asset.department_rel?.name || scanResult.asset.department || '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pengguna / PIC</span>
                                        <span class="text-sm font-semibold text-gray-900 truncate">{{ scanResult.asset.current_holder || '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Harga Pembelian</span>
                                        <span class="text-sm font-bold text-gray-900 tabular-nums">{{ formatCurrency(scanResult.asset.purchase_price) }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Pembelian</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ scanResult.asset.purchase_date ? formatDateFull(scanResult.asset.purchase_date) : '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pemilik Aset</span>
                                        <span class="text-sm font-bold text-emerald-600 truncate">{{ scanResult.asset.asset_owner || 'PT. Interprima Indocom' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Specification -->
                            <div v-if="scanResult.asset.specification" class="border-t border-gray-100 px-5 sm:px-6 py-4">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Spesifikasi</span>
                                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-100 whitespace-pre-line leading-relaxed">{{ scanResult.asset.specification }}</div>
                            </div>

                            <!-- Procurement Info -->
                            <div v-if="scanResult.asset.vendor || scanResult.asset.vendor_rel || scanResult.asset.warranty_date" class="border-t border-gray-100 px-5 sm:px-6 py-4">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-3">Informasi Procurement</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Vendor / Supplier</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ scanResult.asset.vendor_rel?.name || scanResult.asset.vendor || '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Garansi Berakhir</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ scanResult.asset.warranty_date ? formatDateFull(scanResult.asset.warranty_date) : '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Masa Pemakaian</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ scanResult.asset.usage_period || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="border-t border-gray-100 px-5 sm:px-6 py-4">
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <Link :href="route('assets.show', scanResult.asset.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-6 py-3 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition-all duration-200 active:scale-[0.97]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Buka Detail Lengkap
                                    </Link>
                                    <button @click="resetScanner"
                                        class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-gray-200 text-xs font-bold text-gray-500 hover:bg-gray-50 transition-all duration-200 active:scale-[0.97]">
                                        Scan Lagi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Sidebar -->
                        <div class="space-y-5">
                            <!-- Asset Age Card -->
                            <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-xl p-5 text-white shadow-sm">
                                <span class="text-[10px] font-bold uppercase tracking-widest opacity-70">Usia Asset</span>
                                <div class="mt-2 flex items-baseline gap-2">
                                    <span class="text-3xl font-bold">
                                        {{ Math.floor((new Date() - new Date(scanResult.asset.created_at)) / (1000 * 60 * 60 * 24)) }}
                                    </span>
                                    <span class="text-xs font-medium opacity-80">Hari tercatat</span>
                                </div>
                                <p class="text-[10px] mt-3 opacity-60 leading-relaxed">Terdaftar sejak {{ formatDate(scanResult.asset.created_at) }}</p>
                            </div>

                            <!-- Recent Movements -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/50">
                                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Perpindahan Terakhir</h4>
                                </div>
                                <div class="divide-y divide-gray-50">
                                    <div v-for="m in (scanResult.asset.movements || []).slice(0, 3)" :key="m.id" class="px-5 py-3">
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <span class="font-bold text-gray-500 truncate">{{ m.from_location?.name || 'Awal' }}</span>
                                            <svg class="h-3 w-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            <span class="font-bold text-gray-900 truncate">{{ m.to_location?.name || '-' }}</span>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ formatDate(m.created_at) }}</p>
                                    </div>
                                    <div v-if="!scanResult.asset.movements?.length" class="px-5 py-4 text-center text-xs text-gray-400">
                                        Belum ada perpindahan
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/50">
                                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Maintenance Terakhir</h4>
                                </div>
                                <div class="divide-y divide-gray-50">
                                    <div v-for="log in (scanResult.asset.maintenance_logs || []).slice(0, 3)" :key="log.id" class="px-5 py-3">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-bold text-emerald-600 uppercase">{{ log.type }}</span>
                                            <span class="text-[9px] font-bold" :class="log.completed_at ? 'text-emerald-600' : 'text-amber-600'">
                                                {{ log.completed_at ? 'SELESAI' : 'PENDING' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 truncate">{{ log.description }}</p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ formatDate(log.created_at) }}</p>
                                    </div>
                                    <div v-if="!scanResult.asset.maintenance_logs?.length" class="px-5 py-4 text-center text-xs text-gray-400">
                                        Belum ada log maintenance
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
