<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Html5QrcodeScanner } from 'html5-qrcode';
import axios from 'axios';

const props = defineProps({
    audit: Object,
});

const scanResult = ref(null);
const lastScannedAsset = ref(null);
const error = ref(null);
const isLoading = ref(false);
const manualCode = ref('');

const handleScanSuccess = async (decodedText) => {
    if (isLoading.value) return;
    processCode(decodedText);
};

const processCode = async (code) => {
    isLoading.value = true;
    error.value = null;
    
    try {
        const response = await axios.post(route('audits.scan', props.audit.id), {
            code: code
        });
        
        lastScannedAsset.value = response.data.asset;
        scanResult.value = 'success';
        manualCode.value = '';
        
        // Refresh audit data
        router.reload({ only: ['audit'] });
        
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal memproses kode.';
        scanResult.value = 'error';
    } finally {
        isLoading.value = false;
        setTimeout(() => { scanResult.value = null; }, 3000);
    }
};

let html5QrcodeScanner = null;

onMounted(() => {
    if (props.audit.status === 'draft') {
        nextTick(() => {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", 
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                },
                /* verbose= */ false
            );
            html5QrcodeScanner.render(handleScanSuccess, (errorMessage) => {
                // Ignore constant scan failures
            });
        });
    }
});

onUnmounted(() => {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().catch(err => console.error(err));
    }
});

const completeAudit = () => {
    if (confirm('Selesaikan sesi audit ini? Anda tidak akan bisa melakukan scan lagi.')) {
        router.post(route('audits.complete', props.audit.id));
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'found': return 'text-emerald-600 bg-emerald-50 border-emerald-100';
        case 'missing': return 'text-rose-600 bg-rose-50 border-rose-100';
        case 'mismatch': return 'text-amber-600 bg-amber-50 border-amber-100';
        default: return 'text-gray-400 bg-gray-50 border-gray-100';
    }
};
</script>

<template>
    <Head title="Audit Scanning" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="router.get(route('audits.index'))" class="h-9 w-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 leading-tight">Proses Audit</h2>
                        <p class="text-[10px] text-gray-500 font-mono">{{ audit.audit_no }} — {{ audit.location?.name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a v-if="audit.status === 'completed'" :href="route('audits.export-pdf', audit.id)" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1.5m1.5 0H13m-4 4h1.5m1.5 0H13m-4 4h1.5m1.5 0H13" /></svg>
                        Export PDF
                    </a>
                    <button v-if="audit.status === 'draft'" @click="completeAudit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition shadow-sm">
                        Selesaikan Audit
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Left: Scanner & Stats -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- Manual Input Card -->
                        <div v-if="audit.status === 'draft'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Input Manual / Scanner USB</label>
                            <div class="flex gap-2">
                                <input 
                                    v-model="manualCode" 
                                    @keyup.enter="processCode(manualCode)"
                                    type="text" 
                                    placeholder="Tempel kode di sini..." 
                                    class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                />
                                <button @click="processCode(manualCode)" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-200 transition">Proses</button>
                            </div>
                        </div>

                        <!-- Scanner Card -->
                        <div v-if="audit.status === 'draft'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-6">
                                <div id="reader" class="overflow-hidden rounded-xl border-2 border-gray-100 bg-gray-50"></div>
                                
                                <!-- Scan Feedback -->
                                <div v-if="scanResult" class="mt-4 animate-fadeIn">
                                    <div v-if="scanResult === 'success'" class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 text-lg">✓</div>
                                        <div>
                                            <p class="text-xs font-bold text-emerald-800">{{ lastScannedAsset?.name }}</p>
                                            <p class="text-[10px] text-emerald-600">Aset berhasil diverifikasi.</p>
                                        </div>
                                    </div>
                                    <div v-if="scanResult === 'error'" class="p-3 bg-rose-50 border border-rose-200 rounded-lg flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 text-lg">!</div>
                                        <div>
                                            <p class="text-xs font-bold text-rose-800">Gagal</p>
                                            <p class="text-[10px] text-rose-600">{{ error }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-bold text-gray-800 mb-6">Statistik Audit</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Aset</span>
                                    <span class="text-2xl font-black text-gray-900">{{ audit.total_assets }}</span>
                                </div>
                                <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest block mb-1">Ditemukan</span>
                                    <span class="text-2xl font-black text-emerald-700">{{ audit.found_assets }}</span>
                                </div>
                                <div class="p-4 bg-rose-50 rounded-xl border border-rose-100">
                                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-widest block mb-1">Hilang</span>
                                    <span class="text-2xl font-black text-rose-700">{{ audit.missing_assets }}</span>
                                </div>
                                <div class="p-4 bg-amber-50 rounded-xl border border-amber-100">
                                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-widest block mb-1">Salah Lokasi</span>
                                    <span class="text-2xl font-black text-amber-700">{{ audit.mismatch_assets }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Item List -->
                    <div class="lg:col-span-7">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[600px]">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-800">Daftar Aset Audit</h3>
                                <div class="flex gap-2">
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tight">{{ audit.items?.length || 0 }} Aset Terlibat</span>
                                </div>
                            </div>
                            
                            <div class="flex-grow overflow-y-auto">
                                <table class="min-w-full text-left">
                                    <thead class="bg-gray-50/50 sticky top-0 z-10 backdrop-blur-sm">
                                        <tr>
                                            <th class="px-6 py-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider">Aset</th>
                                            <th class="px-6 py-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-2.5 text-[9px] font-bold text-gray-400 uppercase tracking-wider">Waktu Scan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="item in audit.items" :key="item.id" class="hover:bg-gray-50/50 transition">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-800 leading-tight">{{ item.asset?.name }}</span>
                                                    <span class="text-[10px] text-emerald-700 font-mono mt-0.5">{{ item.asset?.asset_code }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full border tracking-wide" :class="getStatusColor(item.status)">
                                                    {{ item.status === 'mismatch' ? 'Salah Lokasi' : item.status }}
                                                </span>
                                                <p v-if="item.status === 'mismatch'" class="text-[8px] text-amber-600 mt-1">Seharusnya: {{ item.scanned_location }}</p>
                                            </td>
                                            <td class="px-6 py-4 text-[10px] text-gray-400 font-mono">
                                                {{ item.scanned_at ? new Date(item.scanned_at).toLocaleTimeString('id-ID') : '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
#reader {
    width: 100% !important;
}
#reader video {
    border-radius: 12px;
}
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
