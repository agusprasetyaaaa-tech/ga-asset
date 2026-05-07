<script setup>
/**
 * Assets Trash Page - Standardized Table UI
 * Consistent with Locations, Departments, Vendors pages.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    assets: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
const isLoading = ref(false);
const selectedIds = ref([]);

// Global Confirmation Modal State
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', type: 'danger', confirmText: 'Ya', onConfirm: () => {} });

// Bulk Actions Logic
const toggleAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = props.assets.data.map(a => a.id);
    } else {
        selectedIds.value = [];
    }
};

let searchTimeout = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('assets.trash'), {
        search: search.value || undefined,
        per_page: perPage.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        only: ['assets', 'filters'],
        onFinish: () => {
            isLoading.value = false;
            selectedIds.value = [];
        }
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch(perPage, (val) => {
    selectedIds.value = [];
    isLoading.value = true;
    router.get(route('assets.trash'), { ...props.filters, per_page: val }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['assets', 'filters'],
        onFinish: () => isLoading.value = false
    });
});

const restoreAsset = (asset) => {
    confirmData.value = {
        title: 'Pulihkan Aset',
        message: `Apakah Anda yakin ingin memulihkan aset "${asset.name}" kembali ke inventory?`,
        type: 'success',
        confirmText: 'Ya, Pulihkan',
        onConfirm: () => {
            router.post(route('assets.restore', asset.id), {}, {
                onSuccess: () => {
                    showConfirmModal.value = false;
                    selectedIds.value = [];
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const forceDeleteAsset = (asset) => {
    confirmData.value = {
        title: 'Hapus Permanen',
        message: `Peringatan: Aset "${asset.name}" akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.`,
        type: 'danger',
        confirmText: 'Ya, Hapus Permanen',
        onConfirm: () => {
            router.delete(route('assets.force-delete', asset.id), {
                onSuccess: () => {
                    showConfirmModal.value = false;
                    selectedIds.value = [];
                }
            });
        }
    };
    showConfirmModal.value = true;
};

// Bulk Actions Execution
const bulkRestore = () => {
    if (selectedIds.value.length === 0) return;
    confirmData.value = {
        title: 'Pulihkan Massal',
        message: `Apakah Anda yakin ingin memulihkan ${selectedIds.value.length} aset yang dipilih?`,
        type: 'success',
        confirmText: 'Ya, Pulihkan Semua',
        onConfirm: () => {
            router.post(route('assets.bulk-restore'), { ids: selectedIds.value }, {
                onSuccess: () => {
                    showConfirmModal.value = false;
                    selectedIds.value = [];
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const bulkForceDelete = () => {
    if (selectedIds.value.length === 0) return;
    confirmData.value = {
        title: 'Hapus Permanen Massal',
        message: `PERINGATAN: ${selectedIds.value.length} aset akan dihapus secara permanen. Data tidak akan bisa dikembalikan lagi.`,
        type: 'danger',
        confirmText: 'Ya, Hapus Semua',
        onConfirm: () => {
            router.post(route('assets.bulk-force-delete'), { ids: selectedIds.value }, {
                onSuccess: () => {
                    showConfirmModal.value = false;
                    selectedIds.value = [];
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const getImageUrl = (path) => {
    if (!path) return null;
    return `/storage/${path}`;
};
</script>

<template>
    <Head title="Arsip Aset" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('assets.index')" 
                        class="h-9 w-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Arsip Aset</h2>
                        <p class="text-xs text-gray-500 mt-1">Katalog aset yang dihapus sementara.</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-opacity duration-300" :class="{'opacity-40 pointer-events-none': isLoading}">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="h-9 w-9 rounded-lg bg-rose-50 border border-rose-100 flex items-center justify-center">
                                <svg class="h-4.5 w-4.5 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div class="hidden sm:block">
                                <h3 class="text-sm font-bold text-gray-800">Daftar Arsip</h3>
                                <p class="text-[10px] text-gray-400 font-medium">{{ assets.total || 0 }} aset diarsipkan</p>
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
                                <input v-model="search" type="text" placeholder="Cari aset di arsip..." 
                                    class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                            </div>
                            <button v-if="selectedIds.length > 0" @click="bulkRestore" 
                                class="bg-emerald-50 text-emerald-600 border border-emerald-100 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-1.5 shadow-sm uppercase shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Pulihkan ({{ selectedIds.length }})
                            </button>
                            <button v-if="selectedIds.length > 0" @click="bulkForceDelete" 
                                class="bg-rose-50 text-rose-600 border border-rose-100 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-rose-600 hover:text-white transition-all flex items-center gap-1.5 shadow-sm uppercase shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus ({{ selectedIds.length }})
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="flex-grow overflow-x-auto min-h-[300px]">
                        <!-- DESKTOP VIEW -->
                        <table class="min-w-full text-left hidden md:table">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-4 py-3 border-b border-gray-100 w-10 text-center">
                                        <input type="checkbox" @change="toggleAll" :checked="selectedIds.length === assets.data?.length && assets.data?.length > 0"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                    </th>
                                    <th class="px-2 py-3 border-b border-gray-100 w-10 text-center text-xs font-bold text-gray-500">No</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500">Detail Aset</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500">Identifikasi</th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-32 text-xs font-bold text-gray-500 hidden lg:table-cell">Subkategori</th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-36 text-center text-xs font-bold text-gray-500">Dihapus Pada</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-right w-24 text-xs font-bold text-gray-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(asset, index) in assets.data" :key="asset.id" 
                                    class="hover:bg-emerald-50/30 transition-colors duration-150"
                                    :class="{'bg-emerald-50/50': selectedIds.includes(asset.id)}">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="selectedIds" :value="asset.id"
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
                                                <div class="font-semibold text-sm text-gray-800 truncate">{{ asset.name }}</div>
                                                <div class="text-[10px] text-gray-400 leading-tight truncate max-w-sm mt-0.5">{{ asset.subcategory?.category?.name || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="min-w-0">
                                            <div class="font-mono text-sm font-bold text-emerald-700 truncate">{{ asset.asset_code }}</div>
                                            <div class="text-[11px] text-gray-400 font-mono leading-tight mt-0.5">SN: {{ asset.serial_number || '-' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <div class="font-semibold text-sm text-gray-800 truncate">{{ asset.subcategory?.name || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full">
                                            <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ new Date(asset.deleted_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-1">
                                            <button @click="restoreAsset(asset)" class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-md hover:bg-emerald-50 transition-all duration-150" title="Pulihkan">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button @click="forceDeleteAsset(asset)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-all duration-150" title="Hapus Permanen">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!assets.data?.length">
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                                <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </div>
                                            <p class="text-xs font-semibold text-gray-400">Arsip kosong</p>
                                            <p class="text-[10px] text-gray-300 mt-0.5">Belum ada aset yang diarsipkan</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- MOBILE VIEW: Card List -->
                        <div class="md:hidden divide-y divide-gray-50">
                            <div v-for="asset in assets.data" :key="asset.id" class="p-4 bg-white space-y-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                            <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                            <span v-else class="text-xs font-bold text-gray-300">{{ asset.name.charAt(0) }}</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] text-gray-400 font-bold truncate">{{ asset.name }}</p>
                                            <p class="text-[11px] text-emerald-700 font-mono font-bold">{{ asset.asset_code }}</p>
                                            <div class="mt-2 text-[9px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                                                SN: {{ asset.serial_number || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <input type="checkbox" v-model="selectedIds" :value="asset.id" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 cursor-pointer" />
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div class="p-2 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="text-[9px] text-gray-400 font-bold block mb-0.5">Subkategori</span>
                                        <span class="text-xs font-bold text-gray-900">{{ asset.subcategory?.name || '-' }}</span>
                                    </div>
                                    <div class="p-2 bg-rose-50 rounded-lg border border-rose-100">
                                        <span class="text-[9px] text-rose-400 font-bold block mb-0.5">Dihapus</span>
                                        <span class="text-xs font-bold text-rose-600">{{ new Date(asset.deleted_at).toLocaleDateString('id-ID') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end pt-2 gap-2">
                                    <button @click="restoreAsset(asset)" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-100">Pulihkan</button>
                                    <button @click="forceDeleteAsset(asset)" class="p-1.5 text-gray-400 hover:text-red-500"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </div>
                            <div v-if="!assets.data.length" class="p-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-400">Arsip kosong</p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">Belum ada aset yang diarsipkan</p>
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

        <ConfirmModal 
            :show="showConfirmModal"
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            :confirmText="confirmData.confirmText"
            @close="showConfirmModal = false"
            @confirm="confirmData.onConfirm"
        />
    </AuthenticatedLayout>
</template>
