<script setup>
/**
 * Assets Index Page - Standardized Table UI
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import AssetFormModal from '@/Components/Inventory/AssetFormModal.vue';
import LoanFormModal from '@/Components/Inventory/LoanFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    assets: Object,
    locations: Array,
    categories: Array,
    subcategories: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    vendors: { type: Array, default: () => [] },
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const subcategoryIdFilter = ref(props.filters?.subcategory_id || '');
const perPage = ref(props.filters?.per_page || 10);
const isLoading = ref(false);
const selectedAssets = ref([]);
const showAssetModal = ref(false);
const editingAsset = ref(null);
const showConfirmModal = ref(false);
const showExportMenu = ref(false);
const showImportModal = ref(false);
const importForm = useForm({ file: null });
const importFileInput = ref(null);

const page = usePage();

const can = (permission) => page.props.auth.user.permissions.includes(permission);

// Export functions
const exportExcel = () => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (subcategoryIdFilter.value) params.set('subcategory_id', subcategoryIdFilter.value);
    window.location.href = route('assets.export.excel') + '?' + params.toString();
    showExportMenu.value = false;
};

const exportPdf = () => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (subcategoryIdFilter.value) params.set('subcategory_id', subcategoryIdFilter.value);
    window.location.href = route('assets.export.pdf') + '?' + params.toString();
    showExportMenu.value = false;
};

const downloadTemplate = () => {
    window.location.href = route('assets.import.template');
};

const onImportFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    importForm.post(route('assets.import'), {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
            if (importFileInput.value) importFileInput.value.value = '';
        },
    });
};
const confirmData = ref({ title: '', message: '', type: 'danger', confirmText: 'Ya, Hapus', onConfirm: () => {} });

let searchTimeout = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('assets.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        subcategory_id: subcategoryIdFilter.value || undefined,
        per_page: perPage.value || undefined,
        sort: props.filters?.sort,
        dir: props.filters?.dir,
    }, {
        preserveState: true,
        replace: true,
        only: ['assets', 'filters'],
        onFinish: () => isLoading.value = false
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([statusFilter, subcategoryIdFilter], () => {
    selectedAssets.value = [];
    applyFilters();
});

watch(perPage, (val) => {
    selectedAssets.value = [];
    isLoading.value = true;
    router.get(route('assets.index'), { ...props.filters, per_page: val }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['assets', 'filters'],
        onFinish: () => isLoading.value = false
    });
});

const toggleSort = (field) => {
    let dir = (props.filters.sort === field && props.filters.dir === 'asc') ? 'desc' : 'asc';
    router.get(route('assets.index'), { ...props.filters, sort: field, dir: dir }, { preserveState: true, only: ['assets', 'filters'] });
};

const toggleAll = (e) => {
    selectedAssets.value = e.target.checked ? props.assets.data.map(a => a.id) : [];
};

const bulkDelete = () => {
    confirmData.value = {
        title: 'Hapus Massal Aset',
        message: `Hapus ${selectedAssets.value.length} aset yang dipilih?`,
        type: 'danger',
        confirmText: 'Ya, Hapus Semua',
        onConfirm: () => {
            router.post(route('assets.bulk-delete'), { ids: selectedAssets.value }, {
                onSuccess: () => {
                    selectedAssets.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const openCreateModal = () => { editingAsset.value = null; showAssetModal.value = true; };
const openEditModal = (asset) => { editingAsset.value = asset; showAssetModal.value = true; };

const deleteAsset = (asset) => {
    confirmData.value = {
        title: 'Hapus Aset',
        message: `Apakah Anda yakin ingin menghapus aset "${asset.name}"? Semua data yang terkait mungkin akan terpengaruh.`,
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: () => { router.delete(route('assets.destroy', asset.id), { onSuccess: () => showConfirmModal.value = false }); }
    };
    showConfirmModal.value = true;
};

const formatCurrency = (val) => {
    if (!val) return 'Rp 0,00';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(val);
};

const getImageUrl = (path) => path ? `/storage/${path}` : null;

// Return to User Logic
const showReturnModal = ref(false);
const returningAsset = ref(null);
const returnForm = ref({ user_name: '', department_id: '', to_location_id: '', notes: '' });
const isReturning = ref(false);

const openReturnModal = (asset) => {
    returningAsset.value = asset;
    returnForm.value = { 
        user_name: asset.current_holder || '', 
        department_id: asset.department_id || '', 
        to_location_id: asset.location_id || '', 
        notes: '' 
    };
    showReturnModal.value = true;
};

const submitReturnToUser = () => {
    if (!returnForm.value.user_name.trim()) return;
    isReturning.value = true;
    router.patch(route('assets.return-to-user', returningAsset.value.id), returnForm.value, {
        preserveScroll: true,
        onSuccess: () => { showReturnModal.value = false; isReturning.value = false; router.get(window.location.href, {}, { preserveState: false, preserveScroll: true }); },
        onFinish: () => isReturning.value = false
    });
};

// Quick Loan Logic
const showLoanModal = ref(false);
const loaningAsset = ref(null);
const openLoanModal = (asset) => {
    loaningAsset.value = asset;
    showLoanModal.value = true;
};

// Mark as Available Logic
const setAvailable = (asset) => {
    confirmData.value = {
        title: 'Setel ke Tersedia',
        message: `Apakah Anda yakin ingin menyetel aset "${asset.name}" kembali ke status Tersedia?`,
        type: 'warning',
        confirmText: 'Ya, Tersedia',
        onConfirm: () => {
            router.patch(route('assets.available', asset.id), {}, {
                onSuccess: () => showConfirmModal.value = false,
                preserveScroll: true
            });
        }
    };
    showConfirmModal.value = true;
};
</script>

<template>
    <Head title="Manajemen Aset" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Aset</h2>
                    <p class="text-xs text-gray-500 mt-1">Katalog inventaris perusahaan.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-[600px] transition-opacity duration-300" :class="{'opacity-40 pointer-events-none': isLoading}">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="h-10 w-10 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="hidden sm:block">
                                <h3 class="text-sm font-bold text-gray-800">Daftar Aset</h3>
                                <p class="text-[10px] text-gray-400 font-medium">{{ assets.total || 0 }} aset</p>
                            </div>
                        </div>
                        
                        <!-- Search & Actions -->
                        <div class="flex items-center gap-2 flex-grow sm:justify-end">
                            <div class="relative flex-grow max-w-[300px]">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input v-model="search" type="text" placeholder="Cari aset, serial, atau kode..." 
                                    class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                            </div>
                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button @click="showExportMenu = !showExportMenu" class="bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-blue-100 transition-all flex items-center gap-1.5 shadow-sm shrink-0">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Export
                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div v-if="showExportMenu" class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-xl border border-gray-200 z-50 py-1">
                                    <button @click="exportExcel" class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 flex items-center gap-2 transition">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        Export Excel
                                    </button>
                                    <button @click="exportPdf" class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-red-50 hover:text-red-700 flex items-center gap-2 transition">
                                        <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        Export PDF
                                    </button>
                                </div>
                                <div v-if="showExportMenu" class="fixed inset-0 z-40" @click="showExportMenu = false"></div>
                            </div>

                            <!-- Import Button -->
                            <button @click="showImportModal = true" class="bg-amber-50 text-amber-600 border border-amber-200 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-amber-100 transition-all flex items-center gap-1.5 shadow-sm shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                Import
                            </button>

                            <Link :href="route('assets.trash')"
                                class="bg-gray-50 text-gray-600 border border-gray-200 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-gray-100 transition-all flex items-center gap-1.5 shadow-sm shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Arsip
                            </Link>
                            <button @click="openCreateModal" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm shrink-0">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah
                            </button>
                        </div>
                    </div>

                    <!-- Secondary Filters -->
                    <div class="px-6 py-3 border-b border-gray-100 bg-white flex flex-wrap items-center gap-3">
                        <div class="relative w-full sm:w-40">
                            <select v-model="statusFilter" 
                                style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none !important;"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 text-[11px] font-bold text-gray-500 py-1.5 pl-3 pr-8 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition cursor-pointer">
                                <option value="">Semua Status</option>
                                <option value="available">Tersedia</option>
                                <option value="in_use">Digunakan</option>
                                <option value="borrowed">Dipinjam</option>
                                <option value="maintenance">Perbaikan</option>
                                <option value="damaged">Rusak</option>
                            </select>
                            <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 h-2.5 w-2.5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div class="relative w-full sm:w-48">
                            <select v-model="subcategoryIdFilter" 
                                style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none !important;"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 text-[11px] font-bold text-gray-500 py-1.5 pl-3 pr-8 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition cursor-pointer">
                                <option value="">Semua Subkategori</option>
                                <option v-for="sub in subcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>
                            <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 h-2.5 w-2.5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Bulk Delete & Select All Indicator -->
                    <div v-if="assets.data?.length > 0" class="px-6 py-3 border-b border-gray-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3 p-3 rounded-xl transition-all border w-full"
                            :class="selectedAssets.length > 0 ? 'bg-rose-50 border-rose-100' : 'bg-gray-50/50 border-gray-100'">
                            <div class="flex items-center gap-3 flex-grow">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" @change="toggleAll" :checked="selectedAssets.length === assets.data?.length && assets.data?.length > 0"
                                        class="h-5 w-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 transition" />
                                    <span class="text-xs font-bold text-gray-600">Pilih Semua</span>
                                </label>
                                <span v-if="selectedAssets.length > 0" class="h-4 w-[1px] bg-rose-200 hidden sm:block"></span>
                                <span v-if="selectedAssets.length > 0" class="text-xs font-extrabold text-rose-700">{{ selectedAssets.length }} dipilih</span>
                            </div>
                            <button v-if="selectedAssets.length > 0 && can('delete assets')" @click="bulkDelete"
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black shadow-sm shadow-rose-100 active:scale-95 transition-all shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Hapus Semua
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="flex-grow overflow-x-auto min-h-[300px]">
                        <!-- DESKTOP VIEW -->
                        <table class="min-w-full text-left hidden md:table">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-4 py-3 border-b border-gray-100 w-10 text-center"></th>
                                    <th class="px-2 py-3 border-b border-gray-100 w-10 text-center text-xs font-bold text-gray-500">No</th>
                                    <th @click="toggleSort('detail')" class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500 cursor-pointer hover:bg-emerald-50 select-none group transition-all">
                                        <div class="flex items-center gap-1.5">
                                            Detail Aset
                                            <div class="flex flex-col -space-y-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                                <svg :class="{'text-emerald-600 opacity-100': filters.sort === 'detail' && filters.dir === 'asc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l-8 8h16l-8-8z"/></svg>
                                                <svg :class="{'text-emerald-600 opacity-100': filters.sort === 'detail' && filters.dir === 'desc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l8-8H4l8 8z"/></svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500">Identifikasi</th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-32 text-xs font-bold text-gray-500 hidden lg:table-cell">Subkategori</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-center w-28 text-xs font-bold text-gray-500">Status</th>
                                    <th @click="toggleSort('nilai')" class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500 cursor-pointer hover:bg-emerald-50 select-none group transition-all w-40">
                                        <div class="flex items-center gap-1.5">
                                            Nilai Aset
                                            <div class="flex flex-col -space-y-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                                <svg :class="{'text-emerald-600 opacity-100': filters.sort === 'nilai' && filters.dir === 'asc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l-8 8h16l-8-8z"/></svg>
                                                <svg :class="{'text-emerald-600 opacity-100': filters.sort === 'nilai' && filters.dir === 'desc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l8-8H4l8 8z"/></svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-right w-32 text-xs font-bold text-gray-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(asset, index) in assets.data" :key="asset.id" 
                                    class="hover:bg-emerald-50/30 transition-colors duration-150"
                                    :class="{'bg-emerald-50/50': selectedAssets.includes(asset.id)}">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="selectedAssets" :value="asset.id"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                    </td>
                                    <td class="px-2 py-4 text-center text-[11px] text-gray-400 font-bold tabular-nums">
                                        {{ (assets.current_page - 1) * assets.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                                                <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                                <span v-else class="text-xs font-bold text-gray-300">{{ asset.name.charAt(0) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <Link :href="route('assets.show', asset.id)" class="font-semibold text-sm text-gray-800 hover:text-emerald-600 truncate block">{{ asset.name }}</Link>
                                                <div class="text-[10px] text-gray-400 leading-tight truncate max-w-sm mt-0.5">{{ asset.subcategory?.name || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="min-w-0">
                                            <div class="font-mono text-sm font-bold text-emerald-700 truncate">{{ asset.asset_code || asset.barcode_code }}</div>
                                            <div class="text-[11px] text-gray-400 font-mono leading-tight mt-0.5">SN: {{ asset.serial_number || '-' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-sm text-gray-800 truncate">{{ asset.subcategory?.name || '-' }}</div>
                                            <div class="text-[10px] text-gray-400 leading-tight truncate mt-0.5">{{ asset.subcategory?.category?.name || '-' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <StatusBadge :status="asset.status" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-sm text-gray-800">{{ formatCurrency(asset.purchase_price) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-1">
                                            <!-- Tombol Pinjam (Hanya jika tersedia) -->
                                            <button v-if="asset.status === 'available' && can('create activity')" @click="openLoanModal(asset)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-md hover:bg-blue-50 transition-all duration-150" title="Pinjamkan Aset">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </button>
                                            
                                            <!-- Tombol Reset ke Tersedia (Jika tidak tersedia) -->
                                            <button v-if="asset.status !== 'available' && can('edit assets')" @click="setAvailable(asset)" class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-md hover:bg-emerald-50 transition-all duration-150" title="Setel ke Tersedia">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                            </button>

                                            <button v-if="asset.status === 'available' && can('edit assets')" @click="openReturnModal(asset)" class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-md hover:bg-emerald-50 transition-all duration-150" title="Serahkan ke User">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            </button>
                                            <button v-if="can('edit assets')" @click="openEditModal(asset)" class="p-1.5 text-gray-400 hover:text-amber-600 rounded-md hover:bg-amber-50 transition-all duration-150" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button v-if="can('delete assets')" @click="deleteAsset(asset)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-all duration-150" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!assets.data?.length">
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                                <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <p class="text-xs font-semibold text-gray-400">Belum ada data aset</p>
                                            <p class="text-[10px] text-gray-300 mt-0.5">Silakan tambahkan aset baru</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- MOBILE VIEW: Card List -->
                        <div class="md:hidden divide-y divide-gray-50">
                            <div v-for="asset in assets.data" :key="asset.id" class="p-4 bg-white space-y-3">
                                <!-- Card Header -->
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                            <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                            <span v-else class="text-xs font-bold text-gray-300">{{ asset.name.charAt(0) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase truncate">{{ asset.name }}</p>
                                            <p class="text-[11px] text-emerald-700 font-mono font-bold">{{ asset.asset_code || asset.barcode_code }}</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" v-model="selectedAssets" :value="asset.id" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 cursor-pointer" />
                                </div>

                                <!-- Info Grid -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="p-2 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="text-[9px] text-gray-400 font-bold block mb-0.5">Nilai Aset</span>
                                        <span class="text-xs font-bold text-gray-900">{{ formatCurrency(asset.purchase_price) }}</span>
                                    </div>
                                    <div class="p-2 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="text-[9px] text-gray-400 font-bold block mb-0.5">Status</span>
                                        <StatusBadge :status="asset.status" class="!scale-[0.8] origin-left !px-2" />
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-2">
                                    <span class="text-[10px] text-gray-400 font-bold tracking-tight">{{ asset.subcategory?.name || '-' }}</span>
                                    <div class="flex gap-2">
                                        <button v-if="asset.status === 'available'" @click="openReturnModal(asset)" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-100">Serahkan</button>
                                        <button v-if="can('edit assets')" @click="openEditModal(asset)" class="p-1.5 text-gray-400 hover:text-emerald-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                        <button v-if="can('delete assets')" @click="deleteAsset(asset)" class="p-1.5 text-gray-400 hover:text-red-500"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!assets.data.length" class="p-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-400">Belum ada data aset</p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">Silakan tambahkan aset baru</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <div class="relative flex items-center">
                                <select v-model="perPage" 
                                    style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none !important;"
                                    class="appearance-none bg-none text-[10px] font-bold text-emerald-700 bg-emerald-50 border-emerald-200 rounded-lg py-1 pl-2 pr-6 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-sm transition-all uppercase">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option value="all">ALL</option>
                                </select>
                                <svg class="absolute right-2 h-2.5 w-2.5 text-emerald-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>

                        <nav v-if="assets.links && assets.links.length > 3" class="flex gap-1">
                            <template v-for="(link, k) in assets.links" :key="k">
                                <Link v-if="link.url" :href="link.url" 
                                    class="px-2.5 py-1.5 text-[10px] font-bold rounded-md border transition-all duration-150 shadow-xs"
                                    :class="link.active ? 'bg-emerald-600 border-emerald-600 text-white shadow-emerald-200' : 'bg-white border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-200'"
                                    v-html="link.label" />
                                <span v-else class="px-2.5 py-1.5 text-[10px] font-bold text-gray-200 border border-gray-100 rounded-md" v-html="link.label"></span>
                            </template>
                        </nav>
                    </div>
                </div>

            </div>
        </div>

        <!-- Confirm Modal -->
        <ConfirmModal 
            :show="showConfirmModal"
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            :confirm-text="confirmData.confirmText"
            @close="showConfirmModal = false"
            @confirm="confirmData.onConfirm"
        />

        <!-- Return to User Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showReturnModal = false"></div>
                    <div class="relative bg-white rounded-xl border border-gray-200 shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="px-6 py-4 flex justify-between items-center bg-emerald-600 shadow-sm relative z-10">
                            <h3 class="text-sm font-bold text-white tracking-wide">Serahkan ke User</h3>
                            <button @click="showReturnModal = false" class="rounded-lg p-1.5 text-emerald-100 hover:bg-emerald-500 hover:text-white transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nama Pengguna *</label>
                                <input v-model="returnForm.user_name" type="text" placeholder="Masukkan nama penerima..." 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Lokasi Penempatan</label>
                                <select v-model="returnForm.to_location_id" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition">
                                    <option value="">-- Tetap di lokasi saat ini --</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Departemen Penerima</label>
                                <select v-model="returnForm.department_id" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition">
                                    <option value="">-- Pilih Departemen --</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2.5 px-6 py-4 border-t border-gray-100">
                            <button @click="showReturnModal = false" 
                                class="px-5 py-2.5 text-[11px] font-bold text-gray-500 tracking-wider rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button @click="submitReturnToUser" :disabled="isReturning"
                                class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg text-[11px] font-bold hover:bg-emerald-700 shadow-sm tracking-wider transition-all disabled:opacity-50">
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <AssetFormModal 
            :show="showAssetModal" :asset="editingAsset" :locations="locations" :categories="categories" :subcategories="subcategories"
            :departments="departments" :vendors="vendors" @close="showAssetModal = false" 
        />

        <LoanFormModal 
            :show="showLoanModal" 
            :asset="loaningAsset" 
            @close="showLoanModal = false" 
        />

        <!-- IMPORT MODAL -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showImportModal = false"></div>
                    <div class="relative bg-white rounded-xl border border-gray-200 shadow-2xl w-full max-w-lg overflow-hidden">
                        <div class="px-6 py-4 flex justify-between items-center bg-amber-500 shadow-sm relative z-10">
                            <h3 class="text-sm font-bold text-white tracking-wide">Import Data Aset dari Excel</h3>
                            <button @click="showImportModal = false" class="rounded-lg p-1.5 text-amber-100 hover:bg-amber-400 hover:text-white transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Flash Success Message -->
                            <div v-if="page.props.flash?.success" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">
                                ✅ {{ page.props.flash.success }}
                            </div>

                            <!-- Flash Errors -->
                            <div v-if="page.props.flash?.importErrors?.length" class="p-3 rounded-lg bg-red-50 border border-red-200 space-y-1 max-h-32 overflow-y-auto">
                                <p class="text-xs font-bold text-red-700 mb-1">⚠️ Detail Error:</p>
                                <p v-for="(err, i) in page.props.flash.importErrors" :key="i" class="text-[10px] text-red-600">{{ err }}</p>
                            </div>

                            <!-- Instructions -->
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <h4 class="text-xs font-bold text-gray-700 mb-2">Petunjuk Import:</h4>
                                <ol class="text-[10px] text-gray-500 space-y-1 list-decimal list-inside leading-relaxed">
                                    <li>Download template Excel terlebih dahulu</li>
                                    <li>Isi data sesuai format kolom yang tersedia</li>
                                    <li>Kolom <span class="font-bold text-gray-700">nama_aset</span> dan <span class="font-bold text-gray-700">serial_number</span> wajib diisi</li>
                                    <li>Kolom <span class="font-bold text-gray-700">kode_aset</span> bisa dikosongkan (auto-generate)</li>
                                    <li>Status: <code class="bg-gray-200 px-1 rounded">available</code>, <code class="bg-gray-200 px-1 rounded">in_use</code>, <code class="bg-gray-200 px-1 rounded">maintenance</code>, <code class="bg-gray-200 px-1 rounded">damaged</code></li>
                                    <li>Kondisi: <code class="bg-gray-200 px-1 rounded">baik</code>, <code class="bg-gray-200 px-1 rounded">cukup_baik</code>, <code class="bg-gray-200 px-1 rounded">kurang_baik</code>, <code class="bg-gray-200 px-1 rounded">rusak</code></li>
                                </ol>
                            </div>

                            <!-- Download Template -->
                            <button @click="downloadTemplate" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-emerald-50 border-2 border-dashed border-emerald-300 rounded-xl text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition-all">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Download Template Excel
                            </button>

                            <!-- File Upload -->
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Pilih File (.xlsx, .xls, .csv)</label>
                                <input ref="importFileInput" type="file" accept=".xlsx,.xls,.csv" @change="onImportFileChange"
                                    class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 cursor-pointer border border-gray-200 rounded-lg" />
                                <p v-if="importForm.errors.file" class="text-[10px] text-red-500 font-bold mt-1">{{ importForm.errors.file }}</p>
                            </div>

                            <!-- Submit -->
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100">
                                <button type="button" @click="showImportModal = false" 
                                    class="px-5 py-2.5 text-[11px] font-bold text-gray-500 uppercase tracking-wider rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                                    Batal
                                </button>
                                <button @click="submitImport" 
                                    :disabled="!importForm.file || importForm.processing"
                                    class="bg-amber-500 text-white px-6 py-2.5 rounded-lg text-[11px] font-bold hover:bg-amber-600 shadow-sm uppercase tracking-wider transition-all disabled:opacity-50 flex items-center gap-2">
                                    <svg v-if="importForm.processing" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ importForm.processing ? 'Mengimpor...' : 'Import Sekarang' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>
