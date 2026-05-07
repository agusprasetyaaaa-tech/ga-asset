<script setup>
/**
 * Asset Show Page - Extended Detail View
 * Displays all asset information including new fields, procurement details and photo.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import QrCodeDisplay from '@/Components/Inventory/QrCodeDisplay.vue';
import AssetFormModal from '@/Components/Inventory/AssetFormModal.vue';
import DepreciationChart from '@/Components/Inventory/DepreciationChart.vue';
import LoanFormModal from '@/Components/Inventory/LoanFormModal.vue';
import LoanReturnModal from '@/Components/Inventory/LoanReturnModal.vue';
import MovementFormModal from '@/Components/Inventory/MovementFormModal.vue';
import MaintenanceFormModal from '@/Components/Inventory/MaintenanceFormModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    asset: Object,
    locations: Array,
    categories: Array,
    subcategories: Array,
    departments: Array,
    vendors: Array,
});

const showEditModal = ref(false);
const showMovementModal = ref(false);
const showMaintenanceModal = ref(false);
const showLoanModal = ref(false);
const showReturnModal = ref(false);

const activeTab = ref('movements');

const updateStatus = (status) => {
    if (confirm(`Ubah status aset menjadi "${status}"?`)) {
        router.patch(route('assets.status', props.asset.id), { status }, {
            preserveScroll: true,
            onSuccess: () => {
                // Force a full reload of the current page data
                router.get(window.location.href, {}, { preserveState: false, preserveScroll: true });
            }
        });
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const formatDateShort = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
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
</script>

<template>
    <Head :title="asset.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('assets.index')"
                        class="h-9 w-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Detail Aset</h2>
                        <p class="text-xs text-gray-500 mt-1 font-mono">{{ asset.asset_code || asset.barcode_code }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Main Content -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- Active Loan Info Alert -->
                        <div v-if="asset.status === 'borrowed' && asset.loans?.length > 0 && asset.loans[0].status === 'borrowed'" 
                            class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-xl">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-bold text-blue-800 uppercase tracking-tight">Sedang Dipinjam</h3>
                                    <div class="mt-1 text-xs text-blue-700">
                                        Oleh <span class="font-bold underline">{{ asset.loans[0].borrower_name }}</span> 
                                        ({{ asset.loans[0].borrower_department || '-' }}) 
                                        sejak {{ formatDateShort(asset.loans[0].loan_date) }}.
                                        <span v-if="asset.loans[0].expected_return_date" class="block mt-1 font-semibold">
                                            Estimasi Kembali: {{ formatDateShort(asset.loans[0].expected_return_date) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Asset Info Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                                <div class="flex items-start gap-4">
                                    <!-- Photo or Initial -->
                                    <div v-if="asset.photo" class="h-16 w-16 rounded-xl border border-gray-200 overflow-hidden flex-shrink-0">
                                        <img :src="`/storage/${asset.photo}`" :alt="asset.name" class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else class="h-16 w-16 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center text-2xl font-bold text-gray-400 flex-shrink-0">
                                        {{ asset.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <StatusBadge :status="asset.status" />
                                            <span v-if="asset.condition" 
                                                class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full border"
                                                :class="conditionColor(asset.condition)">
                                                {{ conditionLabel(asset.condition) }}
                                            </span>
                                        </div>
                                        <h3 class="text-2xl font-bold text-gray-900">{{ asset.name }}</h3>
                                        <p class="text-sm text-gray-500 mt-0.5">
                                            <span v-if="asset.brand" class="font-semibold">{{ asset.brand }}</span>
                                            <span v-if="asset.brand && asset.model_type"> — </span>
                                            <span v-if="asset.model_type">{{ asset.model_type }}</span>
                                            <span v-if="!asset.brand && !asset.model_type" class="text-gray-400">Tidak ada info brand/model</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <button v-if="asset.status === 'available'" @click="showLoanModal = true"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition-colors shadow-sm">
                                        Pinjam Aset
                                    </button>
                                    <button v-if="asset.status === 'borrowed'" @click="showReturnModal = true"
                                        class="px-4 py-2 bg-rose-600 text-white rounded-lg text-xs font-bold hover:bg-rose-700 transition-colors shadow-sm">
                                        Kembalikan Aset
                                    </button>
                                    <button @click="showEditModal = true"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                                        Edit
                                    </button>
                                    <button @click="showMovementModal = true"
                                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition-colors shadow-sm">
                                        Pindah Lokasi
                                    </button>
                                </div>
                            </div>

                            <!-- Detail Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4 border-t border-gray-100 pt-6">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kode Asset</span>
                                    <span class="text-sm font-bold text-emerald-700 font-mono">{{ asset.asset_code || asset.barcode_code }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Serial Number</span>
                                    <span class="text-[11px] font-bold text-gray-900 font-mono italic">{{ asset.serial_number || '-' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Kategori</span>
                                    <span class="text-sm font-semibold text-gray-900">
                                        {{ asset.subcategory?.category?.name || '-' }}
                                        <span v-if="asset.subcategory" class="text-gray-400"> / </span>
                                        {{ asset.subcategory?.name || '' }}
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Lokasi</span>
                                    <span class="text-sm font-bold text-gray-900">{{ asset.location?.name || '-' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Departemen</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ asset.department_rel?.name || asset.department || '-' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pengguna / PIC</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ asset.current_holder || '-' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Harga Pembelian</span>
                                    <span class="text-sm font-bold text-gray-900">{{ formatCurrency(asset.purchase_price) }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal Pembelian</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ asset.purchase_date ? formatDateShort(asset.purchase_date) : '-' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pemilik Aset</span>
                                    <span class="text-sm font-bold text-emerald-600">{{ asset.asset_owner || 'PT. Interprima Indocom' }}</span>
                                </div>
                            </div>

                            <!-- Specification -->
                            <div v-if="asset.specification" class="mt-6 pt-4 border-t border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Spesifikasi</span>
                                <div class="p-4 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-100 whitespace-pre-line">{{ asset.specification }}</div>
                            </div>

                            <!-- Description -->
                            <div v-if="asset.description" class="mt-4">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Keterangan</span>
                                <div class="p-4 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-100 whitespace-pre-line">{{ asset.description }}</div>
                            </div>

                            <!-- Depreciation Chart -->
                            <div v-if="asset.useful_life > 0" class="mt-8 pt-6 border-t border-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Visualisasi Penyusutan (Straight-Line)</span>
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">Proyeksi {{ asset.useful_life }} Tahun</span>
                                </div>
                                <div class="bg-white p-2 rounded-xl border border-gray-100">
                                    <DepreciationChart 
                                        :purchase-price="asset.purchase_price"
                                        :salvage-value="asset.salvage_value"
                                        :useful-life="asset.useful_life"
                                        :purchase-date="asset.purchase_date"
                                        :current-book-value="asset.current_book_value"
                                    />
                                </div>
                                <p class="text-[9px] text-gray-400 mt-3 text-center italic">
                                    Grafik menunjukkan penurunan nilai aset dari harga beli ke nilai residu selama umur ekonomis.
                                </p>
                            </div>

                            <!-- Procurement Info -->
                            <div v-if="asset.vendor || asset.warranty_date || asset.notes" class="mt-6 pt-4 border-t border-gray-100">
                                <span class="text-xs font-bold text-gray-800 uppercase tracking-widest block mb-4">Informasi Pembelian (Procurement)</span>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Vendor / Supplier</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ asset.vendor_rel?.name || asset.vendor || '-' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Garansi Berakhir</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ asset.warranty_date ? formatDateShort(asset.warranty_date) : '-' }}</span>
                                    </div>
                                </div>
                                <div v-if="asset.notes" class="mt-4">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Catatan Tambahan</span>
                                    <div class="p-4 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-100 whitespace-pre-line">{{ asset.notes }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Logs Section -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="flex border-b border-gray-100 bg-gray-50/50">
                                <button @click="activeTab = 'movements'"
                                    :class="activeTab === 'movements' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-gray-400 hover:text-gray-600 font-medium'"
                                    class="px-6 py-4 text-sm border-b-2 transition-all">
                                    Riwayat Perpindahan
                                </button>
                                <button @click="activeTab = 'loans'"
                                    :class="activeTab === 'loans' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-gray-400 hover:text-gray-600 font-medium'"
                                    class="px-6 py-4 text-sm border-b-2 transition-all">
                                    Riwayat Pinjam
                                </button>
                                <button @click="activeTab = 'maintenance'"
                                    :class="activeTab === 'maintenance' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-gray-400 hover:text-gray-600 font-medium'"
                                    class="px-6 py-4 text-sm border-b-2 transition-all">
                                    Log Maintenance
                                </button>
                            </div>

                            <div class="p-6">
                                <!-- Movements -->
                                <div v-if="activeTab === 'movements'">
                                    <div v-if="asset.movements?.length" class="space-y-6 pl-4 border-l border-gray-100 ml-2">
        <div v-for="movement in asset.movements" :key="movement.id" class="relative">
            <div class="absolute -left-[21px] top-1.5 h-3 w-3 rounded-full bg-white border-2 border-emerald-500"></div>
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-2 mb-1">
                <div class="flex flex-col gap-0.5">
                    <!-- Location Movement -->
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-bold text-gray-500">{{ movement.from_location?.name || 'Start' }}</span>
                        <svg class="h-3 w-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        <span class="font-bold text-gray-900">{{ movement.to_location?.name || movement.user?.name || 'N/A' }}</span>
                    </div>
                    <!-- Holder Handoff -->
                    <div v-if="movement.from_holder || movement.to_holder" class="flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-bold">
                        <span class="text-blue-600">{{ movement.from_holder || '-' }}</span>
                        <svg class="h-2 w-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        <span class="text-emerald-600">{{ movement.to_holder || '-' }}</span>
                    </div>
                </div>
                <span class="text-[10px] font-bold text-gray-400 whitespace-nowrap">{{ formatDate(movement.created_at) }}</span>
            </div>
            <p v-if="movement.notes" class="text-xs text-gray-500 italic mt-1 pb-4">"{{ movement.notes }}"</p>
        </div>
                                    </div>
                                    <p v-else class="text-sm text-gray-400 text-center py-4">Belum ada riwayat perpindahan.</p>
                                </div>

                                <!-- Loans -->
                                <div v-if="activeTab === 'loans'">
                                    <div v-if="asset.loans?.length" class="space-y-4">
                                        <div v-for="loan in asset.loans" :key="loan.id" class="p-4 bg-gray-50 border border-gray-100 rounded-lg">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-900">{{ loan.borrower_name }}</span>
                                                    <span class="text-[9px] text-gray-500 uppercase">{{ loan.borrower_department || '-' }}</span>
                                                </div>
                                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border"
                                                    :class="loan.status === 'returned' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-blue-600 bg-blue-50 border-blue-100'">
                                                    {{ loan.status === 'returned' ? 'SUDAH KEMBALI' : 'DIPINJAM' }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-gray-200/50">
                                                <div>
                                                    <span class="text-[8px] font-bold text-gray-400 uppercase block">Dipinjam</span>
                                                    <span class="text-[10px] text-gray-700 font-semibold">{{ formatDateShort(loan.loan_date) }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-[8px] font-bold text-gray-400 uppercase block">Kembali</span>
                                                    <span class="text-[10px] text-gray-700 font-semibold">{{ loan.actual_return_date ? formatDateShort(loan.actual_return_date) : '-' }}</span>
                                                </div>
                                            </div>
                                            <p v-if="loan.notes && loan.status === 'borrowed'" class="text-[9px] text-gray-500 italic mt-2 italic">"{{ loan.notes }}"</p>
                                            <p v-if="loan.return_notes && loan.status === 'returned'" class="text-[9px] text-emerald-600 italic mt-2">"{{ loan.return_notes }}"</p>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-gray-400 text-center py-4">Belum ada riwayat peminjaman.</p>
                                </div>

                                <!-- Maintenance -->
                                <div v-if="activeTab === 'maintenance'">
                                    <div v-if="asset.maintenance_logs?.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div v-for="log in asset.maintenance_logs" :key="log.id" class="p-4 bg-gray-50 border border-gray-100 rounded-lg">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-[10px] font-bold text-emerald-600 uppercase">{{ log.type }}</span>
                                                <span class="text-[9px] font-bold" :class="log.completed_at ? 'text-emerald-600' : 'text-amber-600'">
                                                    {{ log.completed_at ? 'SELESAI' : 'PENDING' }}
                                                </span>
                                            </div>
                                            <p class="text-xs font-semibold text-gray-700 leading-normal mb-3">{{ log.description }}</p>
                                            <div class="flex items-center justify-between pt-2 border-t border-gray-200/50">
                                                <span class="text-[10px] text-gray-400">{{ formatDate(log.created_at) }}</span>
                                                <span class="text-xs font-bold text-gray-900">{{ formatCurrency(log.cost) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-gray-400 text-center py-4">Belum ada log maintenance.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="lg:col-span-4 space-y-6">
                        <!-- QR Code -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h4 class="text-sm font-bold text-gray-900 mb-6">Identifikasi Asset</h4>
                            <QrCodeDisplay :asset-id="asset.id" :code="asset.asset_code || asset.barcode_code" />
                            
                            <div class="mt-8 pt-6 border-t border-gray-100 space-y-2">
                                <button @click="showMaintenanceModal = true" class="w-full py-2.5 bg-gray-50 border border-gray-200 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-100 transition-colors">
                                    Log Maintenance
                                </button>
                                <button @click="updateStatus('available')" v-if="asset.status !== 'available'" class="w-full py-2.5 bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors">
                                    Tandai Tersedia
                                </button>
                            </div>
                        </div>

                        <!-- Asset Value & Depreciation Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h4 class="text-sm font-bold text-gray-900 mb-6">Nilai & Penyusutan</h4>
                            
                            <div class="space-y-4">
                                <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block mb-1">Nilai Buku Saat Ini</span>
                                    <div class="text-xl font-black text-emerald-700 font-mono">
                                        {{ formatCurrency(asset.current_book_value) }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3">
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-gray-400 font-bold uppercase">Harga Beli</span>
                                        <span class="text-gray-700 font-bold">{{ formatCurrency(asset.purchase_price) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-gray-400 font-bold uppercase">Nilai Residu</span>
                                        <span class="text-gray-700 font-bold">{{ formatCurrency(asset.salvage_value) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-gray-400 font-bold uppercase">Umur Ekonomis</span>
                                        <span class="text-gray-700 font-bold">{{ asset.useful_life || 0 }} Tahun</span>
                                    </div>
                                    <div class="h-[1px] bg-gray-100 my-1"></div>
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-gray-400 font-bold uppercase">Akumulasi Penyusutan</span>
                                        <span class="text-rose-600 font-bold">{{ formatCurrency(asset.purchase_price - asset.current_book_value) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <p class="text-[10px] text-gray-400 leading-relaxed italic">
                                    * Perhitungan menggunakan metode Garis Lurus (Straight-Line) berdasarkan tanggal pembelian dan umur ekonomis.
                                </p>
                            </div>
                        </div>

                        <!-- Asset Age Card -->
                        <div class="bg-gray-900 rounded-xl p-6 text-white shadow-sm">
                            <span class="text-[10px] font-bold uppercase tracking-widest opacity-70">Usia Record</span>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-4xl font-bold">
                                    {{ Math.floor((new Date() - new Date(asset.created_at)) / (1000 * 60 * 60 * 24)) }}
                                </span>
                                <span class="text-xs font-medium opacity-80">Hari tercatat</span>
                            </div>
                            <p class="text-xs mt-4 opacity-70 leading-relaxed">Asset pertama kali didaftarkan pada {{ formatDateShort(asset.created_at) }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AssetFormModal :show="showEditModal" :asset="asset" :locations="locations" :categories="categories" :subcategories="subcategories" :departments="departments" :vendors="vendors" @close="showEditModal = false" />
        <MovementFormModal :show="showMovementModal" :asset="asset" :locations="locations" @close="showMovementModal = false" />
        <MaintenanceFormModal :show="showMaintenanceModal" :asset="asset" @close="showMaintenanceModal = false" />
        <LoanFormModal :show="showLoanModal" :asset="asset" @close="showLoanModal = false" />
        <LoanReturnModal :show="showReturnModal" :loan="asset.loans?.length > 0 ? asset.loans[0] : null" @close="showReturnModal = false" />
    </AuthenticatedLayout>
</template>
