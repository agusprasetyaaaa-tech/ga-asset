<script setup>
/**
 * Categories & Subcategories Index Page
 * Professional Enterprise Design with Green Action Buttons
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    categories: Object,
    subcategories: Object,
    filters: Object,
});

const catSearch = ref(props.filters?.cat_search || '');
const subSearch = ref(props.filters?.sub_search || '');
const catPerPage = ref(props.filters?.cat_per_page || 10);
const subPerPage = ref(props.filters?.sub_per_page || 10);
const isCatLoading = ref(false);
const isSubLoading = ref(false);

const applyFilters = (params) => {
    router.get(route('categories.index'), {
        cat_search: catSearch.value,
        sub_search: subSearch.value,
        cat_per_page: catPerPage.value,
        sub_per_page: subPerPage.value,
        ...params
    }, { preserveState: true, replace: true });
};

let catTimeout = null;
watch(catSearch, (val) => {
    clearTimeout(catTimeout);
    catTimeout = setTimeout(() => applyFilters(), 400);
});

let subTimeout = null;
watch(subSearch, (val) => {
    clearTimeout(subTimeout);
    subTimeout = setTimeout(() => applyFilters(), 400);
});

watch([catPerPage, subPerPage], () => applyFilters());

const showCatModal = ref(false);
const editingCat = ref(null);
const catForm = useForm({ name: '', description: '' });

const openCreateCat = () => { editingCat.value = null; catForm.reset(); showCatModal.value = true; };
const openEditCat = (cat) => { editingCat.value = cat; catForm.name = cat.name; catForm.description = cat.description; showCatModal.value = true; };
const submitCat = () => editingCat.value ? catForm.put(route('categories.update', editingCat.value.id), { onSuccess: () => showCatModal.value = false }) : catForm.post(route('categories.store'), { onSuccess: () => showCatModal.value = false });
const showSubModal = ref(false);
const editingSub = ref(null);
const subForm = useForm({ category_id: '', name: '', prefix: '', managing_dept: '', description: '' });

// Bulk selection state
const selectedCats = ref([]);
const selectedSubs = ref([]);

// Watchers for Pagination Entries
watch(catPerPage, (val) => {
    selectedCats.value = []; // Reset selection on page change
    isCatLoading.value = true;
    router.get(route('categories.index'), { ...props.filters, cat_per_page: val }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['categories', 'filters'],
        onFinish: () => isCatLoading.value = false
    });
});

watch(subPerPage, (val) => {
    selectedSubs.value = []; // Reset selection on page change
    isSubLoading.value = true;
    router.get(route('categories.index'), { ...props.filters, sub_per_page: val }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['subcategories', 'filters'],
        onFinish: () => isSubLoading.value = false
    });
});

// Bulk Select Helpers
const toggleAllCats = (e) => {
    if (e.target.checked) {
        selectedCats.value = props.categories.data.map(c => c.id);
    } else {
        selectedCats.value = [];
    }
};

const toggleAllSubs = (e) => {
    if (e.target.checked) {
        selectedSubs.value = props.subcategories.data.map(s => s.id);
    } else {
        selectedSubs.value = [];
    }
};

const bulkDeleteCats = () => {
    confirmData.value = {
        title: 'Hapus Massal Kategori',
        message: `Hapus ${selectedCats.value.length} kategori yang dipilih?`,
        type: 'danger',
        onConfirm: () => {
            router.post(route('categories.bulk-delete'), { ids: selectedCats.value }, {
                onSuccess: () => {
                    selectedCats.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const bulkDeleteSubs = () => {
    confirmData.value = {
        title: 'Hapus Massal Subkategori',
        message: `Hapus ${selectedSubs.value.length} subkategori yang dipilih?`,
        type: 'danger',
        onConfirm: () => {
            router.post(route('subcategories.bulk-delete-sub'), { ids: selectedSubs.value }, {
                onSuccess: () => {
                    selectedSubs.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

// Sorting logic
const toggleSortCat = () => {
    let currentDir = props.filters.cat_dir || 'asc';
    let dir = currentDir === 'asc' ? 'desc' : 'asc';
    router.get(route('categories.index'), { ...props.filters, cat_dir: dir }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['categories', 'filters']
    });
};

const toggleSortSub = () => {
    let currentDir = props.filters.sub_dir || 'asc';
    let dir = currentDir === 'asc' ? 'desc' : 'asc';
    router.get(route('categories.index'), { ...props.filters, sub_dir: dir }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['subcategories', 'filters']
    });
};

// Global Confirmation Modal State
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', type: 'danger', onConfirm: () => {} });

const openCreateSub = () => { editingSub.value = null; subForm.reset(); if (props.categories.data.length) subForm.category_id = props.categories.data[0].id; showSubModal.value = true; };
const openEditSub = (sub) => { editingSub.value = sub; subForm.category_id = sub.category_id; subForm.name = sub.name; subForm.prefix = sub.prefix || ''; subForm.managing_dept = sub.managing_dept || ''; subForm.description = sub.description; showSubModal.value = true; };
const submitSub = () => editingSub.value ? subForm.put(route('subcategories.update', editingSub.value.id), { onSuccess: () => showSubModal.value = false }) : subForm.post(route('subcategories.store'), { onSuccess: () => showSubModal.value = false });

const deleteCat = (cat) => {
    confirmData.value = {
        title: 'Hapus Kategori',
        message: `Apakah Anda yakin ingin menghapus kategori "${cat.name}"? Semua data yang terkait mungkin akan terpengaruh.`,
        type: 'danger',
        onConfirm: () => {
            catForm.delete(route('categories.destroy', cat.id), {
                onSuccess: () => showConfirmModal.value = false
            });
        }
    };
    showConfirmModal.value = true;
};

const deleteSub = (sub) => {
    confirmData.value = {
        title: 'Hapus Subkategori',
        message: `Apakah Anda yakin ingin menghapus subkategori "${sub.name}"? Aset yang terkait dengan subkategori ini tidak dapat dihapus jika masih ada.`,
        type: 'danger',
        onConfirm: () => {
            subForm.delete(route('subcategories.destroy', sub.id), {
                onSuccess: () => showConfirmModal.value = false
            });
        }
    };
    showConfirmModal.value = true;
};
</script>

<template>
    <Head title="Kategori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Kategori</h2>
                    <p class="text-xs text-gray-500 mt-1">Kelola kategori dan subkategori untuk klasifikasi aset perusahaan.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    
                    <!-- KATEGORI UTAMA CARD -->
                    <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-opacity duration-300" :class="{'opacity-40 pointer-events-none': isCatLoading}">
                        <!-- Card Header -->
                        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="h-9 w-9 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                                    <svg class="h-4.5 w-4.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                </div>
                                <div class="hidden sm:block">
                                    <h3 class="text-sm font-bold text-gray-800">Kategori</h3>
                                    <p class="text-[10px] text-gray-400 font-medium">{{ categories.total || 0 }} total</p>
                                </div>
                            </div>
                            
                            <!-- Search & Actions -->
                            <div class="flex items-center gap-2 flex-grow sm:justify-end">
                                <div class="relative flex-grow max-w-[200px]">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input v-model="catSearch" type="text" placeholder="Cari..." 
                                        class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                                </div>
                                <button v-if="selectedCats.length > 0" @click="bulkDeleteCats" 
                                    class="bg-rose-50 text-rose-600 border border-rose-100 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-rose-600 hover:text-white transition-all flex items-center gap-1.5 shadow-sm uppercase shrink-0">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus ({{ selectedCats.length }})
                                </button>
                                <button @click="openCreateCat" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm shrink-0">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tambah
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="flex-grow overflow-x-auto min-h-[300px]">
                            <table class="min-w-full text-left">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th class="px-4 py-3 border-b border-gray-100 w-10 text-center">
                                            <input type="checkbox" @change="toggleAllCats" :checked="selectedCats.length === categories.data.length && categories.data.length > 0"
                                                class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                        </th>
                                        <th class="px-2 py-3 border-b border-gray-100 w-10 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">No</th>
                                        <th @click="toggleSortCat" class="px-6 py-3 border-b border-gray-100 text-[10px] font-bold text-gray-400 uppercase tracking-widest cursor-pointer hover:bg-emerald-50 select-none group transition-all">
                                            <div class="flex items-center gap-1.5">
                                                Detail Kategori
                                                <div class="flex flex-col -space-y-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                                    <svg :class="{'text-emerald-600 opacity-100': filters.cat_dir === 'asc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l-8 8h16l-8-8z"/></svg>
                                                    <svg :class="{'text-emerald-600 opacity-100': filters.cat_dir === 'desc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l8-8H4l8 8z"/></svg>
                                                </div>
                                            </div>
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-100 text-center w-16 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Sub</th>
                                        <th class="px-6 py-3 border-b border-gray-100 text-right w-24 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="(cat, index) in categories.data" :key="cat.id" 
                                        class="hover:bg-emerald-50/30 transition-colors duration-150"
                                        :class="{'bg-emerald-50/50': selectedCats.includes(cat.id)}">
                                        <td class="px-4 py-4 text-center">
                                            <input type="checkbox" v-model="selectedCats" :value="cat.id"
                                                class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                        </td>
                                        <td class="px-2 py-4 text-center text-[11px] text-gray-400 font-bold tabular-nums">
                                            {{ (categories.current_page - 1) * categories.per_page + index + 1 }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="min-w-0">
                                                <div class="font-semibold text-sm text-gray-800 truncate">{{ cat.name }}</div>
                                                <div class="text-[10px] text-gray-400 leading-tight truncate max-w-[200px]">{{ cat.description || 'Tidak ada deskripsi' }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center justify-center h-6 min-w-[24px] px-1.5 text-[10px] font-bold rounded-full"
                                                :class="cat.subcategories_count > 0 ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-gray-400 bg-gray-50 border border-gray-100'">
                                                {{ cat.subcategories_count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-1">
                                                <button @click="openEditCat(cat)" class="p-1.5 text-gray-400 hover:text-amber-600 rounded-md hover:bg-amber-50 transition-all duration-150" title="Edit">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="deleteCat(cat)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-all duration-150" title="Hapus">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!categories.data.length">
                                        <td colspan="4" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                                    <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-xs font-semibold text-gray-400">Belum ada kategori</p>
                                                <p class="text-[10px] text-gray-300 mt-0.5">Klik tombol Tambah untuk membuat kategori baru</p>
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
                                    <select v-model="catPerPage" 
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

                            <nav v-if="categories.links.length > 3" class="flex gap-1">
                                <template v-for="(link, k) in categories.links" :key="k">
                                    <Link v-if="link.url" :href="link.url" 
                                        class="px-2.5 py-1.5 text-[10px] font-bold rounded-md border transition-all duration-150 shadow-xs"
                                        :class="link.active ? 'bg-emerald-600 border-emerald-600 text-white shadow-emerald-200' : 'bg-white border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-200'"
                                        v-html="link.label" />
                                    <span v-else class="px-2.5 py-1.5 text-[10px] font-bold text-gray-200 border border-gray-100 rounded-md" v-html="link.label"></span>
                                </template>
                            </nav>
                        </div>
                    </div>

                    <!-- SUBKATEGORI CARD -->
                    <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-opacity duration-300" :class="{'opacity-40 pointer-events-none': isSubLoading}">
                        <!-- Card Header -->
                        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="h-9 w-9 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center">
                                    <svg class="h-4.5 w-4.5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <div class="hidden sm:block">
                                    <h3 class="text-sm font-bold text-gray-800">Subkategori</h3>
                                    <p class="text-[10px] text-gray-400 font-medium">{{ subcategories.total || 0 }} total</p>
                                </div>
                            </div>

                            <!-- Search & Actions -->
                            <div class="flex items-center gap-2 flex-grow sm:justify-end">
                                <div class="relative flex-grow max-w-[200px]">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input v-model="subSearch" type="text" placeholder="Cari..." 
                                        class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-blue-500 transition-all font-medium" />
                                </div>
                                <button v-if="selectedSubs.length > 0" @click="bulkDeleteSubs" 
                                    class="bg-rose-50 text-rose-600 border border-rose-100 px-3 py-1.5 rounded-lg text-[10px] font-bold hover:bg-rose-600 hover:text-white transition-all flex items-center gap-1.5 shadow-sm uppercase shrink-0">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus ({{ selectedSubs.length }})
                                </button>
                                <button @click="openCreateSub" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm shrink-0">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tambah
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="flex-grow overflow-x-auto min-h-[300px]">
                            <table class="min-w-full text-left">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th class="px-4 py-3 border-b border-gray-100 w-10 text-center">
                                            <input type="checkbox" @change="toggleAllSubs" :checked="selectedSubs.length === subcategories.data.length && subcategories.data.length > 0"
                                                class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition cursor-pointer" />
                                        </th>
                                        <th class="px-2 py-3 border-b border-gray-100 w-10 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">No</th>
                                        <th @click="toggleSortSub" class="px-6 py-3 border-b border-gray-100 text-[10px] font-bold text-gray-400 uppercase tracking-widest cursor-pointer hover:bg-blue-50 select-none group transition-all">
                                            <div class="flex items-center gap-1.5">
                                                Subkategori
                                                <div class="flex flex-col -space-y-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                                    <svg :class="{'text-blue-600 opacity-100': filters.sub_dir === 'asc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l-8 8h16l-8-8z"/></svg>
                                                    <svg :class="{'text-blue-600 opacity-100': filters.sub_dir === 'desc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l8-8H4l8 8z"/></svg>
                                                </div>
                                            </div>
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-100 w-32 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Induk</th>
                                        <th class="px-6 py-3 border-b border-gray-100 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Identitas</th>
                                        <th class="px-6 py-3 border-b border-gray-100 text-right w-24 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="(sub, index) in subcategories.data" :key="sub.id" 
                                        class="hover:bg-emerald-50/30 transition-colors duration-150"
                                        :class="{'bg-blue-50/50': selectedSubs.includes(sub.id)}">
                                        <td class="px-4 py-4 text-center">
                                            <input type="checkbox" v-model="selectedSubs" :value="sub.id"
                                                class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition cursor-pointer" />
                                        </td>
                                        <td class="px-2 py-4 text-center text-[11px] text-gray-400 font-bold tabular-nums">
                                            {{ (subcategories.current_page - 1) * subcategories.per_page + index + 1 }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="min-w-0">
                                                <div class="font-semibold text-sm text-gray-800 truncate">
                                                    {{ sub.name }}
                                                </div>
                                                <div class="text-[10px] text-gray-400 font-medium">
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                        {{ sub.assets_count }} Aset
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200 uppercase tracking-tight">
                                                <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                                {{ sub.category?.name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span v-if="sub.prefix" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 uppercase tracking-wider tabular-nums" title="Prefix">
                                                    {{ sub.prefix }}
                                                </span>
                                                <span v-if="sub.managing_dept" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200 uppercase tracking-wider tabular-nums" title="Divisi Pengelola">
                                                    {{ sub.managing_dept }}
                                                </span>
                                                <span v-if="!sub.prefix && !sub.managing_dept" class="text-gray-300">-</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-1">
                                                <button @click="openEditSub(sub)" class="p-1.5 text-gray-400 hover:text-amber-600 rounded-md hover:bg-amber-50 transition-all duration-150" title="Edit">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="deleteSub(sub)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-all duration-150" title="Hapus">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!subcategories.data.length">
                                        <td colspan="6" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                                    <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-xs font-semibold text-gray-400">Belum ada subkategori</p>
                                                <p class="text-[10px] text-gray-300 mt-0.5">Buat subkategori dari kategori yang tersedia</p>
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
                                    <select v-model="subPerPage" 
                                        style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none !important;"
                                        class="appearance-none bg-none text-[10px] font-bold text-blue-700 bg-blue-50 border-blue-200 rounded-lg py-1 pl-2 pr-6 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 cursor-pointer shadow-sm transition-all uppercase">
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                        <option value="all">ALL</option>
                                    </select>
                                    <svg class="absolute right-2 h-2.5 w-2.5 text-blue-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>

                            <nav v-if="subcategories.links.length > 3" class="flex gap-1">
                                <template v-for="(link, k) in subcategories.links" :key="k">
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
        </div>

        <!-- MODAL: Kategori -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showCatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showCatModal = false"></div>
                    <div class="relative bg-white rounded-xl border border-gray-200 shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="px-6 py-4 flex justify-between items-center bg-emerald-600 shadow-sm relative z-10">
                            <h3 class="text-sm font-bold text-white tracking-wide">{{ editingCat ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                            <button @click="showCatModal = false" class="rounded-lg p-1.5 text-emerald-100 hover:bg-emerald-500 hover:text-white transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form @submit.prevent="submitCat" class="p-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nama Kategori *</label>
                                <input v-model="catForm.name" type="text" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition" 
                                    placeholder="Contoh: Elektronik" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Deskripsi</label>
                                <textarea v-model="catForm.description" rows="3" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition"
                                    placeholder="Deskripsi singkat tentang kategori ini..."></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100">
                                <button type="button" @click="showCatModal = false" 
                                    class="px-5 py-2.5 text-[11px] font-bold text-gray-500 uppercase tracking-wider rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                                    Batal
                                </button>
                                <button type="submit" 
                                    class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg text-[11px] font-bold hover:bg-emerald-700 shadow-sm uppercase tracking-wider transition-all disabled:opacity-50" 
                                    :disabled="catForm.processing">
                                    {{ editingCat ? 'Update' : 'Simpan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- MODAL: Subkategori -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showSubModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showSubModal = false"></div>
                    <div class="relative bg-white rounded-xl border border-gray-200 shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="px-6 py-4 flex justify-between items-center bg-emerald-600 shadow-sm relative z-10">
                            <h3 class="text-sm font-bold text-white tracking-wide">{{ editingSub ? 'Edit Subkategori' : 'Tambah Subkategori' }}</h3>
                            <button @click="showSubModal = false" class="rounded-lg p-1.5 text-emerald-100 hover:bg-emerald-500 hover:text-white transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form @submit.prevent="submitSub" class="p-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Kategori Induk *</label>
                                <select v-model="subForm.category_id" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition" required>
                                    <option value="" disabled>Pilih Kategori</option>
                                    <option v-for="cat in categories.data" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                             <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nama Subkategori *</label>
                                    <input v-model="subForm.name" type="text" 
                                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition" 
                                        placeholder="Laptop, AC, dll" required />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Kode / Prefix</label>
                                    <input v-model="subForm.prefix" type="text" 
                                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition uppercase" 
                                        placeholder="Contoh: LTP, AC" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Divisi Pengelola</label>
                                <input v-model="subForm.managing_dept" type="text" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition uppercase" 
                                    placeholder="Contoh: GA, IT, MAINTENANCE" />
                                <p class="text-[10px] text-gray-400 mt-1">Divisi yang bertanggung jawab atas aset ini.</p>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100">
                                <button type="button" @click="showSubModal = false" 
                                    class="px-5 py-2.5 text-[11px] font-bold text-gray-500 uppercase tracking-wider rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                                    Batal
                                </button>
                                <button type="submit" 
                                    class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg text-[11px] font-bold hover:bg-emerald-700 shadow-sm uppercase tracking-wider transition-all disabled:opacity-50" 
                                    :disabled="subForm.processing">
                                    {{ editingSub ? 'Update' : 'Simpan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- MODAL: KONFIRMASI HAPUS -->
        <ConfirmModal 
            :show="showConfirmModal"
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            @close="showConfirmModal = false"
            @confirm="confirmData.onConfirm"
        />

    </AuthenticatedLayout>
</template>
