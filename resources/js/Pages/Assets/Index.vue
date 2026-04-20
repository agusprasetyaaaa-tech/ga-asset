<template>
    <Head title="Manajemen Aset" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Aset</h2>
                    <p class="text-xs text-gray-500 mt-1 font-bold">Katalog Inventaris Perusahaan</p>
                </div>
            </div>
        </template>

        <div class="py-6 min-h-screen bg-gray-50/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all">
                    
                    <!-- Search & Bulk Action Header -->
                    <div class="p-4 md:p-6 border-b border-gray-100 space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Search Input -->
                            <div class="relative flex-grow max-w-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="search" type="text" 
                                    placeholder="Cari aset, serial, atau kode..."
                                    class="block w-full pl-10 pr-4 py-2.5 bg-gray-50/50 border-gray-100 rounded-xl text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all border" />
                            </div>

                            <!-- Permanent Actions -->
                            <div class="flex items-center gap-2">
                                <Link :href="route('assets.trash')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gray-50 border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100 transition shadow-sm">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    Arsip
                                </Link>
                                <button @click="openCreateModal"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm shadow-emerald-100 hover:bg-emerald-700 active:scale-95 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah
                                </button>
                            </div>
                        </div>

                        <!-- Secondary Filters -->
                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <div class="relative w-full sm:w-44">
                                <select v-model="statusFilter" class="w-full rounded-xl border-gray-100 bg-gray-50/50 text-xs font-bold text-gray-500 py-2 pl-3 pr-10 focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 appearance-none transition border">
                                    <option value="">Semua Status</option>
                                    <option value="available">Tersedia</option>
                                    <option value="in_use">Digunakan</option>
                                    <option value="maintenance">Perbaikan</option>
                                    <option value="damaged">Rusak</option>
                                </select>
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-3 w-3 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <div class="relative w-full sm:w-56">
                                <select v-model="subcategoryIdFilter" class="w-full rounded-xl border-gray-100 bg-gray-50/50 text-xs font-bold text-gray-500 py-2 pl-3 pr-10 focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 appearance-none transition border">
                                    <option value="">Semua Subkategori</option>
                                    <option v-for="sub in subcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                </select>
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-3 w-3 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                             </div>
                        </div>

                        <!-- Bulk Action & Select All (Responsive) -->
                        <div v-if="assets.data.length > 0" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 rounded-xl transition-all border"
                            :class="selectedAssets.length > 0 ? 'bg-rose-50 border-rose-100' : 'bg-gray-50/50 border-gray-100'">
                             
                             <div class="flex items-center gap-3">
                                 <label class="flex items-center gap-2 cursor-pointer select-none">
                                     <input type="checkbox" @change="toggleAll" :checked="selectedAssets.length === assets.data.length && assets.data.length > 0"
                                         class="h-5 w-5 rounded-lg border-gray-200 text-emerald-600 focus:ring-emerald-500 transition" />
                                     <span class="text-xs font-bold text-gray-600">Pilih Semua</span>
                                 </label>
                                 <span v-if="selectedAssets.length > 0" class="h-4 w-[1px] bg-rose-200 hidden sm:block"></span>
                                 <span v-if="selectedAssets.length > 0" class="text-xs font-extrabold text-rose-700">{{ selectedAssets.length }} dipilih</span>
                             </div>

                             <button v-if="selectedAssets.length > 0" @click="bulkDelete" 
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black shadow-sm active:scale-95 transition">
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
                                    <th class="px-4 py-4 text-center w-10"></th>
                                    <th scope="col" class="px-2 py-4 text-center text-[10px] font-black text-gray-400 w-10">No</th>
                                    <th scope="col" @click="toggleSort('detail')" class="px-6 py-4 text-left text-[11px] font-black text-gray-700 cursor-pointer group hover:bg-emerald-50/50 transition-all select-none border-b border-transparent">
                                        Asset Detail
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-gray-700">Identifikasi</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-gray-700 w-32 hidden lg:table-cell">Subkategori</th>
                                    <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-gray-700 w-28">Status</th>
                                    <th scope="col" @click="toggleSort('nilai')" class="px-6 py-4 text-left text-[11px] font-black text-gray-700 cursor-pointer w-40 border-b border-transparent">Nilai Aset</th>
                                    <th scope="col" class="px-6 py-4 text-right text-[11px] font-black text-gray-700 w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="(asset, index) in assets.data" :key="asset.id" class="hover:bg-emerald-50/10 transition-colors">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="selectedAssets" :value="asset.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600 cursor-pointer" />
                                    </td>
                                    <td class="px-2 py-4 text-xs font-bold text-gray-400 text-center">{{ (assets.current_page - 1) * assets.per_page + index + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                                                <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                                <span v-else class="text-xs font-bold text-gray-300">{{ asset.name.charAt(0) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <Link :href="route('assets.show', asset.id)" class="text-sm font-bold text-gray-900 hover:text-emerald-600 truncate block">{{ asset.name }}</Link>
                                                <div class="text-[10px] text-gray-400 font-bold uppercase">{{ asset.subcategory?.name || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-gray-900">{{ asset.asset_code || asset.barcode_code }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">SN: {{ asset.serial_number || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <div class="flex flex-col">
                                            <span class="text-[11px] font-bold text-gray-900 leading-tight">{{ asset.subcategory?.name || '-' }}</span>
                                            <span class="text-[9px] font-bold text-gray-400 tracking-tight">{{ asset.subcategory?.category?.name || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center scale-90">
                                        <StatusBadge :status="asset.status" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-bold text-gray-900">{{ formatCurrency(asset.purchase_price) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                        <button v-if="asset.status === 'available'" @click="openReturnModal(asset)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Serahkan ke User"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg></button>
                                        <button @click="openEditModal(asset)" class="p-1.5 text-gray-400 hover:text-emerald-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                        <button @click="deleteAsset(asset)" class="p-1.5 text-gray-400 hover:text-red-500"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

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
                                        <p class="text-[10px] text-gray-400 font-mono">{{ asset.asset_code || asset.barcode_code }}</p>
                                    </div>
                                </div>
                                <input type="checkbox" v-model="selectedAssets" :value="asset.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600" />
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
                                    <button @click="openEditModal(asset)" class="p-1.5 text-gray-400 hover:text-emerald-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button @click="deleteAsset(asset)" class="p-1.5 text-gray-400 hover:text-red-500"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="!assets.data.length" class="p-16 text-center text-xs text-gray-400 font-bold bg-white">Belum ada data aset.</div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-4 md:px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
                        <div class="flex items-center gap-3">
                             <div class="relative flex items-center">
                                <select v-model="perPage" class="appearance-none bg-emerald-50 text-[10px] font-bold text-emerald-700 border-none rounded-lg py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-emerald-500/10 cursor-pointer shadow-sm">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option value="all">SEMUA</option>
                                </select>
                                <svg class="absolute right-2.5 h-3 w-3 text-emerald-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <span class="text-[10px] text-gray-400 font-bold tracking-tight">Menampilkan {{ assets.from }}-{{ assets.to }} dari {{ assets.total }} aset</span>
                        </div>

                        <nav v-if="assets.links.length > 3" class="flex gap-1.5">
                            <template v-for="(link, k) in assets.links" :key="k">
                                <Link v-if="link.url" :href="link.url" 
                                    class="px-3 py-1.5 text-[10px] font-bold rounded-lg border transition shadow-sm"
                                    :class="link.active ? 'bg-emerald-600 border-emerald-600 text-white shadow-emerald-100' : 'bg-white border-gray-100 text-gray-500 hover:border-emerald-200'"
                                    v-html="link.label" />
                                <span v-else class="px-3 py-1.5 text-[10px] font-bold text-gray-300 border border-gray-50 rounded-lg bg-gray-50/50" v-html="link.label"></span>
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Modal Reset -->
        <ConfirmModal 
            :show="showConfirmModal"
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            :confirm-text="confirmData.confirmText"
            @close="showConfirmModal = false"
            @confirm="confirmData.onConfirm"
        />

        <!-- Return to User Modal (Simplified & Themed) -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" leave-active-class="transition duration-150">
                <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100">
                        <div class="px-6 py-5 border-b border-gray-50 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-gray-900 leading-tight">Serahkan ke User</h3>
                            <button @click="showReturnModal = false" class="text-gray-400 hover:text-gray-600 transition"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                        </div>
                        <div class="px-6 py-5 space-y-4 bg-white">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1.5">Nama Pengguna</label>
                                <input v-model="returnForm.user_name" type="text" placeholder="Masukkan nama penerima..." class="w-full rounded-xl border-gray-100 bg-gray-50/50 py-2.5 text-sm focus:ring-emerald-500/10 focus:border-emerald-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-1.5">Lokasi Penempatan</label>
                                <select v-model="returnForm.to_location_id" class="w-full rounded-xl border-gray-100 bg-gray-50/50 py-2.5 text-sm focus:ring-emerald-500/10 focus:border-emerald-500">
                                    <option value="">-- Tetap di lokasi saat ini --</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                            <button @click="showReturnModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-white transition">Batal</button>
                            <button @click="submitReturnToUser" :disabled="isReturning" class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-emerald-100 hover:bg-emerald-700 transition">Ya, Serahkan</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <AssetFormModal 
            :show="showAssetModal" :asset="editingAsset" :locations="locations" :categories="categories" :subcategories="subcategories"
            :departments="departments" :vendors="vendors" @close="showAssetModal = false" 
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import AssetFormModal from '@/Components/Inventory/AssetFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
const isProcessing = ref(false);
const selectedAssets = ref([]);
const showAssetModal = ref(false);
const editingAsset = ref(null);
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', type: 'danger', confirmText: 'Ya, Hapus', onConfirm: () => {} });

let searchTimeout = null;

const applyFilters = () => {
    isProcessing.value = true;
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
        onFinish: () => isProcessing.value = false
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([statusFilter, subcategoryIdFilter, perPage], () => {
    selectedAssets.value = [];
    applyFilters();
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
        title: 'Hapus Semua Aset',
        message: `Apakah Anda yakin ingin menghapus ${selectedAssets.value.length} aset yang dipilih?`,
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
        message: `Hapus aset "${asset.name}"?`,
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
const returnForm = ref({ user_name: '', to_location_id: '', notes: '' });
const isReturning = ref(false);

const openReturnModal = (asset) => {
    returningAsset.value = asset;
    returnForm.value = { user_name: asset.current_holder || '', to_location_id: asset.location_id || '', notes: '' };
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
</script>
