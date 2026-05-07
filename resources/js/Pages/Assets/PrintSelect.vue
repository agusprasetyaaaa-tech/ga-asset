<script setup>
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
const perPage = ref(props.filters?.per_page || 10);
const categoryFilter = ref(props.filters?.category_id || '');
const subcategoryFilter = ref(props.filters?.subcategory_id || '');
const departmentFilter = ref(props.filters?.department_id || '');
const isLoading = ref(false);

const applyFilters = (params) => {
    router.get(route('assets.print-index'), {
        search: search.value,
        category_id: categoryFilter.value,
        subcategory_id: subcategoryFilter.value,
        department_id: departmentFilter.value,
        per_page: perPage.value,
        ...params
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
watch([search, categoryFilter, subcategoryFilter, departmentFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch(perPage, (val) => {
    isLoading.value = true;
    router.get(route('assets.print-index'), { ...props.filters, per_page: val }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['assets', 'filters'],
        onFinish: () => isLoading.value = false
    });
});

const selectedItems = ref([]);

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedItems.value = props.assets.data.map(i => i.id);
    } else {
        selectedItems.value = [];
    }
};

const printSelected = () => {
    if (selectedItems.value.length === 0) return;
    
    // Redirect to layout view with selected IDs
    window.location.href = route('assets.print', { ids: selectedItems.value.join(',') });
};

// Sorting logic
const toggleSort = () => {
    let currentDir = props.filters.dir || 'asc';
    let dir = currentDir === 'asc' ? 'desc' : 'asc';
    router.get(route('assets.print-index'), { ...props.filters, dir: dir }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['assets', 'filters']
    });
};

const filteredSubcategories = computed(() => {
    if (!categoryFilter.value) return props.subcategories;
    return props.subcategories.filter(s => s.category_id == categoryFilter.value);
});

</script>

<template>
    <Head title="Cetak Barcode" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Cetak Barcode Aset</h2>
                    <p class="text-xs text-gray-500 mt-1">Pilih aset untuk mencetak label QR Code format kertas A4.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-[600px] transition-opacity duration-300" :class="{'opacity-40 pointer-events-none': isLoading}">
                    <!-- Card Header / Filter Bar -->
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col gap-4 bg-gradient-to-r from-gray-50 to-white">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="h-10 w-10 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                </div>
                                <div class="hidden sm:block">
                                    <h3 class="text-sm font-bold text-gray-800">Pemilihan Aset</h3>
                                    <p class="text-[10px] text-gray-400 font-medium whitespace-nowrap">{{ assets.total || 0 }} total data</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 flex-grow sm:justify-end">
                                <button @click="printSelected" :disabled="selectedItems.length === 0"
                                    class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak Terpilih ({{ selectedItems.length }})
                                </button>
                            </div>
                        </div>

                        <!-- Complex Filters -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2">
                            <!-- Search -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input v-model="search" type="text" placeholder="Cari nama atau kode..." 
                                    class="w-full pl-8 pr-3 py-2 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                            </div>
                            
                            <!-- Categories Filter -->
                            <select v-model="categoryFilter" class="w-full text-[11px] font-medium rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 py-2 transition-all">
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>

                            <!-- Subcategories Filter -->
                            <select v-model="subcategoryFilter" class="w-full text-[11px] font-medium rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 py-2 transition-all" :disabled="!categoryFilter">
                                <option value="">Semua Subkategori</option>
                                <option v-for="sub in filteredSubcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>

                            <!-- Departments Filter -->
                            <select v-model="departmentFilter" class="w-full text-[11px] font-medium rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 py-2 transition-all">
                                <option value="">Semua Departemen</option>
                                <option v-for="dep in departments" :key="dep.id" :value="dep.id">{{ dep.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bulk Select All Indicator -->
                    <div v-if="assets.data?.length > 0" class="px-6 py-3 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 rounded-xl transition-all border"
                            :class="selectedItems.length > 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-gray-50/50 border-gray-100'">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" @change="toggleAll" :checked="selectedItems.length === assets.data?.length && assets.data?.length > 0"
                                        class="h-5 w-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 transition" />
                                    <span class="text-xs font-bold text-gray-600">Pilih Semua</span>
                                </label>
                                <span v-if="selectedItems.length > 0" class="h-4 w-[1px] bg-emerald-200 hidden sm:block"></span>
                                <span v-if="selectedItems.length > 0" class="text-xs font-extrabold text-emerald-700">{{ selectedItems.length }} dipilih</span>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="flex-grow overflow-x-auto min-h-[300px]">
                        <table class="min-w-full text-left">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-4 py-3 border-b border-gray-100 w-10 text-center"></th>
                                    <th class="px-2 py-3 border-b border-gray-100 w-10 text-center text-xs font-bold text-gray-500">No</th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-64 text-xs font-bold text-gray-500">Kode & Serial</th>
                                    <th @click="toggleSort" class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500 cursor-pointer hover:bg-emerald-50 select-none group transition-all">
                                        <div class="flex items-center gap-1.5">
                                            Identitas Aset
                                            <div class="flex flex-col -space-y-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                                <svg :class="{'text-emerald-600 opacity-100': filters.dir === 'asc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l-8 8h16l-8-8z"/></svg>
                                                <svg :class="{'text-emerald-600 opacity-100': filters.dir === 'desc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l8-8H4l8 8z"/></svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-48 text-xs font-bold text-gray-500">Kategori / Sub</th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-40 text-xs font-bold text-gray-500 whitespace-nowrap">Departemen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(item, index) in assets.data" :key="item.id" 
                                    class="hover:bg-emerald-50/30 transition-colors duration-150 cursor-pointer"
                                    :class="{'bg-emerald-50/50': selectedItems.includes(item.id)}"
                                    @click="(e) => { if(e.target.tagName !== 'INPUT') { const idx = selectedItems.indexOf(item.id); if (idx > -1) selectedItems.splice(idx, 1); else selectedItems.push(item.id); } }">
                                    
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="selectedItems" :value="item.id"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                    </td>
                                    <td class="px-2 py-4 text-center text-[11px] text-gray-400 font-bold tabular-nums">
                                        {{ (assets.current_page - 1) * assets.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-0.5 mt-0.5">
                                            <span class="font-mono text-sm font-bold text-emerald-700">{{ item.asset_code }}</span>
                                            <span class="text-[11px] text-gray-400 font-mono truncate max-w-[200px]" :title="item.serial_number">SN: {{ item.serial_number || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-sm text-gray-800">{{ item.name }}</div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ item.brand }} {{ item.model }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">{{ item.subcategory?.category?.name || 'Kategori' }}</span>
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100 max-w-max">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                {{ item.subcategory?.name || '-' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-100 max-w-max">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                            {{ item.department_rel?.name || item.department || '-' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!assets.data?.length">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                                <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <p class="text-xs font-semibold text-gray-400">Belum ada data aset</p>
                                            <p class="text-[10px] text-gray-300 mt-0.5">Gunakan filter lain atau tambah aset baru.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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

    </AuthenticatedLayout>
</template>
