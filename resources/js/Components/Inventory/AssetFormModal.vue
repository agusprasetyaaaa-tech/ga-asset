<script setup>
/**
 * AssetFormModal Component - Extended Version
 * Comprehensive form with all asset fields organized in sections.
 * Handles Category -> Subcategory selection, photo upload, and procurement info.
 */
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    asset: { type: Object, default: null },
    locations: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    subcategories: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    vendors: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const isEditing = ref(false);
const selectedCategoryId = ref('');
const photoPreview = ref(null);

const form = useForm({
    asset_code: '',
    name: '',
    subcategory_id: '',
    brand: '',
    model_type: '',
    serial_number: '',
    specification: '',
    condition: 'baik',
    status: 'available',
    location_id: '',
    department: '',
    department_id: '',
    vendor_id: '',
    current_holder: '',
    description: '',
    photo: null,
    purchase_date: new Date().toISOString().split('T')[0],
    purchase_price: '',
    vendor: '',
    warranty_date: '',
    usage_period: '',
    notes: '',
    asset_owner: 'PT. Interprima Indocom',
});

// Auto-generate code when subcategory, department, or purchase_date changes
const isCheckingCode = ref(false);
const codeIsDuplicate = ref(false);

watch([() => form.subcategory_id, () => form.department_id, () => form.department, () => form.purchase_date], async ([newSub, newDeptId, newDept, newDate]) => {
    // We trigger generation if Subcategory is selected. 
    // Dept can be empty (defaults to 'DEPT') and Date defaults to today.
    if (!isEditing.value && newSub) {
        try {
            const response = await fetch(route('assets.generate-code'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({ 
                    subcategory_id: newSub, 
                    department_id: newDeptId || '',
                    department: newDept || '',
                    purchase_date: newDate || new Date().toISOString().split('T')[0]
                })
            });
            const data = await response.json();
            if (data.code) {
                form.asset_code = data.code;
            }
        } catch (e) { console.error(e); }
    }
});

// Debounced check for asset_code uniqueness
let codeCheckTimeout = null;
watch(() => form.asset_code, (newCode) => {
    if (!newCode) {
        codeIsDuplicate.value = false;
        return;
    }
    
    clearTimeout(codeCheckTimeout);
    codeCheckTimeout = setTimeout(async () => {
        // Only check if it's different from the original asset code (if editing)
        if (isEditing.value && newCode === props.asset?.asset_code) {
            codeIsDuplicate.value = false;
            return;
        }

        isCheckingCode.value = true;
        try {
            const response = await fetch(`${route('assets.check-code')}?code=${newCode}${isEditing.value ? '&exclude_id='+props.asset.id : ''}`);
            const data = await response.json();
            codeIsDuplicate.value = data.exists;
        } catch (e) { 
            console.error(e); 
        } finally {
            isCheckingCode.value = false;
        }
    }, 500);
});

// Filter subcategories based on selected category
const filteredSubcategories = computed(() => {
    if (!selectedCategoryId.value) return [];
    return props.subcategories.filter(sub => sub.category_id == selectedCategoryId.value);
});

watch(() => props.show, (val) => {
    if (val && props.asset) {
        isEditing.value = true;
        form.asset_code = props.asset.asset_code || '';
        form.name = props.asset.name || '';
        form.subcategory_id = props.asset.subcategory_id || '';
        form.brand = props.asset.brand || '';
        form.model_type = props.asset.model_type || '';
        form.serial_number = props.asset.serial_number || '';
        form.specification = props.asset.specification || '';
        form.condition = props.asset.condition || 'baik';
        form.status = props.asset.status || 'available';
        form.location_id = props.asset.location_id || '';
        form.department_id = props.asset.department_id || '';
        form.vendor_id = props.asset.vendor_id || '';
        form.department = props.asset.department || '';
        form.current_holder = props.asset.current_holder || '';
        form.description = props.asset.description || '';
        form.photo = null;
        form.purchase_date = props.asset.purchase_date ? props.asset.purchase_date.split('T')[0] : '';
        form.purchase_price = props.asset.purchase_price || '';
        form.vendor = props.asset.vendor || '';
        form.warranty_date = props.asset.warranty_date ? props.asset.warranty_date.split('T')[0] : '';
        form.usage_period = props.asset.usage_period || '';
        form.notes = props.asset.notes || '';
        form.asset_owner = props.asset.asset_owner || '';

        // Photo preview for existing
        photoPreview.value = props.asset.photo ? `/storage/${props.asset.photo}` : null;
        
        // Find existing category from subcategory
        const sub = props.subcategories.find(s => s.id == props.asset.subcategory_id);
        if (sub) selectedCategoryId.value = sub.category_id;
    } else {
        isEditing.value = false;
        selectedCategoryId.value = '';
        photoPreview.value = null;
        form.reset();
    }
});

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => { photoPreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            if (!isEditing.value) form.reset();
            emit('close');
        },
    };

    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('assets.update', props.asset.id), options);
    } else {
        form.post(route('assets.store'), options);
    }
};

const close = () => {
    form.clearErrors();
    emit('close');
};

const inputClass = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition placeholder:text-gray-400';
const labelClass = 'block text-[10px] font-bold text-gray-500 mb-1.5';
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

                <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    <!-- Header (Refined Emerald Theme) -->
                    <div class="sticky top-0 z-10 flex items-center justify-between bg-emerald-600 px-6 py-5 rounded-t-2xl shadow-sm">
                        <div>
                            <h3 class="text-lg font-bold text-white">
                                {{ isEditing ? 'Edit Asset' : 'Tambah Asset Baru' }}
                            </h3>
                            <p v-if="isEditing && asset?.asset_code" class="text-xs text-emerald-50 mt-0.5 font-mono opacity-90">
                                Kode: {{ asset.asset_code }}
                            </p>
                            <p v-if="!isEditing" class="text-xs text-emerald-50 mt-0.5 opacity-90">
                                Kode Asset akan di-generate otomatis
                            </p>
                        </div>
                        <button @click="close" class="rounded-lg p-1.5 text-white hover:bg-emerald-500 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">

                        <!-- Form Fields -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-800 mb-6">
                                Informasi Aset
                            </h4>

                             <!-- Kode Asset -->
                            <div class="mb-4">
                                <label :class="labelClass" class="flex items-center justify-between">
                                    <span>Kode Asset / ID Asset *</span>
                                    <span v-if="!isEditing" class="text-[9px] bg-emerald-100 text-emerald-600 px-1.5 py-0.5 rounded leading-none">Auto-generate</span>
                                </label>
                                <input v-model="form.asset_code" type="text" required :class="[inputClass, codeIsDuplicate ? 'border-red-500 ring-1 ring-red-500' : '']"
                                    placeholder="Akan terisi otomatis saat memilih subkategori & dept" />
                                <div v-if="isCheckingCode" class="mt-1 flex items-center gap-1.5 text-[10px] text-gray-400">
                                    <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Memeriksa ketersediaan...
                                </div>
                                <p v-if="codeIsDuplicate" class="text-[10px] font-bold text-red-600 mt-1 flex items-center gap-1">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    Kode aset sudah digunakan! Silakan gunakan kode lain agar tidak terduplikasi.
                                </p>
                                <p v-if="form.errors.asset_code" class="text-xs text-red-500 mt-1">{{ form.errors.asset_code }}</p>
                            </div>

                            <!-- Nama Asset -->
                            <div class="mb-4">
                                <label :class="labelClass">Nama Asset *</label>
                                <input v-model="form.name" type="text" required :class="inputClass"
                                    placeholder="Contoh: Laptop Dell Latitude 5540" />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                            </div>

                            <!-- Pemilik Aset -->
                            <div class="mb-4">
                                <label :class="labelClass">Pemilik Aset (Perusahaan)</label>
                                <input v-model="form.asset_owner" type="text" :class="inputClass"
                                    placeholder="Contoh: PT. Interprima Indocom" list="owner-suggestions" />
                                <datalist id="owner-suggestions">
                                    <option value="PT. Interprima Indocom" />
                                </datalist>
                                <p v-if="form.errors.asset_owner" class="text-xs text-red-500 mt-1">{{ form.errors.asset_owner }}</p>
                            </div>

                            <!-- Kategori & Subkategori -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                                <div>
                                    <label :class="labelClass">Kategori *</label>
                                    <select v-model="selectedCategoryId" @change="form.subcategory_id = ''" :class="inputClass">
                                        <option value="">Pilih Kategori</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Subkategori *</label>
                                    <select v-model="form.subcategory_id" required :disabled="!selectedCategoryId"
                                        :class="[inputClass, !selectedCategoryId ? 'bg-gray-100 text-gray-400' : '']">
                                        <option value="">{{ selectedCategoryId ? 'Pilih Subkategori' : 'Pilih Kategori Dulu' }}</option>
                                        <option v-for="sub in filteredSubcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                    </select>
                                    <p v-if="form.errors.subcategory_id" class="text-xs text-red-500 mt-1">{{ form.errors.subcategory_id }}</p>
                                </div>
                            </div>

                            <!-- Brand & Model -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label :class="labelClass">Brand / Merk</label>
                                    <input v-model="form.brand" type="text" :class="inputClass" placeholder="Contoh: Dell, HP, Lenovo" />
                                </div>
                                <div>
                                    <label :class="labelClass">Model / Tipe</label>
                                    <input v-model="form.model_type" type="text" :class="inputClass" placeholder="Contoh: Latitude 5540" />
                                </div>
                            </div>

                            <!-- Serial Number -->
                            <div class="mb-4">
                                <label :class="labelClass">Serial Number *</label>
                                <input v-model="form.serial_number" type="text" required :class="inputClass"
                                    placeholder="Contoh: DLL-2024-001" />
                                <p v-if="form.errors.serial_number" class="text-xs text-red-500 mt-1">{{ form.errors.serial_number }}</p>
                            </div>

                            <!-- Spesifikasi -->
                            <div class="mb-4">
                                <label :class="labelClass">Spesifikasi <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                                <textarea v-model="form.specification" rows="2" :class="inputClass"
                                    placeholder="Contoh: Core i7-1365U, 16GB RAM, 512GB SSD..."></textarea>
                            </div>

                            <!-- Kondisi & Status -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label :class="labelClass">Kondisi *</label>
                                    <select v-model="form.condition" required :class="inputClass">
                                        <option value="baik">Baik</option>
                                        <option value="cukup_baik">Cukup Baik</option>
                                        <option value="kurang_baik">Kurang Baik</option>
                                        <option value="rusak">Rusak</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Status *</label>
                                    <select v-model="form.status" required :class="inputClass">
                                        <option value="available">Tersedia</option>
                                        <option value="in_use">Digunakan</option>
                                        <option value="maintenance">Perbaikan</option>
                                        <option value="damaged">Rusak</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Lokasi & Departemen -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label :class="labelClass">Lokasi</label>
                                    <select v-model="form.location_id" :class="inputClass">
                                        <option value="">Pilih Lokasi</option>
                                        <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Departemen *</label>
                                    <select v-model="form.department_id" required :class="inputClass">
                                        <option value="">Pilih Departemen</option>
                                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }} ({{ dept.code }})</option>
                                        <option value="other">Lainnya (Input Manual)</option>
                                    </select>
                                    <p v-if="form.errors.department_id" class="text-xs text-red-500 mt-1">{{ form.errors.department_id }}</p>
                                </div>
                            </div>

                            <!-- Manual Dept Input (only if 'other' selected) -->
                            <div v-if="form.department_id === 'other'" class="mb-4 animate-fadeIn">
                                <label :class="labelClass">Nama/Kode Departemen Manual</label>
                                <input v-model="form.department" type="text" :class="inputClass" placeholder="Contoh: TEK, GA, IT" />
                            </div>

                            <!-- Pengguna/PIC -->
                            <div class="mb-4">
                                <label :class="labelClass">Pengguna / PIC</label>
                                <input v-model="form.current_holder" type="text" :class="inputClass"
                                    placeholder="Nama pemegang / penanggung jawab asset" />
                            </div>

                            <!-- Keterangan -->
                            <div class="mb-4">
                                <label :class="labelClass">Keterangan</label>
                                <textarea v-model="form.description" rows="2" :class="inputClass"
                                    placeholder="Catatan tambahan tentang asset..."></textarea>
                            </div>

                            <!-- Foto Asset -->
                            <div>
                                <label :class="labelClass">Foto Asset</label>
                                <div class="flex items-start gap-4">
                                    <div v-if="photoPreview"
                                        class="relative h-24 w-24 rounded-lg border border-gray-200 overflow-hidden flex-shrink-0">
                                        <img :src="photoPreview" class="h-full w-full object-cover" alt="Preview" />
                                        <button type="button" @click="removePhoto"
                                            class="absolute top-1 right-1 h-5 w-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition">
                                            ✕
                                        </button>
                                    </div>
                                    <label class="flex-1 cursor-pointer">
                                        <div class="flex items-center justify-center h-24 border-2 border-dashed border-gray-300 rounded-lg hover:border-emerald-400 hover:bg-emerald-50/30 transition-all">
                                            <div class="text-center">
                                                <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <p class="text-[10px] text-gray-400 mt-1 font-semibold">Klik untuk upload foto</p>
                                                <p class="text-[9px] text-gray-300">JPG, PNG maks 2MB</p>
                                            </div>
                                        </div>
                                        <input type="file" accept="image/*" @change="handlePhotoChange" class="hidden" />
                                    </label>
                                </div>
                                <p v-if="form.errors.photo" class="text-xs text-red-500 mt-1">{{ form.errors.photo }}</p>
                            </div>
                        </div>

                        <!-- Procurement Section -->
                        <div class="border-t border-gray-100 pt-6">
                            <h4 class="text-xs font-bold text-gray-800 mb-6">
                                Informasi Pembelian (Procurement)
                            </h4>

                            <!-- Vendor & Harga -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label :class="labelClass">Supplier</label>
                                    <select v-model="form.vendor_id" :class="inputClass">
                                        <option value="">Pilih Supplier</option>
                                        <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                                        <option value="other">Input Manual</option>
                                    </select>
                                </div>
                                <div v-if="form.vendor_id === 'other'">
                                    <label :class="labelClass">Nama Supplier Manual</label>
                                    <input v-model="form.vendor" type="text" :class="inputClass" placeholder="Contoh: Tokopedia, PT. ABC" />
                                </div>
                                <div>
                                    <label :class="labelClass">Harga Pembelian (Rp)</label>
                                    <input v-model="form.purchase_price" type="number" min="0" step="1" :class="inputClass" placeholder="0" />
                                </div>
                            </div>

                            <!-- Tanggal Pembelian & Garansi -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label :class="labelClass">Tanggal Pembelian</label>
                                    <input v-model="form.purchase_date" type="date" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Tanggal Garansi Berakhir</label>
                                    <input v-model="form.warranty_date" type="date" :class="inputClass" />
                                </div>
                            </div>

                            <!-- Masa Pemakaian -->
                            <div class="mb-4">
                                <label :class="labelClass">Masa Pemakaian</label>
                                <input v-model="form.usage_period" type="text" :class="inputClass" placeholder="Contoh: 3 Tahun, 5 Tahun" />
                            </div>

                            <!-- Catatan Tambahan -->
                            <div>
                                <label :class="labelClass">Catatan Tambahan</label>
                                <textarea v-model="form.notes" rows="2" :class="inputClass"
                                    placeholder="Catatan terkait pembelian atau garansi..."></textarea>
                            </div>
                        </div>

                        <!-- ===================== ACTIONS ===================== -->
                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                            <button type="button" @click="close"
                                class="rounded-lg border border-gray-300 px-6 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-emerald-600 px-8 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50">
                                {{ isEditing ? 'Update' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
