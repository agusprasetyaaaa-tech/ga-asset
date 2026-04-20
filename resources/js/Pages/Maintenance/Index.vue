<script setup>
/**
 * Maintenance Index Page
 * Lists all maintenance logs with filtering by type, sorting, and bulk actions.
 * Enhanced with Responsive Card View for Mobile.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import MaintenanceFormModal from '@/Components/Inventory/MaintenanceFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    logs: Object,
    assets: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const perPage = ref(props.filters?.per_page || 10);
const isProcessing = ref(false);

// Selection & Bulk Actions
const selectedLogs = ref([]);

// Modal state
const showModal = ref(false);
const editingLog = ref(null);

// Global Confirmation Modal State
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', type: 'danger', onConfirm: () => {} });

let searchTimeout = null;

const applyFilters = () => {
    isProcessing.value = true;
    router.get(route('maintenance.index'), {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        per_page: perPage.value || undefined,
        sort: props.filters?.sort,
        dir: props.filters?.dir,
    }, { 
        preserveState: true, 
        replace: true,
        only: ['logs', 'filters'],
        onFinish: () => isProcessing.value = false
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([typeFilter, perPage], () => {
    selectedLogs.value = []; // Reset selection
    applyFilters();
});

// Sorting Logic
const toggleSort = (field) => {
    let dir = (props.filters.sort === field && props.filters.dir === 'asc') ? 'desc' : 'asc';
    router.get(route('maintenance.index'), {
        ...props.filters,
        sort: field,
        dir: dir
    }, {
        preserveState: true,
        only: ['logs', 'filters']
    });
};

// Selection Helpers
const toggleAll = (e) => {
    if (e.target.checked) {
        selectedLogs.value = props.logs.data.map(l => l.id);
    } else {
        selectedLogs.value = [];
    }
};

const bulkDelete = () => {
    confirmData.value = {
        title: 'Hapus Semua Log Pemeliharaan',
        message: `Apakah Anda yakin ingin menghapus ${selectedLogs.value.length} catatan pemeliharaan yang dipilih?`,
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: () => {
            router.post(route('maintenance.bulk-delete'), { ids: selectedLogs.value }, {
                onSuccess: () => {
                    selectedLogs.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const openCreate = () => {
    editingLog.value = null;
    showModal.value = true;
};

const openEdit = (log) => {
    editingLog.value = log;
    showModal.value = true;
};

const deleteLog = (log) => {
    confirmData.value = {
        title: 'Hapus Log Maintenance',
        message: 'Apakah Anda yakin ingin menghapus catatan pemeliharaan ini?',
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: () => {
            router.delete(route('maintenance.destroy', log.id), { 
                preserveScroll: true,
                onSuccess: () => showConfirmModal.value = false
            });
        }
    };
    showConfirmModal.value = true;
};

const completeMaintenance = (log) => {
    confirmData.value = {
        title: 'Selesaikan Maintenance',
        message: 'Apakah Anda yakin ingin menyelesaikan pemeliharaan ini sekarang?',
        type: 'success',
        confirmText: 'Ya, Selesaikan',
        onConfirm: () => {
            router.patch(route('maintenance.complete', log.id), {}, { 
                preserveScroll: true,
                onSuccess: () => showConfirmModal.value = false
            });
        }
    };
    showConfirmModal.value = true;
};

const typeLabel = (type) => {
    const map = { repair: 'Perbaikan', replacement: 'Pergantian Suku Cadang', inspection: 'Inspeksi' };
    return map[type] || type;
};

const typeColor = (type) => {
    const map = {
        repair: 'bg-red-50 text-red-700 border-red-100',
        replacement: 'bg-purple-50 text-purple-700 border-purple-100',
        inspection: 'bg-blue-50 text-blue-700 border-blue-100',
    };
    return map[type] || 'bg-gray-50 text-gray-700 border-gray-100';
};

const typeAccent = (type) => {
    const map = {
        repair: 'bg-red-500',
        replacement: 'bg-purple-500',
        inspection: 'bg-blue-500',
    };
    return map[type] || 'bg-gray-500';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};

const formatCurrency = (val) => {
    if (!val || val == 0) return '-';
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR', 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(val);
};
</script>

<template>
    <Head title="Manajemen Pemeliharaan" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight">Manajemen Pemeliharaan</h2>
                <p class="text-[11px] md:text-xs text-gray-500 max-w-2xl leading-relaxed">Pantau riwayat perbaikan, servis berkala, dan optimalkan performa aset melalui pencatatan pemeliharaan yang terintegrasi.</p>
            </div>
        </template>

        <div class="py-4 md:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300" :class="{'opacity-40 pointer-events-none': isProcessing}">
                    
                    <!-- Search & Filter Bar (Optimized for Mobile) -->
                    <div class="p-4 md:p-6 border-b border-gray-50 space-y-4">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <!-- Left: Search Box -->
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="search" type="text" 
                                    placeholder="Cari aset, kode, vendor..."
                                    class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 border-transparent rounded-xl text-sm placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all border border-gray-100" />
                            </div>

                            <!-- Right: Actions & Filters -->
                            <div class="flex items-center gap-2">
                                <select v-model="typeFilter"
                                    class="flex-grow md:flex-none md:w-48 rounded-xl border-gray-100 bg-gray-50 text-sm py-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                    <option value="">Semua Tipe</option>
                                    <option value="repair">Perbaikan (Repair)</option>
                                    <option value="replacement">Pergantian</option>
                                    <option value="inspection">Inspeksi</option>
                                </select>

                                <button @click="openCreate"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-100 hover:bg-emerald-700 hover:scale-[1.02] active:scale-[0.98] transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    <span class="hidden sm:inline">Tambah</span>
                                </button>
                            </div>
                        </div>

                        <!-- Bulk Delete & Select All Indicator (Responsive) -->
                        <div v-if="logs.data.length > 0" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 rounded-xl transition-all border"
                            :class="selectedLogs.length > 0 ? 'bg-rose-50 border-rose-100' : 'bg-gray-50/50 border-gray-100'">
                             
                             <div class="flex items-center gap-3">
                                 <!-- Mobile Select All Checkbox -->
                                 <label class="flex items-center gap-2 cursor-pointer select-none">
                                     <input type="checkbox" @change="toggleAll" :checked="selectedLogs.length === logs.data.length && logs.data.length > 0"
                                         class="h-5 w-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 transition" />
                                     <span class="text-xs font-bold text-gray-600">Pilih Semua</span>
                                 </label>
                                 <span v-if="selectedLogs.length > 0" class="h-4 w-[1px] bg-rose-200 hidden sm:block"></span>
                                 <span v-if="selectedLogs.length > 0" class="text-xs font-extrabold text-rose-700">{{ selectedLogs.length }} dipilih</span>
                             </div>

                             <button v-if="selectedLogs.length > 0" @click="bulkDelete" 
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black shadow-sm shadow-rose-100 active:scale-95 transition-all">
                                 <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                 Hapus Semua
                             </button>
                        </div>
                    </div>

                    <!-- DESKTOP VIEW: Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-4 py-4 text-center w-10">
                                        <input type="checkbox" @change="toggleAll" :checked="selectedLogs.length === logs.data.length && logs.data.length > 0"
                                            class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                    </th>
                                    <th scope="col" class="px-2 py-4 text-center text-[10px] font-bold text-gray-500 w-10 hidden sm:table-cell">No</th>
                                    <th scope="col" @click="toggleSort('asset')" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 cursor-pointer group hover:bg-emerald-50/50 transition-all select-none">
                                        <div class="flex items-center gap-1.5 uppercase tracking-wider">Asset Detail <svg class="h-3 w-3 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg></div>
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Deskripsi</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Vendor</th>
                                    <th scope="col" @click="toggleSort('cost')" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 cursor-pointer group hover:bg-emerald-50/50 transition-all select-none uppercase tracking-wider">Biaya</th>
                                    <th scope="col" class="px-6 py-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Tanggal</th>
                                    <th scope="col" class="px-6 py-4 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="(log, idx) in logs.data" :key="log.id" class="hover:bg-emerald-50/10 transition-colors duration-150">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="selectedLogs" :value="log.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600 transition" />
                                    </td>
                                    <td class="px-2 py-4 text-center text-xs text-gray-400 font-mono hidden sm:table-cell">
                                        {{ (logs.current_page - 1) * logs.per_page + idx + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                                                <img v-if="log.asset?.photo" :src="`/storage/${log.asset.photo}`" class="h-full w-full object-cover" />
                                                <span v-else class="text-xs font-bold text-gray-300">{{ log.asset?.name.charAt(0) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <Link :href="route('assets.show', log.asset?.id || '#')" class="text-sm font-bold text-gray-900 hover:text-emerald-600 transition-colors block truncate">{{ log.asset?.name }}</Link>
                                                <div class="text-[10px] text-gray-400 font-mono">{{ log.asset?.asset_code || log.asset?.barcode_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell">
                                        <div class="max-w-[150px]">
                                            <span :class="typeColor(log.type)" class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border mb-1 inline-block">{{ typeLabel(log.type) }}</span>
                                            <p class="text-xs text-gray-500 truncate italic">{{ log.description }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="text-xs font-semibold text-gray-600">{{ log.technician || '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-sm text-gray-900 tabular-nums">{{ formatCurrency(log.cost) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <StatusBadge :status="log.status === 'selesai' ? 'available' : 'maintenance'" :custom-label="log.status" class="!py-0.5 !px-2 !text-[9px]" />
                                    </td>
                                    <td class="px-6 py-4 text-center hidden sm:table-cell">
                                        <span class="text-[11px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded">{{ formatDate(log.created_at) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                        <button v-if="log.status === 'proses'" @click="completeMaintenance(log)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Selesaikan"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></button>
                                        <button @click="openEdit(log)" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                        <button @click="deleteLog(log)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </td>
                                </tr>
                                <tr v-if="!logs.data.length">
                                    <td colspan="9" class="p-20 text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest bg-white">Belum ada data pemeliharaan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE VIEW: Original Card List (Restore) -->
                    <div class="md:hidden divide-y divide-gray-50">
                        <div v-for="log in logs.data" :key="log.id" class="p-4 bg-white space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                        <img v-if="log.asset?.photo" :src="`/storage/${log.asset.photo}`" class="h-full w-full object-cover" />
                                        <span v-else class="text-xs font-bold text-gray-300">{{ log.asset?.name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">{{ log.asset?.name }}</h4>
                                        <p class="text-[10px] text-gray-400 font-mono">{{ log.asset?.asset_code || log.asset?.barcode_code }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                     <input type="checkbox" v-model="selectedLogs" :value="log.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div class="p-2 bg-gray-50 rounded-lg border border-gray-100">
                                    <span class="text-gray-400 block mb-0.5">Biaya</span>
                                    <span class="font-bold text-gray-900 tabular-nums">{{ formatCurrency(log.cost) }}</span>
                                </div>
                                <div class="p-2 bg-gray-50 rounded-lg border border-gray-100">
                                    <span class="text-gray-400 block mb-0.5">Status</span>
                                    <StatusBadge :status="log.status === 'selesai' ? 'available' : 'maintenance'" :custom-label="log.status" class="!py-0.5 !px-2 !text-[9px]" />
                                </div>
                            </div>

                            <!-- Job Description -->
                            <div v-if="log.description" class="bg-gray-50/50 p-2.5 rounded-lg border border-dashed border-gray-100">
                                <span class="text-[9px] text-gray-400 font-bold uppercase block mb-0.5">Deskripsi Pekerjaan</span>
                                <p class="text-[11px] text-gray-600 leading-relaxed italic line-clamp-2">"{{ log.description }}"</p>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <div class="text-[10px] text-gray-400 font-bold italic">{{ formatDate(log.created_at) }}</div>
                                <div class="flex gap-2">
                                    <button v-if="log.status === 'proses'" @click="completeMaintenance(log)" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-100">Selesaikan</button>
                                    <button @click="openEdit(log)" class="p-1.5 text-gray-400 hover:text-emerald-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                    <button @click="deleteLog(log)" class="p-1.5 text-gray-400 hover:text-red-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="!logs.data.length" class="p-12 text-center text-xs text-gray-400 font-bold uppercase tracking-widest bg-white">Belum ada data pemeliharaan.</div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-4 md:px-6 py-4 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                        <div class="flex items-center gap-3">
                             <div class="relative flex items-center">
                                <select v-model="perPage" class="appearance-none bg-emerald-50 text-[10px] font-bold text-emerald-700 border-none rounded-lg py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-emerald-500/20 cursor-pointer transition-all">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option value="all">SEMUA</option>
                                </select>
                                <svg class="absolute right-2.5 h-3 w-3 text-emerald-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <span class="text-[10px] text-gray-400 font-bold">Total: {{ logs.total }}</span>
                        </div>

                        <nav v-if="logs.links.length > 3" class="flex gap-1 overflow-x-auto pb-1 max-w-full">
                            <template v-for="(link, k) in logs.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg transition-all" :class="link.active ? 'bg-emerald-600 text-white shadow-md' : 'text-gray-400 hover:bg-emerald-50 hover:text-emerald-600'" v-html="link.label" />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <MaintenanceFormModal :show="showModal" :assets="assets" :maintenance-log="editingLog" @close="showModal = false" />

        <ConfirmModal 
            :show="showConfirmModal"
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            :confirm-text="confirmData.confirmText"
            @close="showConfirmModal = false"
            @confirm="confirmData.onConfirm"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.slide-in-top { animation: slide-in-top 0.2s cubic-bezier(0.250, 0.460, 0.450, 0.940) both; }
@keyframes slide-in-top { 0% { transform: translateY(-10px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
</style>
