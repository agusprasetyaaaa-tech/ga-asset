<script setup>
/**
 * Assets Trash Page - Professional Archive Management
 * Redesigned for consistency with Asset Management (Index).
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
const isProcessing = ref(false);
const selectedIds = ref([]);

// Global Confirmation Modal State
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', type: 'danger', confirmText: 'Ya', onConfirm: () => {} });

// Bulk Actions Logic
const isAllSelected = computed(() => {
    return props.assets.data.length > 0 && selectedIds.value.length === props.assets.data.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.assets.data.map(asset => asset.id);
    }
};

const toggleSelection = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

let searchTimeout = null;

const applyFilters = () => {
    isProcessing.value = true;
    router.get(route('assets.trash'), {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        only: ['assets', 'filters'],
        onFinish: () => {
            isProcessing.value = false;
            selectedIds.value = []; // Reset selections after filter
        }
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
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
        title: 'Pulihkan Semua Masal',
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
        title: 'Hapus Permanen Semua',
        message: `PERINGATAN KRITIS: ${selectedIds.value.length} aset akan dihapus secara permanen. Data tidak akan bisa dikembalikan lagi.`,
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
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('assets.index')" 
                        class="p-2.5 bg-white border border-gray-200 rounded-xl text-gray-400 hover:text-emerald-600 hover:border-emerald-200 transition-all shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Arsip Aset</h2>
                        <p class="text-xs text-gray-500 mt-1 font-bold">Katalog Aset yang Dihapus Sementara</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden transition-all duration-300" :class="{'opacity-40 pointer-events-none': isProcessing}">
                    
                    <!-- Unified Search & Action Bar -->
                    <div class="px-6 py-6 border-b border-gray-50 bg-gray-50/30 space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Search -->
                            <div class="relative w-full md:max-w-md">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="search" type="text" 
                                    placeholder="Cari aset di arsip..."
                                    class="block w-full pl-11 pr-4 py-3 bg-white border border-gray-100 rounded-2xl text-sm placeholder:text-gray-400 focus:ring-4 focus:ring-emerald-500/5 focus:border-emerald-500 transition-all shadow-sm" />
                            </div>

                            <!-- Bulk Actions (Desktop) -->
                            <div v-if="selectedIds.length > 0" class="flex items-center gap-2 animate-fadeIn transition-all">
                                <span class="hidden lg:inline text-xs font-bold text-gray-400 mr-2">{{ selectedIds.length }} item dipilih</span>
                                <button @click="bulkRestore" class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold border border-emerald-100 hover:bg-emerald-100 transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Pulihkan Semua
                                </button>
                                <button @click="bulkForceDelete" class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-rose-50 text-rose-700 rounded-xl text-xs font-bold border border-rose-100 hover:bg-rose-100 transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus Semua
                                </button>
                            </div>
                        </div>

                        <!-- Mobile Bulk Controls -->
                        <div class="lg:hidden flex items-center justify-between p-3 bg-white/50 rounded-2xl border border-dashed border-gray-200">
                             <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                                        class="h-5 w-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0 transition-all" />
                                </div>
                                <span class="text-xs font-black text-gray-700 select-none">Pilih Semua</span>
                            </label>
                            <span v-if="selectedIds.length > 0" class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-2 py-1 rounded-lg">{{ selectedIds.length }} Dipilih</span>
                        </div>
                    </div>

                    <!-- Desktop View (Table) -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-center w-10">
                                        <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                                            class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all pointer-events-auto" />
                                    </th>
                                    <th class="px-2 py-4 text-center text-[10px] font-black text-gray-400 w-10">No</th>
                                    <th class="px-6 py-4 text-left text-[11px] font-black text-gray-700">Aset Detail</th>
                                    <th class="px-6 py-4 text-left text-[11px] font-black text-gray-700">Identifikasi</th>
                                    <th class="px-6 py-4 text-left text-[11px] font-black text-gray-700 hidden lg:table-cell">Subkategori</th>
                                    <th class="px-6 py-4 text-center text-[11px] font-black text-gray-700">Dihapus Pada</th>
                                    <th class="px-6 py-4 text-right text-[11px] font-black text-gray-700 w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="(asset, index) in assets.data" :key="asset.id" 
                                    class="hover:bg-emerald-50/30 transition-all group"
                                    :class="selectedIds.includes(asset.id) ? 'bg-emerald-50/50' : ''">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox" :checked="selectedIds.includes(asset.id)" @change="toggleSelection(asset.id)"
                                            class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all pointer-events-auto" />
                                    </td>
                                    <td class="px-2 py-4 text-center text-xs font-black text-gray-300 tabular-nums">
                                        {{ (assets.current_page - 1) * assets.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="h-12 w-12 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center overflow-hidden shadow-inner group-hover:scale-110 transition-transform duration-300">
                                                <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                                <span v-else class="text-gray-300 text-xs font-black">{{ asset.name.charAt(0) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-bold text-gray-900 truncate">{{ asset.name }}</div>
                                                <div class="text-[10px] text-gray-400 font-bold leading-tight">{{ asset.subcategory?.category?.name || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-emerald-800 tracking-tight">{{ asset.asset_code }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono tracking-tight">SN: {{ asset.serial_number || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="text-[11px] font-bold text-gray-900 leading-tight">{{ asset.subcategory?.name || '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                         <div class="text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-3 py-1.5 rounded-xl inline-flex items-center gap-1.5">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ new Date(asset.deleted_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 pr-2">
                                            <button @click="restoreAsset(asset)"
                                                class="p-2 text-emerald-600 bg-emerald-50 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100"
                                                title="Pulihkan">
                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                            <button @click="forceDeleteAsset(asset)"
                                                class="p-2 text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm border border-rose-100"
                                                title="Hapus Permanen">
                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!assets.data.length">
                                    <td colspan="7" class="px-6 py-32 text-center bg-gray-50/30">
                                        <div class="flex flex-col items-center">
                                            <div class="h-20 w-20 bg-white rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center mb-4">
                                                <svg class="h-10 w-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7M10 11v6m4-6v6m-4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </div>
                                            <p class="text-sm font-black text-gray-400">Arsip kosong.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View (Card) -->
                    <div class="lg:hidden divide-y divide-gray-50">
                        <div v-for="asset in assets.data" :key="asset.id" 
                            class="p-5 flex flex-col gap-4 group transition-all"
                            :class="selectedIds.includes(asset.id) ? 'bg-emerald-50/40' : 'bg-white'">
                            
                            <div class="flex items-start gap-4">
                                <input type="checkbox" :checked="selectedIds.includes(asset.id)" @change="toggleSelection(asset.id)"
                                    class="h-6 w-6 mt-1 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all pointer-events-auto shadow-sm" />
                                <div class="h-16 w-16 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center overflow-hidden shadow-inner">
                                    <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                    <span v-else class="text-xs font-black text-gray-300">{{ asset.name.charAt(0) }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold truncate">{{ asset.name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono tracking-tight">{{ asset.asset_code }}</p>
                                    <div class="mt-2 text-[9px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-1 rounded-lg inline-flex items-center gap-1">
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Dihapus {{ new Date(asset.deleted_at).toLocaleDateString('id-ID') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <span class="text-[10px] text-gray-400 font-bold tracking-tight">{{ asset.subcategory?.name || '-' }}</span>
                                <div class="flex gap-2">
                                    <button @click="restoreAsset(asset)" class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold border border-emerald-100 uppercase tracking-tighter">
                                        Pulihkan
                                    </button>
                                    <button @click="forceDeleteAsset(asset)" class="p-2 text-rose-500 hover:text-rose-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="!assets.data.length" class="p-20 text-center bg-gray-50/20">
                            <p class="text-xs font-black text-gray-300 italic">Arsip belum memiliki data.</p>
                        </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-6 py-6 border-t border-gray-50 bg-gray-50/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 order-2 sm:order-1">
                             <span class="text-[10px] text-gray-400 font-bold tracking-tight text-center sm:text-left">Menampilkan {{ assets.from }}-{{ assets.to }} dari {{ assets.total }} arsip aset</span>
                        </div>

                        <nav v-if="assets.links.length > 3" class="flex gap-1.5 order-1 sm:order-2">
                            <template v-for="(link, k) in assets.links" :key="k">
                                <Link v-if="link.url" :href="link.url" 
                                    class="px-3.5 py-1.5 text-[10px] font-black rounded-lg transition-all"
                                    :class="link.active 
                                        ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' 
                                        : 'bg-white border border-gray-100 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600'"
                                    v-html="link.label" />
                                <span v-else class="px-3.5 py-1.5 text-[10px] font-black text-gray-200 bg-gray-50/50 rounded-lg" v-html="link.label"></span>
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

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
