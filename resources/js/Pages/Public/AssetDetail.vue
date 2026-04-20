<script setup>
/**
 * Comprehensive Public Asset Detail View
 * Accessible without login via QR code scan.
 * Matches the internal detail view information.
 */
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    asset: Object,
});

const activeTab = ref('movements');

const getImageUrl = (path) => {
    if (!path) return null;
    return `/storage/${path}`;
};

const formatCurrency = (value) => {
    if (!value) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const getStatusBadge = (status) => {
    const badges = {
        'available': { text: 'Tersedia', class: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
        'in_use': { text: 'Digunakan', class: 'bg-blue-100 text-blue-700 border-blue-200' },
        'maintenance': { text: 'Perbaikan', class: 'bg-amber-100 text-amber-700 border-amber-200' },
        'damaged': { text: 'Rusak', class: 'bg-rose-100 text-rose-700 border-rose-200' }
    };
    return badges[status] || { text: status, class: 'bg-gray-100 text-gray-700 border-gray-200' };
};

const conditionLabel = (condition) => {
    const labels = { 'good': 'Baik', 'fair': 'Cukup', 'poor': 'Buruk' };
    return labels[condition] || condition;
};

const conditionColor = (condition) => {
    const colors = { 
        'good': 'bg-emerald-50 text-emerald-600 border-emerald-100', 
        'fair': 'bg-amber-50 text-amber-600 border-amber-100', 
        'poor': 'bg-rose-50 text-rose-600 border-rose-100' 
    };
    return colors[condition] || 'bg-gray-50 text-gray-600 border-gray-100';
};
</script>

<template>
    <Head :title="`Detail Aset: ${asset.name}`" />

    <div class="min-h-screen bg-[#f8fafc] flex flex-col items-center font-sans tracking-tight">
        <!-- Sticky Header (Mobile) -->
        <div class="sticky top-0 w-full bg-white/80 backdrop-blur-md border-b border-gray-200 px-6 py-4 flex items-center justify-between z-50">
            <div class="flex items-center gap-3">
                <ApplicationLogo class="h-8 w-8 object-contain" />
                <span class="text-xs font-black tracking-tight text-gray-900 uppercase">Asset Management</span>
            </div>
            <Link :href="route('login')" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest bg-gray-100 px-3 py-1.5 rounded-full hover:bg-gray-200 transition-colors">
                Login
            </Link>
        </div>

        <div class="w-full max-w-lg p-4 space-y-4 pb-20">
            <!-- Asset Main Card -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                <!-- Photo Banner -->
                <div class="w-full h-56 bg-white relative overflow-hidden flex items-center justify-center border-b border-gray-100 p-6">
                    <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="w-full h-full object-contain" />
                    <div v-else class="flex flex-col items-center text-gray-200">
                        <svg class="h-14 w-14" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <!-- Status Overlay -->
                    <div class="absolute top-4 right-4 flex flex-col gap-2 items-end">
                        <div :class="`px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg backdrop-blur-md border ${getStatusBadge(asset.status).class}`">
                            {{ getStatusBadge(asset.status).text }}
                        </div>
                        <div v-if="asset.condition" :class="`px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg backdrop-blur-md border ${conditionColor(asset.condition)}`">
                            {{ conditionLabel(asset.condition) }}
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="p-6">
                    <div class="mb-6">
                        <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-[0.2em] mb-1">
                            {{ asset.subcategory?.category?.name || 'Asset' }} / {{ asset.subcategory?.name || 'Detail' }}
                        </div>
                        <h1 class="text-2xl font-black text-gray-900 leading-tight mb-1">{{ asset.name }}</h1>
                        <p class="text-sm text-gray-500 font-medium">
                            <span v-if="asset.brand" class="font-bold text-gray-700">{{ asset.brand }}</span>
                            <span v-if="asset.brand && asset.model_type"> — </span>
                            <span v-if="asset.model_type">{{ asset.model_type }}</span>
                        </p>
                    </div>

                    <!-- Core Attributes Grid -->
                    <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-6">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 font-mono">Kode Asset</span>
                            <span class="text-xs font-bold text-gray-900 bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">{{ asset.asset_code || asset.barcode_code }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 font-mono">Serial Number</span>
                            <span class="text-xs font-bold text-gray-900 bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">{{ asset.serial_number || '-' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 font-mono">Departemen</span>
                            <span class="text-xs font-bold text-gray-900">{{ asset.department_rel?.name || asset.department || '-' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 font-mono">Kategori</span>
                            <span class="text-xs font-bold text-gray-900">{{ asset.subcategory?.name || '-' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 font-mono">Lokasi</span>
                            <span class="text-xs font-bold text-gray-900">{{ asset.location?.name || '-' }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 font-mono">Pengguna / PIC</span>
                            <span class="text-xs font-bold text-gray-900">{{ asset.current_holder || '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Specifications Card -->
            <div v-if="asset.specification" class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-8 w-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest text-gray-800">Spesifikasi</span>
                </div>
                <div class="p-4 bg-gray-50 rounded-2xl text-xs text-gray-700 leading-relaxed whitespace-pre-line border border-gray-100">
                    {{ asset.specification }}
                </div>
            </div>

            <!-- Description Card -->
            <div v-if="asset.description" class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-8 w-8 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest text-gray-800">Keterangan</span>
                </div>
                <div class="p-4 bg-gray-50 rounded-2xl text-xs text-gray-700 leading-relaxed whitespace-pre-line border border-gray-100">
                    {{ asset.description }}
                </div>
            </div>

            <!-- Procurement Card -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-8 w-8 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest text-gray-800">Informasi Pembelian</span>
                </div>
                
                <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Vendor / Supplier</span>
                        <span class="text-xs font-bold text-gray-900">{{ asset.vendor_rel?.name || asset.vendor || '-' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Tanggal Pembelian</span>
                        <span class="text-xs font-bold text-gray-900">{{ asset.purchase_date ? formatDateShort(asset.purchase_date) : '-' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Garansi Berakhir</span>
                        <span class="text-xs font-bold text-gray-900">{{ asset.warranty_date ? formatDateShort(asset.warranty_date) : '-' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Masa Pemakaian</span>
                        <span class="text-xs font-bold text-gray-900">{{ asset.usage_period || '-' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Harga Pembelian</span>
                        <span class="text-xs font-bold text-emerald-600 font-mono">{{ formatCurrency(asset.purchase_price) }}</span>
                    </div>
                </div>

                <div v-if="asset.notes" class="mt-6 pt-4 border-t border-gray-50">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Catatan Tambahan</span>
                    <p class="text-xs text-gray-600 italic bg-purple-50/50 p-4 rounded-2xl border border-purple-100">"{{ asset.notes }}"</p>
                </div>
            </div>

            <!-- History Tabs Card -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden min-h-[400px]">
                <div class="flex border-b border-gray-100 p-2 bg-gray-50/50">
                    <button @click="activeTab = 'movements'"
                        :class="activeTab === 'movements' ? 'bg-white shadow-sm text-emerald-600 font-black' : 'text-gray-400 font-bold'"
                        class="flex-1 py-3 text-[10px] uppercase tracking-widest rounded-2xl transition-all">
                        Riwayat
                    </button>
                    <button @click="activeTab = 'maintenance'"
                        :class="activeTab === 'maintenance' ? 'bg-white shadow-sm text-emerald-600 font-black' : 'text-gray-400 font-bold'"
                        class="flex-1 py-3 text-[10px] uppercase tracking-widest rounded-2xl transition-all">
                        Maintenance
                    </button>
                </div>

                <div class="p-6">
                    <!-- Movements List -->
                    <div v-if="activeTab === 'movements'">
                        <div v-if="asset.movements?.length" class="relative space-y-8 before:absolute before:left-[17px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-100">
                            <div v-for="movement in asset.movements" :key="movement.id" class="relative pl-10">
                                <div class="absolute left-0 top-1.5 h-[34px] w-[34px] rounded-full bg-white border-2 border-emerald-500 shadow-sm flex items-center justify-center z-10">
                                    <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 20h5v-5" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 4l19 16" /></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-xs font-black text-gray-900 uppercase tracking-tight">{{ movement.to_location?.name || 'N/A' }}</span>
                                        <span class="text-[10px] bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full font-bold">{{ movement.to_holder || 'Sistem' }}</span>
                                    </div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ formatDate(movement.created_at) }}</div>
                                    <p v-if="movement.notes" class="text-xs text-gray-500 italic bg-gray-50 p-3 rounded-xl border border-gray-100">"{{ movement.notes }}"</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg class="h-12 w-12 mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-[10px] font-bold uppercase tracking-widest">Belum ada riwayat</span>
                        </div>
                    </div>

                    <!-- Maintenance List -->
                    <div v-if="activeTab === 'maintenance'">
                        <div v-if="asset.maintenance_logs?.length" class="space-y-4">
                            <div v-for="log in asset.maintenance_logs" :key="log.id" class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-[9px] font-black text-emerald-600 uppercase tracking-[0.2em]">{{ log.type }}</span>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded-full" :class="log.completed_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        {{ log.completed_at ? 'DONE' : 'PENDING' }}
                                    </span>
                                </div>
                                <p class="text-xs font-bold text-gray-800 leading-normal mb-4">{{ log.description }}</p>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-200/50">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-bold text-gray-400 uppercase">Waktu</span>
                                        <span class="text-[9px] font-bold text-gray-600">{{ formatDate(log.created_at) }}</span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[8px] font-bold text-gray-400 uppercase">Biaya</span>
                                        <span class="text-xs font-black text-gray-900 font-mono">{{ formatCurrency(log.cost) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg class="h-12 w-12 mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="text-[10px] font-bold uppercase tracking-widest">Tidak ada record</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
