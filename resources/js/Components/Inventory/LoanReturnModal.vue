<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    loan: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    return_notes: '',
    asset_condition: 'baik',
});

const submit = () => {
    form.patch(route('loans.return', props.loan.id), {
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
};

const inputClass = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition placeholder:text-gray-400';
const labelClass = 'block text-[10px] font-bold text-gray-500 mb-1.5';
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="emit('close')"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fadeIn">
            <div class="px-6 py-4 bg-emerald-600 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-bold">Proses Pengembalian Aset</h3>
                    <p class="text-[10px] opacity-80">Peminjam: {{ loan.borrower_name }}</p>
                </div>
                <button @click="emit('close')" class="hover:bg-emerald-500 rounded p-1 transition">✕</button>
            </div>
            
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label :class="labelClass">Kondisi Aset Saat Kembali *</label>
                    <select v-model="form.asset_condition" required :class="inputClass">
                        <option value="baik">Baik</option>
                        <option value="cukup_baik">Cukup Baik</option>
                        <option value="kurang_baik">Kurang Baik</option>
                        <option value="rusak">Rusak</option>
                    </select>
                </div>

                <div>
                    <label :class="labelClass">Catatan Pengembalian</label>
                    <textarea v-model="form.return_notes" rows="3" :class="inputClass" placeholder="Misal: Barang kembali lengkap, ada goresan halus, dll..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" @click="emit('close')" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-50 rounded-lg transition">Batal</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition shadow-sm">Proses Kembali</button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
