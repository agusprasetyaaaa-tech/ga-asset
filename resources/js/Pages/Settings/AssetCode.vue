<script setup>
/**
 * Asset Code Template Settings Page - Updated
 * Supports custom formats like LTP/GA/2/2025
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    setting: Object,
    preview: String,
});

const form = useForm({
    prefix: props.setting.prefix,
    separator: props.setting.separator,
    date_format: props.setting.date_format,
    date_position: props.setting.date_position || 'middle',
    digit_length: props.setting.digit_length,
    reset_period: props.setting.reset_period,
});

const dateSegmentMap = {
    'none': '',
    'Y': new Date().getFullYear().toString(),
    'Ym': new Date().getFullYear().toString() + String(new Date().getMonth() + 1).padStart(2, '0'),
    'Ymd': new Date().getFullYear().toString() + String(new Date().getMonth() + 1).padStart(2, '0') + String(new Date().getDate()).padStart(2, '0'),
    'ym': String(new Date().getFullYear()).slice(-2) + String(new Date().getMonth() + 1).padStart(2, '0'),
    'ymd': String(new Date().getFullYear()).slice(-2) + String(new Date().getMonth() + 1).padStart(2, '0') + String(new Date().getDate()).padStart(2, '0'),
};

const dateFormatLabels = {
    'none': 'Tidak ada',
    'Y': 'Tahun (YYYY) → ' + dateSegmentMap['Y'],
    'Ym': 'Tahun+Bulan (YYYYMM) → ' + dateSegmentMap['Ym'],
    'Ymd': 'Tahun+Bulan+Hari (YYYYMMDD) → ' + dateSegmentMap['Ymd'],
    'ym': 'Tahun+Bulan Singkat (YYMM) → ' + dateSegmentMap['ym'],
    'ymd': 'Lengkap Singkat (YYMMDD) → ' + dateSegmentMap['ymd'],
};

const resetLabels = {
    'never': 'Tidak pernah — nomor terus naik',
    'yearly': 'Setiap tahun — reset ke 1 di awal tahun',
    'monthly': 'Setiap bulan — reset ke 1 di awal bulan',
};

const separatorOptions = [
    { value: '-', label: 'Dash ( - )' },
    { value: '/', label: 'Slash ( / )' },
    { value: '.', label: 'Dot ( . )' },
    { value: '_', label: 'Underscore ( _ )' },
];

const livePreview = computed(() => {
    const prefix = form.prefix || 'AST';
    const dateSegment = dateSegmentMap[form.date_format] || '';
    const sep = form.separator || '-';
    const digits = form.digit_length || 1;
    const nextNum = props.setting.next_number || 1;
    const numberStr = String(nextNum).padStart(digits, '0');

    let parts = [prefix];
    if (form.date_position === 'end') {
        parts.push(numberStr);
        if (dateSegment) parts.push(dateSegment);
    } else {
        if (dateSegment) parts.push(dateSegment);
        parts.push(numberStr);
    }
    
    return parts.join(sep);
});

const sampleCodes = computed(() => {
    const parts = (num) => {
        const prefix = form.prefix || 'AST';
        const dateSegment = dateSegmentMap[form.date_format] || '';
        const digits = form.digit_length || 1;
        const numberStr = String(num).padStart(digits, '0');
        const sep = form.separator || '-';
        
        let p = [prefix];
        if (form.date_position === 'end') {
            p.push(numberStr);
            if (dateSegment) p.push(dateSegment);
        } else {
            if (dateSegment) p.push(dateSegment);
            p.push(numberStr);
        }
        return p.join(sep);
    };
    return [parts(1), parts(2), parts(3), parts(50), parts(100)];
});

const hasChanges = computed(() => {
    return form.prefix !== props.setting.prefix ||
        form.separator !== props.setting.separator ||
        form.date_format !== props.setting.date_format ||
        form.date_position !== props.setting.date_position ||
        form.digit_length !== props.setting.digit_length ||
        form.reset_period !== props.setting.reset_period;
});

const submitForm = () => {
    form.put(route('settings.asset-code.update'), {
        preserveScroll: true,
    });
};

const resetCounter = () => {
    if (confirm('Reset nomor urut ke 1?')) {
        router.post(route('settings.asset-code.reset'), {}, {
            preserveScroll: true,
        });
    }
};

const inputClass = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition';
const labelClass = 'block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5';
</script>

<template>
    <Head title="Template Kode Asset" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Konfigurasi Kode Asset</h2>
                    <p class="text-xs text-gray-400 font-medium mt-1">Konfigurasi Struktur Penomoran Otomatis dan Format Barcode Aset</p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                    <!-- Settings Form (Left) -->
                    <div class="flex flex-col">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-800">Format Dinamis Baru</h3>
                                        <p class="text-[10px] text-gray-400 font-medium">Contoh: LAPTOP1/TEK/4/2026</p>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="submitForm" class="p-6 space-y-5">
                                <!-- Prefix -->
                                <div>
                                    <label :class="labelClass">Prefix Default (Fallback) *</label>
                                    <input v-model="form.prefix" type="text" :class="inputClass" maxlength="20"
                                        placeholder="Contoh: AST" required />
                                    <p class="text-[10px] text-gray-400 mt-1">Kode ini hanya digunakan jika Subkategori <b>tidak memiliki</b> kode sendiri. Mendukung karakter (/) dan (.).</p>
                                    <p v-if="form.errors.prefix" class="text-xs text-red-500 mt-1">{{ form.errors.prefix }}</p>
                                </div>

                                <!-- Separator -->
                                <div>
                                    <label :class="labelClass">Pemisah (Separator) *</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <label v-for="opt in separatorOptions" :key="opt.value"
                                            class="flex items-center justify-center px-3 py-2.5 rounded-lg border cursor-pointer transition-all text-sm font-semibold"
                                            :class="form.separator === opt.value 
                                                ? 'bg-emerald-50 border-emerald-300 text-emerald-700' 
                                                : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'">
                                            <input type="radio" v-model="form.separator" :value="opt.value" class="hidden" />
                                            {{ opt.label }}
                                        </label>
                                    </div>
                                </div>

                                <!-- Date Settings -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label :class="labelClass">Format Tanggal *</label>
                                        <select v-model="form.date_format" :class="inputClass">
                                            <option v-for="(label, value) in dateFormatLabels" :key="value" :value="value">{{ label }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label :class="labelClass">Posisi Tanggal *</label>
                                        <select v-model="form.date_position" :class="inputClass">
                                            <option value="middle">Tengah (setelah Prefix)</option>
                                            <option value="end">Akhir (setelah No Urut)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Digit Length -->
                                <div>
                                    <label :class="labelClass">Jumlah Digit Nomor Urut *</label>
                                    <div class="grid grid-cols-8 gap-2">
                                        <label v-for="n in [1, 2, 3, 4, 5, 6, 7, 8]" :key="n"
                                            class="flex items-center justify-center px-3 py-2.5 rounded-lg border cursor-pointer transition-all text-sm font-bold"
                                            :class="form.digit_length === n 
                                                ? 'bg-emerald-50 border-emerald-300 text-emerald-700' 
                                                : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'">
                                            <input type="radio" v-model="form.digit_length" :value="n" class="hidden" />
                                            {{ n }}
                                        </label>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">Gunakan 1 digit jika ingin nomor tanpa nol di depan (contoh : 1, 2, 10).</p>
                                </div>

                                <!-- Reset Period -->
                                <div>
                                    <label :class="labelClass">Reset Nomor Urut *</label>
                                    <div class="space-y-2">
                                        <label v-for="(label, value) in resetLabels" :key="value"
                                            class="flex items-start gap-3 px-4 py-3 rounded-lg border cursor-pointer transition-all"
                                            :class="form.reset_period === value 
                                                ? 'bg-emerald-50 border-emerald-300 ring-1 ring-emerald-200' 
                                                : 'bg-white border-gray-200 hover:bg-gray-50'">
                                            <input type="radio" v-model="form.reset_period" :value="value" 
                                                class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                                            <div>
                                                <span class="text-sm font-semibold text-gray-800 capitalize">{{ value === 'never' ? 'Tidak Pernah' : value === 'yearly' ? 'Tahunan' : 'Bulanan' }}</span>
                                                <p class="text-[10px] text-gray-400 mt-0.5">{{ label }}</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <button type="button" @click="resetCounter"
                                        class="text-xs font-bold text-rose-500 hover:text-rose-700 transition">
                                        Reset Nomor Urut
                                    </button>
                                    <button type="submit" :disabled="form.processing || !hasChanges"
                                        class="rounded-lg bg-emerald-600 px-8 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-40">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Live Preview (Right) -->
                    <div class="flex flex-col gap-6">
                        <!-- Preview Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white">
                                <h3 class="text-sm font-bold text-emerald-800">Live Preview (Struktur Utama)</h3>
                                <p class="text-[10px] text-emerald-600 font-medium">Berdasarkan prefix default di samping</p>
                            </div>
                            <div class="p-6">
                                <div class="w-full text-center p-6 bg-emerald-50/30 border-2 border-dashed border-emerald-100 rounded-2xl">
                                    <p class="font-mono text-xl font-bold text-emerald-600 tracking-wider">
                                        {{ livePreview }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 mt-2 font-bold uppercase tracking-widest">Preview Menggunakan Prefix Default ({{ form.prefix }})</p>
                                </div>
                            </div>

                            <!-- Simulasi Subkategori -->
                            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">Penjelasan Format</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3">
                                        <div class="h-5 w-5 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold shrink-0">1</div>
                                        <p class="text-[11px] text-gray-500 leading-relaxed"><b class="text-gray-700">Prefix Subkategori</b>: Diambil dari field "Kode / Prefix" di menu Kategori. (Contoh: LAPTOP, AC, MTR)</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="h-5 w-5 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold shrink-0">2</div>
                                        <p class="text-[11px] text-gray-500 leading-relaxed"><b class="text-gray-700">Nomor Urut</b>: Otomatis bertambah +1 per masing-masing prefix subkategori secara global.</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="h-5 w-5 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold shrink-0">3</div>
                                        <p class="text-[11px] text-gray-500 leading-relaxed"><b class="text-gray-700">Departemen, Bulan & Tahun</b>: Diambil dinamis dari input form asset (Dept, Tgl Beli).</p>
                                    </div>
                                </div>
                                <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-lg">
                                    <p class="text-[10px] text-amber-700 leading-relaxed font-medium">
                                        <b>Pro Tip:</b> Jika Anda ingin nomor urut dengan nol di depan (misal: 001), silakan atur "Jumlah Digit Nomor Urut" di samping menjadi lebih dari 1.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Codes -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-bold text-gray-800">Contoh Urutan</h3>
                            </div>
                            <div class="p-4">
                                <div class="space-y-1.5">
                                    <div v-for="(code, i) in sampleCodes" :key="i"
                                        class="flex items-center justify-between px-4 py-2.5 rounded-lg"
                                        :class="i === 0 ? 'bg-emerald-50 border border-emerald-100' : 'bg-gray-50'">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">
                                            {{ i === 0 ? '#1' : i === 1 ? '#2' : i === 2 ? '#3' : i === 3 ? '#50' : '#100' }}
                                        </span>
                                        <span class="font-mono text-xs font-bold" :class="i === 0 ? 'text-emerald-700' : 'text-gray-700'">
                                            {{ code }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
