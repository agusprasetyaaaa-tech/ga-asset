<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();
const can = (permission) => page.props.auth.user.permissions.includes(permission);

const props = defineProps({
    loans: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const perPage = ref(props.filters?.per_page || 10);
const isProcessing = ref(false);

// Selection & Bulk Actions
const selectedItems = ref([]);
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', onConfirm: () => {} });

let searchTimeout = null;

const applyFilters = () => {
    isProcessing.value = true;
    router.get(route('loans.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        per_page: perPage.value || undefined,
    }, { 
        preserveState: true, 
        replace: true,
        only: ['loans', 'filters'],
        onFinish: () => {
            isProcessing.value = false;
            selectedItems.value = [];
        }
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([statusFilter, perPage], () => {
    applyFilters();
});

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedItems.value = props.loans.data.map(i => i.id);
    } else {
        selectedItems.value = [];
    }
};

const bulkDelete = () => {
    confirmData.value = {
        title: 'Hapus Data Peminjaman',
        message: `Apakah Anda yakin ingin menghapus ${selectedItems.value.length} riwayat peminjaman yang dipilih?`,
        onConfirm: () => {
            router.post(route('loans.bulk-delete'), { ids: selectedItems.value }, {
                onSuccess: () => {
                    selectedItems.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'borrowed': return 'bg-blue-50 text-blue-700 border-blue-100';
        case 'returned': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'overdue': return 'bg-rose-50 text-rose-700 border-rose-100';
        default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
};
</script>

<template>
    <Head title="Manajemen Peminjaman" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Peminjaman</h2>
                    <p class="text-xs text-gray-500 mt-1">Pantau aset yang sedang dipinjam oleh karyawan dan pihak eksternal.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-[500px]" :class="{'opacity-40 pointer-events-none': isProcessing}">
                    
                    <!-- Card Header / Filter Bar -->
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="h-9 w-9 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800">Daftar Transaksi</h3>
                                <p class="text-[10px] text-gray-400 font-medium">{{ loans.total || 0 }} total data</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 flex-grow sm:justify-end">
                            <div class="relative flex-grow max-w-[300px]">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="search" type="text" placeholder="Cari peminjam, aset..." 
                                    class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                            </div>

                            <div class="relative">
                                <select v-model="statusFilter" class="appearance-none rounded-lg border-gray-200 bg-gray-50 text-[11px] font-bold text-gray-500 py-1.5 pl-3 pr-8 focus:ring-1 focus:ring-emerald-500 transition cursor-pointer">
                                    <option value="">Semua Status</option>
                                    <option value="borrowed">Dipinjam</option>
                                    <option value="returned">Kembali</option>
                                </select>
                                <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 h-2.5 w-2.5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div v-if="loans.data.length > 0" class="px-6 py-3 border-b border-gray-100 flex items-center justify-between gap-3 bg-gray-50/30">
                        <div class="flex items-center gap-3 p-2 rounded-xl transition-all border shrink-0"
                            :class="selectedItems.length > 0 ? 'bg-rose-50 border-rose-100' : 'bg-white border-gray-100'">
                             <label class="flex items-center gap-2 cursor-pointer select-none px-2">
                                 <input type="checkbox" @change="toggleAll" :checked="selectedItems.length === loans.data.length && loans.data.length > 0"
                                     class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition" />
                                 <span class="text-[11px] font-bold text-gray-600">Pilih Semua</span>
                             </label>
                             <span v-if="selectedItems.length > 0" class="h-4 w-[1px] bg-rose-200"></span>
                             <span v-if="selectedItems.length > 0" class="text-[11px] font-extrabold text-rose-700 pr-2">{{ selectedItems.length }} dipilih</span>
                        </div>

                        <button v-if="selectedItems.length > 0 && can('delete activity')" @click="bulkDelete" 
                            class="flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-lg text-xs font-bold shadow-sm hover:bg-rose-700 transition active:scale-95">
                             <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                             Hapus Terpilih
                        </button>
                    </div>

                    <!-- DESKTOP VIEW: Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-left table-auto">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="w-10 px-6 py-3 border-b border-gray-100"></th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500">Detail Aset</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500">Peminjam</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500">Tgl Pinjam</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500 text-center">Status</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-emerald-50/30 transition-colors duration-150"
                                    :class="{'bg-emerald-50/50': selectedItems.includes(loan.id)}">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox" v-model="selectedItems" :value="loan.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600 transition" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-center">
                                                <span class="text-xs font-bold text-gray-300">{{ loan.asset?.name.charAt(0) }}</span>
                                            </div>
                                            <div>
                                                <Link :href="route('assets.show', loan.asset?.id)" class="font-bold text-sm text-gray-900 hover:text-emerald-600 transition">{{ loan.asset?.name }}</Link>
                                                <div class="text-[10px] text-emerald-700 font-mono font-bold mt-0.5">{{ loan.asset?.asset_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800">{{ loan.borrower_name }}</span>
                                            <span class="text-[10px] text-gray-400 uppercase font-semibold">{{ loan.borrower_department || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600 font-medium">
                                        {{ formatDate(loan.loan_date) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-full border shadow-sm" :class="getStatusClass(loan.status)">
                                            {{ loan.status === 'borrowed' ? 'Dipinjam' : 'Kembali' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('assets.show', loan.asset_id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-600 hover:bg-gray-50 transition shadow-xs">
                                            Detail
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!loans.data.length">
                                    <td colspan="6" class="p-20 text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest bg-white">Belum ada data peminjaman.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE VIEW: Card List -->
                    <div class="md:hidden divide-y divide-gray-50">
                        <div v-for="loan in loans.data" :key="loan.id" class="p-4 bg-white space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" v-model="selectedItems" :value="loan.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600" />
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900">{{ loan.asset?.name }}</h4>
                                        <p class="text-[10px] text-emerald-700 font-mono font-bold">{{ loan.asset?.asset_code }}</p>
                                    </div>
                                </div>
                                <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded border" :class="getStatusClass(loan.status)">
                                    {{ loan.status === 'borrowed' ? 'Dipinjam' : 'Kembali' }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <span class="text-[8px] font-bold text-gray-400 uppercase block mb-1">Peminjam</span>
                                    <span class="text-xs font-bold text-gray-800">{{ loan.borrower_name }}</span>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <span class="text-[8px] font-bold text-gray-400 uppercase block mb-1">Tgl Pinjam</span>
                                    <span class="text-xs font-bold text-gray-800">{{ formatDate(loan.loan_date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between mt-auto">
                        <div class="flex items-center gap-2">
                            <select v-model="perPage" class="appearance-none bg-emerald-50 border-emerald-200 text-emerald-700 text-[10px] font-bold rounded-lg py-1 pl-2 pr-6 focus:ring-1 focus:ring-emerald-500 cursor-pointer shadow-sm">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                        </div>

                        <nav v-if="loans.links && loans.links.length > 3" class="flex gap-1">
                            <template v-for="(link, k) in loans.links" :key="k">
                                <Link v-if="link.url" :href="link.url" 
                                    class="px-2.5 py-1.5 text-[10px] font-bold rounded-md border transition-all"
                                    :class="link.active ? 'bg-emerald-600 border-emerald-600 text-white shadow-sm' : 'bg-white border-gray-200 text-gray-400 hover:border-emerald-200 hover:text-emerald-600'"
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
            type="danger"
            confirm-text="Ya, Hapus"
            @close="showConfirmModal = false"
            @confirm="confirmData.onConfirm"
        />
    </AuthenticatedLayout>
</template>
