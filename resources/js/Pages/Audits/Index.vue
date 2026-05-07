<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();
const can = (permission) => page.props.auth.user.permissions.includes(permission);

const props = defineProps({
    audits: Object,
    locations: Array,
    filters: Object,
});

const showModal = ref(false);
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const perPage = ref(props.filters?.per_page || 10);
const isProcessing = ref(false);

// Selection & Bulk Actions
const selectedItems = ref([]);
const showConfirmModal = ref(false);
const confirmData = ref({ title: '', message: '', onConfirm: () => {} });

const form = useForm({
    location_id: '',
    notes: '',
});

let searchTimeout = null;

const applyFilters = () => {
    isProcessing.value = true;
    router.get(route('audits.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        per_page: perPage.value || undefined,
    }, { 
        preserveState: true, 
        replace: true,
        only: ['audits', 'filters'],
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
        selectedItems.value = props.audits.data.map(i => i.id);
    } else {
        selectedItems.value = [];
    }
};

const bulkDelete = () => {
    confirmData.value = {
        title: 'Hapus Sesi Audit',
        message: `Apakah Anda yakin ingin menghapus ${selectedItems.value.length} sesi audit yang dipilih?`,
        onConfirm: () => {
            router.post(route('audits.bulk-delete'), { ids: selectedItems.value }, {
                onSuccess: () => {
                    selectedItems.value = [];
                    showConfirmModal.value = false;
                }
            });
        }
    };
    showConfirmModal.value = true;
};

const createAudit = () => {
    form.post(route('audits.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'completed': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'draft': return 'bg-amber-50 text-amber-700 border-amber-100';
        default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
};
</script>

<template>
    <Head title="Manajemen Audit" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Audit / Opname</h2>
                    <p class="text-xs text-gray-500 mt-1">Lakukan pemeriksaan fisik aset secara berkala per lokasi.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-[500px]" :class="{'opacity-40 pointer-events-none': isProcessing}">
                    
                    <!-- Card Header / Filter Bar -->
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3 shrink-0">
                            <div class="h-9 w-9 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800">Riwayat Audit</h3>
                                <p class="text-[10px] text-gray-400 font-medium">{{ audits.total || 0 }} sesi audit</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 flex-grow sm:justify-end">
                            <div class="relative flex-grow max-w-[250px]">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="search" type="text" placeholder="Cari No. Audit, Lokasi..." 
                                    class="w-full pl-8 pr-3 py-1.5 text-[11px] rounded-lg border-gray-200 bg-gray-50 focus:ring-1 focus:ring-emerald-500 transition-all font-medium" />
                            </div>

                            <button @click="showModal = true" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm shrink-0">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Mulai Audit
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div v-if="audits.data.length > 0" class="px-6 py-3 border-b border-gray-100 flex items-center justify-between gap-3 bg-gray-50/30">
                        <div class="flex items-center gap-3 p-2 rounded-xl transition-all border shrink-0"
                            :class="selectedItems.length > 0 ? 'bg-rose-50 border-rose-100' : 'bg-white border-gray-100'">
                             <label class="flex items-center gap-2 cursor-pointer select-none px-2">
                                 <input type="checkbox" @change="toggleAll" :checked="selectedItems.length === audits.data.length && audits.data.length > 0"
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
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500">No. Audit</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500">Lokasi</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500 text-center">Statistik</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500 text-center">Status</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-[11px] font-bold text-gray-500 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="audit in audits.data" :key="audit.id" class="hover:bg-emerald-50/30 transition-colors duration-150"
                                    :class="{'bg-emerald-50/50': selectedItems.includes(audit.id)}">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox" v-model="selectedItems" :value="audit.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600 transition" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-900 leading-tight">{{ audit.audit_no }}</span>
                                            <span class="text-[10px] text-gray-400 font-medium mt-0.5">{{ formatDate(audit.created_at) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-2 rounded-full bg-emerald-400"></div>
                                            <span class="text-sm font-semibold text-gray-800">{{ audit.location?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="flex flex-col items-center">
                                                <span class="text-[10px] font-black text-gray-900">{{ audit.found_assets }}</span>
                                                <span class="text-[7px] text-gray-400 font-bold uppercase">Ada</span>
                                            </div>
                                            <div class="h-4 w-[1px] bg-gray-100"></div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-[10px] font-black text-rose-600">{{ audit.missing_assets }}</span>
                                                <span class="text-[7px] text-gray-400 font-bold uppercase">Hilang</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-full border shadow-xs" :class="getStatusClass(audit.status)">
                                            {{ audit.status === 'draft' ? 'Dalam Proses' : 'Selesai' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('audits.show', audit.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-600 hover:bg-gray-50 transition shadow-xs">
                                            Buka
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!audits.data.length">
                                    <td colspan="6" class="p-20 text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest bg-white">Belum ada riwayat audit.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE VIEW: Card List -->
                    <div class="md:hidden divide-y divide-gray-50">
                        <div v-for="audit in audits.data" :key="audit.id" class="p-4 bg-white space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" v-model="selectedItems" :value="audit.id" class="h-4 w-4 rounded border-gray-300 text-emerald-600" />
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900">{{ audit.audit_no }}</h4>
                                        <p class="text-[10px] text-gray-400 font-bold">{{ formatDate(audit.created_at) }}</p>
                                    </div>
                                </div>
                                <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded border shadow-xs" :class="getStatusClass(audit.status)">
                                    {{ audit.status === 'draft' ? 'Proses' : 'Selesai' }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <span class="text-[8px] font-bold text-gray-400 uppercase block mb-1">Lokasi</span>
                                    <span class="text-xs font-bold text-gray-800">{{ audit.location?.name }}</span>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-around">
                                    <div class="text-center">
                                        <span class="text-[10px] font-black text-gray-900 block">{{ audit.found_assets }}</span>
                                        <span class="text-[7px] text-gray-400 font-bold uppercase">Ada</span>
                                    </div>
                                    <div class="h-6 w-[1px] bg-gray-200"></div>
                                    <div class="text-center">
                                        <span class="text-[10px] font-black text-rose-600 block">{{ audit.missing_assets }}</span>
                                        <span class="text-[7px] text-gray-400 font-bold uppercase">Hilang</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Link :href="route('audits.show', audit.id)" class="w-full text-center py-2 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-100">
                                    Buka Sesi Scan
                                </Link>
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

                        <nav v-if="audits.links && audits.links.length > 3" class="flex gap-1">
                            <template v-for="(link, k) in audits.links" :key="k">
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

        <!-- Start Audit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 bg-emerald-600 text-white flex justify-between items-center">
                    <h3 class="font-bold">Mulai Audit Baru</h3>
                    <button @click="showModal = false" class="hover:bg-emerald-500 rounded p-1 transition">✕</button>
                </div>
                
                <form @submit.prevent="createAudit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase tracking-widest">Pilih Lokasi Audit *</label>
                        <select v-model="form.location_id" required class="w-full rounded-xl border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- Pilih Lokasi --</option>
                            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase tracking-widest">Catatan (Opsional)</label>
                        <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: Audit Rutin Triwulan 2"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-lg transition">Batal</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition shadow-sm">Mulai Sesi Scan</button>
                    </div>
                </form>
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
