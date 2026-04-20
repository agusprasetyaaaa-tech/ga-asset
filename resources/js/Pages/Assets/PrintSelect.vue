<script setup>
/**
 * Print Barcode Selection Page - Standardized UI
 * Matches Asset Index UI exactly for consistency.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    assets: Object,
    categories: Array,
    subcategories: Array,
    departments: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const categoryId = ref(props.filters?.category_id || '');
const subcategoryId = ref(props.filters?.subcategory_id || '');
const departmentId = ref(props.filters?.department_id || '');
const selectedIds = ref([]);
const isProcessing = ref(false);

const filteredSubcategories = computed(() => {
    if (!categoryId.value) return props.subcategories;
    return props.subcategories.filter(s => s.category_id == categoryId.value);
});

const applyFilters = () => {
    isProcessing.value = true;
    router.get(route('assets.print-index'), {
        search: search.value || undefined,
        category_id: categoryId.value || undefined,
        subcategory_id: subcategoryId.value || undefined,
        department_id: departmentId.value || undefined,
    }, { 
        preserveState: true, 
        replace: true,
        onFinish: () => isProcessing.value = false
    });
};

let searchTimeout = null;
watch([search, categoryId, subcategoryId, departmentId], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

const isAllSelected = computed(() => {
    return props.assets.data.length > 0 && selectedIds.value.length === props.assets.data.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.assets.data.map(a => a.id);
    }
};

const goToPrint = () => {
    if (selectedIds.value.length === 0) return;
    const ids = selectedIds.value.join(',');
    window.open(route('assets.print', { ids }), '_blank');
};

const getImageUrl = (path) => {
    if (!path) return null;
    return `/storage/${path}`;
};
</script>

<template>
    <Head title="Cetak Barcode" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-baseline sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('assets.index')"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-emerald-600 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Cetak Barcode</h2>
                        <p class="text-xs text-gray-500 font-medium">Pilih aset untuk mencetak label label format A4</p>
                    </div>
                </div>
                
                <button @click="goToPrint"
                    :disabled="selectedIds.length === 0"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-all active:scale-95 disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Cetak Terpilih ({{ selectedIds.length }})
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-300" :class="{'opacity-50': isProcessing}">
                    
                    <!-- Search & Filter Area -->
                    <div class="p-6 space-y-4">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="search" type="text" placeholder="Cari aset, serial, atau kode..." 
                                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50/50 border-gray-200 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 transition-all" />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <select v-model="categoryId" class="rounded-lg border-gray-200 text-xs font-bold text-gray-600 focus:ring-emerald-500 min-w-[160px]">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>

                            <select v-model="subcategoryId" class="rounded-lg border-gray-200 text-xs font-bold text-gray-600 focus:ring-emerald-500 min-w-[160px]">
                                <option value="">Semua Subkategori</option>
                                <option v-for="sub in filteredSubcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>

                            <select v-model="departmentId" class="rounded-lg border-gray-200 text-xs font-bold text-gray-600 focus:ring-emerald-500 min-w-[160px]">
                                <option value="">Semua Departemen</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                            </select>
                        </div>

                        <!-- Selection Bar -->
                        <div class="p-3 bg-gray-50/50 rounded-lg border border-gray-100 flex items-center justify-between">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                                    class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all" />
                                <span class="text-xs font-bold text-gray-500 group-hover:text-gray-700 transition-colors">Pilih Semua</span>
                            </label>
                            <span v-if="selectedIds.length > 0" class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded border border-emerald-100">{{ selectedIds.length }} Dipilih</span>
                        </div>
                    </div>

                    <!-- Table Desktop -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/30">
                                <tr>
                                    <th class="px-6 py-3 w-10"></th>
                                    <th class="px-2 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Asset Detail</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Identifikasi</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider text-center">Subkategori</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider text-center">Lokasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="(asset, index) in assets.data" :key="asset.id" 
                                    class="hover:bg-gray-50/50 transition-all group"
                                    :class="selectedIds.includes(asset.id) ? 'bg-emerald-50/30' : ''">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox" v-model="selectedIds" :value="asset.id"
                                            class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all" />
                                    </td>
                                    <td class="px-2 py-4 text-center text-xs font-bold text-gray-300 tabular-nums">
                                        {{ (assets.current_page - 1) * assets.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="h-10 w-10 flex-shrink-0 bg-gray-50 rounded-lg border border-gray-100 overflow-hidden transition-transform group-hover:scale-105">
                                                <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                                <div v-else class="h-full w-full flex items-center justify-center text-xs font-bold text-gray-300">{{ asset.name.charAt(0) }}</div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-bold text-gray-900 truncate">{{ asset.name }}</div>
                                                <div class="text-[10px] text-gray-400 font-bold uppercase">{{ asset.brand || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-gray-900 font-mono tracking-tight">{{ asset.asset_code || asset.barcode_code }}</div>
                                        <div class="text-[10px] text-gray-400 font-medium">SN: {{ asset.serial_number || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-xs font-bold text-gray-700 leading-tight">{{ asset.subcategory?.name || '-' }}</div>
                                        <div class="text-[9px] text-gray-400 font-bold">{{ asset.subcategory?.category?.name || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="inline-flex items-center gap-1 text-xs font-bold text-gray-500">
                                            <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ asset.location?.name || '-' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!assets.data.length">
                                    <td colspan="6" class="px-6 py-24 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="h-12 w-12 text-gray-100 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                            <p class="text-xs font-bold text-gray-300 italic">Data aset tidak ditemukan.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="lg:hidden divide-y divide-gray-50">
                        <div v-for="asset in assets.data" :key="asset.id" 
                            class="p-5 flex items-start gap-4 transition-all"
                            :class="selectedIds.includes(asset.id) ? 'bg-emerald-50/30' : 'bg-white'">
                            <input type="checkbox" v-model="selectedIds" :value="asset.id"
                                class="h-6 w-6 mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 shadow-sm" />
                            <div class="h-12 w-12 flex-shrink-0 bg-gray-50 rounded-lg border border-gray-100 overflow-hidden shadow-inner">
                                <img v-if="asset.photo" :src="getImageUrl(asset.photo)" class="h-full w-full object-cover" />
                                <span v-else class="h-full w-full flex items-center justify-center text-xs font-black text-gray-300">{{ asset.name.charAt(0) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-black text-gray-900 truncate tracking-tight">{{ asset.name }}</h4>
                                <p class="text-[10px] text-emerald-600 font-bold font-mono">{{ asset.asset_code || asset.barcode_code }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5 font-bold uppercase text-[9px]">
                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded">{{ asset.subcategory?.name || '-' }}</span>
                                    <span class="px-1.5 py-0.5 bg-white border border-gray-100 text-gray-400 rounded">{{ asset.location?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/20 flex flex-col items-center justify-between gap-4 md:flex-row">
                        <span class="text-[10px] text-gray-400 font-bold italic tracking-wide">Menampilkan {{ assets.from }}-{{ assets.to }} dari {{ assets.total }} aset</span>

                        <nav v-if="assets.links.length > 3" class="flex gap-1.5">
                            <template v-for="(link, k) in assets.links" :key="k">
                                <Link v-if="link.url" :href="link.url" 
                                    class="px-3 py-1.5 text-[10px] font-bold rounded shadow-sm border transition-all"
                                    :class="link.active 
                                        ? 'bg-emerald-600 border-emerald-600 text-white shadow-emerald-200' 
                                        : 'bg-white border-gray-100 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600'"
                                    v-html="link.label" />
                                <span v-else class="px-3 py-1.5 text-[10px] font-bold text-gray-200 bg-white border border-transparent rounded grayscale cursor-not-allowed opacity-50" v-html="link.label"></span>
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Button Mobile -->
        <Transition
            enter-active-class="transition ease-out duration-300 transform"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200 transform"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div v-if="selectedIds.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-[calc(100%-2.5rem)] max-w-sm sm:hidden pointer-events-none">
                <button @click="goToPrint"
                    class="pointer-events-auto w-full flex items-center justify-between gap-4 rounded-xl bg-emerald-600 p-4 text-white shadow-2xl shadow-emerald-700/40 hover:bg-emerald-700 transition-all active:scale-95 border border-emerald-500">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/10 p-2 rounded-lg">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        </div>
                        <div class="text-left leading-tight">
                            <p class="text-[9px] font-black opacity-70 uppercase tracking-widest">Siap Cetak</p>
                            <p class="text-sm font-black">{{ selectedIds.length }} Terpilih</p>
                        </div>
                    </div>
                    <div class="bg-white text-emerald-600 px-4 py-2 rounded-lg text-[10px] font-black shadow-sm uppercase">Cetak</div>
                </button>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
