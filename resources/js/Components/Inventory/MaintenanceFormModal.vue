<script setup>
/**
 * MaintenanceFormModal Component
 * Modal form for logging maintenance activities (repair, replacement, inspection).
 * Enhanced with asset search and camera barcode scanner.
 */
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    asset: { type: Object, default: null },
    assets: { type: Array, default: () => [] },
    maintenanceLog: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const isEditing = ref(false);
const searchAsset = ref('');
const displayCost = ref(''); 
const isScannerOpen = ref(false);
const scanError = ref('');
const isScanning = ref(false);
const isDropdownOpen = ref(false);

const selectAsset = (a) => {
    form.asset_id = a.id;
    searchAsset.value = a.name;
    isDropdownOpen.value = false;
};

let html5QrCode = null;

const filteredAssets = computed(() => {
    if (!searchAsset.value) return props.assets;
    const s = searchAsset.value.toLowerCase();
    return props.assets.filter(a => 
        a.name.toLowerCase().includes(s) || 
        (a.asset_code && a.asset_code.toLowerCase().includes(s)) ||
        (a.barcode_code && a.barcode_code.toLowerCase().includes(s)) ||
        (a.serial_number && a.serial_number.toLowerCase().includes(s))
    );
});

const form = useForm({
    asset_id: '',
    type: 'repair',
    description: '',
    cost: '',
    technician: '',
    completed_at: '',
});

// Watch for manual cost input to format with dots
watch(displayCost, (val) => {
    if (!val) {
        form.cost = '';
        return;
    }
    // Remove dots and everything except numbers
    const rawValue = String(val).replace(/\./g, '').replace(/[^0-9]/g, '');
    form.cost = rawValue;
    
    // Format back with dots for display
    if (rawValue) {
        displayCost.value = new Intl.NumberFormat('id-ID').format(rawValue);
    }
});

watch(() => props.show, (val) => {
    if (val) {
        searchAsset.value = '';
        isScannerOpen.value = false;
        if (props.maintenanceLog) {
            isEditing.value = true;
            form.asset_id = props.maintenanceLog.asset_id;
            form.type = props.maintenanceLog.type;
            form.description = props.maintenanceLog.description;
            form.cost = props.maintenanceLog.cost || '';
            displayCost.value = props.maintenanceLog.cost ? new Intl.NumberFormat('id-ID').format(props.maintenanceLog.cost) : '';
            form.technician = props.maintenanceLog.technician || '';
            form.completed_at = props.maintenanceLog.completed_at ? props.maintenanceLog.completed_at.split('T')[0] : '';
        } else {
            isEditing.value = false;
            form.reset();
            if (props.asset) {
                form.asset_id = props.asset.id;
            }
        }
    } else {
        stopScanner();
    }
});

const startScanner = async () => {
    scanError.value = '';
    
    // Check if the context is secure (HTTPS or localhost)
    if (!window.isSecureContext && window.location.hostname !== 'localhost') {
        scanError.value = 'Browser memblokir akses kamera pada koneksi HTTP (tidak aman). Harap gunakan HTTPS atau buka dari localhost.';
        isScannerOpen.value = true; // Show the error box
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        scanError.value = 'Browser Anda tidak mendukung akses kamera atau izin ditolak.';
        isScannerOpen.value = true;
        return;
    }

    isScannerOpen.value = true;
    isScanning.value = true;

    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        setTimeout(async () => {
            html5QrCode = new Html5Qrcode('maintenance-scanner-reader');
            await html5QrCode.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 150 },
                },
                onScanSuccess,
                (errorMessage) => {
                    // Check for common permission errors but don't stop scanning
                    if (errorMessage.includes('NotAllowedError')) {
                        scanError.value = 'Izin kamera ditolak oleh browser.';
                    }
                }
            );
        }, 300); // Wait for DOM
    } catch (err) {
        scanError.value = 'Gagal mengakses kamera: ' + err.message;
        isScanning.value = false;
    }
};

const stopScanner = async () => {
    if (html5QrCode && isScanning.value) {
        try {
            await html5QrCode.stop();
        } catch (e) { /* ignore */ }
    }
    isScanning.value = false;
    isScannerOpen.value = false;
};

const onScanSuccess = (decodedText) => {
    stopScanner();
    // Try to find the asset in our local props.assets list
    const foundAsset = props.assets.find(a => 
        a.barcode_code === decodedText || 
        a.asset_code === decodedText || 
        a.serial_number === decodedText
    );

    if (foundAsset) {
        form.asset_id = foundAsset.id;
        searchAsset.value = foundAsset.name; // Fill search to show result
    } else {
        // If not found in local list, we could try to lookup via API
        lookupAssetViaAPI(decodedText);
    }
};

const lookupAssetViaAPI = async (code) => {
    try {
        const response = await axios.post(route('scanner.lookup'), { code });
        if (response.data.found) {
            form.asset_id = response.data.asset.id;
            searchAsset.value = response.data.asset.name;
        } else {
            alert('Asset tidak ditemukan untuk kode: ' + code);
        }
    } catch (err) {
        alert('Asset tidak ditemukan atau kesalahan server.');
    }
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('maintenance.update', props.maintenanceLog.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('maintenance.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
};

const close = () => {
    stopScanner();
    form.clearErrors();
    emit('close');
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close"></div>

                <div class="relative w-full max-w-lg max-h-[95vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    <!-- Header -->
                    <div class="sticky top-0 z-20 flex items-center justify-between bg-emerald-600 px-6 py-5 rounded-t-2xl shadow-sm">
                        <h3 class="text-lg font-bold text-white">
                            {{ isEditing ? 'Edit Log Maintenance' : 'Tambah Log Maintenance' }}
                        </h3>
                        <button @click="close" class="rounded-lg p-1.5 text-white hover:bg-emerald-500 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5">
                        <!-- User-friendly Searchable Asset Selector (Single Box) -->
                        <div v-if="!asset" class="space-y-1.5 relative">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-700">Asset *</label>
                                <button v-if="!isScannerOpen" type="button" @click="startScanner" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100 hover:bg-emerald-100 transition-all">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Scan
                                </button>
                                <button v-else type="button" @click="stopScanner" 
                                    class="text-[10px] font-bold text-rose-600 hover:text-rose-700">
                                    Tutup Scan
                                </button>
                            </div>
                            
                            <!-- Scanner View -->
                            <div v-if="isScannerOpen" class="mb-3">
                                <div id="maintenance-scanner-reader" class="w-full rounded-xl overflow-hidden bg-gray-900 aspect-video border-2 border-emerald-500 shadow-inner"></div>
                                <div v-if="scanError" class="mt-2 text-[10px] text-red-600 font-bold bg-red-50 p-2 rounded-lg border border-red-100">{{ scanError }}</div>
                            </div>

                            <!-- Single Search Box -->
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="searchAsset" type="text" 
                                    @focus="isDropdownOpen = true"
                                    placeholder="Cari nama atau kode aset..."
                                    class="w-full rounded-lg border-gray-200 pl-10 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 transition shadow-sm bg-white" />
                                
                                <!-- Result Dropdown -->
                                <div v-if="isDropdownOpen && filteredAssets.length > 0" 
                                    class="absolute z-[60] mt-1 w-full max-h-60 overflow-y-auto rounded-xl bg-white shadow-xl border border-gray-100 py-1 slide-in-top">
                                    <button v-for="a in filteredAssets" :key="a.id" type="button"
                                        @click="selectAsset(a)"
                                        class="w-full px-4 py-2.5 text-left hover:bg-emerald-50 transition-colors flex flex-col gap-0.5 border-b border-gray-50 last:border-0">
                                        <span class="text-sm font-bold text-gray-900">{{ a.name }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono tracking-tight">{{ a.asset_code || a.barcode_code }}</span>
                                    </button>
                                </div>

                                <div v-if="isDropdownOpen && searchAsset && filteredAssets.length === 0"
                                    class="absolute z-[60] mt-1 w-full rounded-xl bg-white shadow-xl border border-gray-100 p-4 text-center text-xs text-gray-400">
                                    Aset tidak ditemukan.
                                </div>
                            </div>

                            <!-- Click overlay to close dropdown -->
                            <div v-if="isDropdownOpen" class="fixed inset-0 z-[55]" @click="isDropdownOpen = false"></div>
                            
                            <!-- Hidden select for form validation/data -->
                            <input type="hidden" v-model="form.asset_id" required />
                            <div v-if="form.asset_id" class="flex items-center gap-2 mt-2 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-100 w-fit">
                                <span class="text-[10px] font-bold text-emerald-700">Terpilih:</span>
                                <span class="text-xs font-bold text-emerald-800">{{ props.assets.find(a => a.id === form.asset_id)?.name }}</span>
                                <button type="button" @click="form.asset_id = ''; searchAsset = ''" class="text-emerald-400 hover:text-emerald-600">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div v-else class="rounded-xl bg-gray-50 p-4 border border-gray-100 text-sm font-bold text-gray-700">
                            Asset: <span class="text-emerald-600 underline">{{ asset.name }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Tipe Pemeliharaan *</label>
                            <select v-model="form.type" required
                                class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm">
                                <option value="repair">Perbaikan (Repair)</option>
                                <option value="replacement">Pergantian Suku Cadang</option>
                                <option value="inspection">Inspeksi (Inspection)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Deskripsi Pekerjaan *</label>
                            <textarea v-model="form.description" rows="3" required
                                class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm"
                                placeholder="Detail kerusakan / perbaikan yang dilakukan..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Biaya (Rp)</label>
                                <input v-model="displayCost" type="text"
                                    class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm"
                                    placeholder="0" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Vendor</label>
                                <input v-model="form.technician" type="text"
                                    class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm"
                                    placeholder="Nama vendor pelaksana" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Tanggal Selesai</label>
                            <input v-model="form.completed_at" type="date"
                                class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm" />
                            <p class="mt-1.5 text-[10px] text-gray-400 italic">Kosongkan jika proses pemeliharaan masih berlangsung.</p>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                            <button type="button" @click="close"
                                class="rounded-lg border border-gray-300 px-6 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-emerald-600 px-8 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>{{ isEditing ? 'Update' : 'Simpan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
