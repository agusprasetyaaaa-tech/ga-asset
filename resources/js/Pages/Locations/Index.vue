<script setup>
/**
 * Locations Index Page - Standardized Table UI
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, useForm, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();
const can = (permission) => page.props.auth.user.permissions.includes(permission);

const props = defineProps({
    locations: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
const isLoading = ref(false);

const applyFilters = (params) => {
    router.get(route('locations.index'), {
        search: search.value,
        per_page: perPage.value,
        ...params
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch(perPage, (val) => {
    selectedItems.value = [];
    isLoading.value = true;
    router.get(route('locations.index'), { ...props.filters, per_page: val }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['locations', 'filters'],
        onFinish: () => isLoading.value = false
    });
});

const showModal = ref(false);
const editingItem = ref(null);
const form = useForm({ name: '', description: '' });

const openCreate = () => { editingItem.value = null; form.reset(); showModal.value = true; };
const openEdit = (item) => { editingItem.value = item; form.name = item.name; form.description = item.description || ''; showModal.value = true; };
const submit = () => editingItem.value ? form.put(route('locations.update', editingItem.value.id), { onSuccess: () => showModal.value = false }) : form.post(route('locations.store'), { onSuccess: () => showModal.value = false });

// Bulk selection state
const selectedItems = ref([]);

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedItems.value = props.locations.data.map(i => i.id);
    } else {
        selectedItems.value = [];
    }
};

const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', type: 'danger', onConfirm: () => {} });

const bulkDelete = () => {
    confirmData.value = {
        title: 'Hapus Massal Lokasi',
        message: `Hapus ${selectedItems.value.length} lokasi yang dipilih?`,
        type: 'danger',
        onConfirm: () => {
            router.post(route('locations.bulk-delete'), { ids: selectedItems.value }, {
                onSuccess: () => {
                    selectedItems.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

// Sorting logic
const toggleSort = () => {
    let currentDir = props.filters.dir || 'asc';
    let dir = currentDir === 'asc' ? 'desc' : 'asc';
    router.get(route('locations.index'), { ...props.filters, dir: dir }, { 
        preserveScroll: true, 
        preserveState: true,
        only: ['locations', 'filters']
    });
};

const deleteItem = (item) => {
    confirmData.value = {
        title: 'Hapus Lokasi',
        message: `Apakah Anda yakin ingin menghapus lokasi "${item.name}"? Semua data yang terkait mungkin akan terpengaruh.`,
        type: 'danger',
        onConfirm: () => {
            router.delete(route('locations.destroy', item.id), {
                onSuccess: () => showConfirmModal.value = false
            });
        }
    };
    showConfirmModal.value = true;
};
</script>

<template>
    <Head title="Lokasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Lokasi</h2>
                    <p class="text-xs text-gray-500 mt-1">Kelola daftar ruangan dan area penempatan aset.</p>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="hidden sm:block">
                                <h3 class="text-sm font-bold text-gray-800">Daftar Lokasi</h3>
                                <p class="text-[10px] text-gray-400 font-medium">{{ locations.total || 0 }} lokasi</p>
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
                                <input v-model="search" type="text" placeholder="Cari nama lokasi..." 
                                    class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                            </div>
                            <button @click="openCreate" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm shrink-0">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah
                            </button>
                        </div>
                    </div>


                    <!-- Bulk Delete & Select All Indicator -->
                    <div v-if="locations.data?.length > 0" class="px-6 py-3 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 rounded-xl transition-all border"
                            :class="selectedItems.length > 0 ? 'bg-rose-50 border-rose-100' : 'bg-gray-50/50 border-gray-100'">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" @change="toggleAll" :checked="selectedItems.length === locations.data?.length && locations.data?.length > 0"
                                        class="h-5 w-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 transition" />
                                    <span class="text-xs font-bold text-gray-600">Pilih Semua</span>
                                </label>
                                <span v-if="selectedItems.length > 0" class="h-4 w-[1px] bg-rose-200 hidden sm:block"></span>
                                <span v-if="selectedItems.length > 0" class="text-xs font-extrabold text-rose-700">{{ selectedItems.length }} dipilih</span>
                            </div>
                            <button v-if="selectedItems.length > 0 && can('delete database')" @click="bulkDelete"
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black shadow-sm shadow-rose-100 active:scale-95 transition-all">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Hapus Semua
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="flex-grow overflow-x-auto min-h-[300px]">
                        <table class="min-w-full text-left">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-4 py-3 border-b border-gray-100 w-10 text-center"></th>
                                    <th class="px-2 py-3 border-b border-gray-100 w-10 text-center text-xs font-bold text-gray-500">No</th>
                                    <th @click="toggleSort" class="px-6 py-3 border-b border-gray-100 text-xs font-bold text-gray-500 cursor-pointer hover:bg-emerald-50 select-none group transition-all">
                                        <div class="flex items-center gap-1.5">
                                            Nama Lokasi
                                            <div class="flex flex-col -space-y-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                                <svg :class="{'text-emerald-600 opacity-100': filters.dir === 'asc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5l-8 8h16l-8-8z"/></svg>
                                                <svg :class="{'text-emerald-600 opacity-100': filters.dir === 'desc'}" class="h-2 w-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l8-8H4l8 8z"/></svg>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-100 w-32 text-center text-xs font-bold text-gray-500">Jumlah Aset</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-right w-24 text-xs font-bold text-gray-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(item, index) in locations.data" :key="item.id" 
                                    class="hover:bg-emerald-50/30 transition-colors duration-150"
                                    :class="{'bg-emerald-50/50': selectedItems.includes(item.id)}">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="selectedItems" :value="item.id"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer" />
                                    </td>
                                    <td class="px-2 py-4 text-center text-[11px] text-gray-400 font-bold tabular-nums">
                                        {{ (locations.current_page - 1) * locations.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-sm text-gray-800 truncate">{{ item.name }}</div>
                                            <div class="text-[10px] text-gray-400 leading-tight truncate max-w-sm mt-0.5">{{ item.description || 'Tidak ada deskripsi' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center h-6 min-w-[24px] px-2 text-[10px] font-bold rounded-full"
                                            :class="item.assets_count > 0 ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-gray-400 bg-gray-50 border border-gray-100'">
                                            {{ item.assets_count || 0 }} Aset
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-1">
                                            <button v-if="can('edit database')" @click="openEdit(item)" class="p-1.5 text-gray-400 hover:text-amber-600 rounded-md hover:bg-amber-50 transition-all duration-150" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button v-if="can('delete database')" @click="deleteItem(item)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-all duration-150" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!locations.data?.length">
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                                <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <p class="text-xs font-semibold text-gray-400">Belum ada lokasi</p>
                                            <p class="text-[10px] text-gray-300 mt-0.5">Silakan buat lokasi penempatan aset baru</p>
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

                        <nav v-if="locations.links && locations.links.length > 3" class="flex gap-1">
                            <template v-for="(link, k) in locations.links" :key="k">
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
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-xl border border-gray-200 shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="px-6 py-4 flex justify-between items-center bg-emerald-600 shadow-sm relative z-10">
                            <h3 class="text-sm font-bold text-white tracking-wide">{{ editingItem ? 'Edit Lokasi' : 'Tambah Lokasi' }}</h3>
                            <button @click="showModal = false" class="rounded-lg p-1.5 text-emerald-100 hover:bg-emerald-500 hover:text-white transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form @submit.prevent="submit" class="p-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nama Lokasi *</label>
                                <input v-model="form.name" type="text" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition" 
                                    placeholder="Contoh: Gudang Barat" required />
                                <p v-if="form.errors.name" class="mt-2 text-[10px] font-bold text-rose-600 uppercase">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Deskripsi</label>
                                <textarea v-model="form.description" rows="3" 
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition"
                                    placeholder="Penjelasan ruangan atau lokasi..."></textarea>
                                <p v-if="form.errors.description" class="mt-2 text-[10px] font-bold text-rose-600 uppercase">{{ form.errors.description }}</p>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100">
                                <button type="button" @click="showModal = false" 
                                    class="px-5 py-2.5 text-[11px] font-bold text-gray-500 uppercase tracking-wider rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                                    Batal
                                </button>
                                <button type="submit" 
                                    class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg text-[11px] font-bold hover:bg-emerald-700 shadow-sm uppercase tracking-wider transition-all disabled:opacity-50" 
                                    :disabled="form.processing">
                                    {{ editingItem ? 'Update' : 'Simpan' }}
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
