<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { watch, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    asset: Object,
});

const emit = defineEmits(['close']);
const page = usePage();

// Get departments from shared props or page props
const departments = computed(() => page.props.departments || []);

const form = useForm({
    asset_id: '',
    borrower_name: '',
    borrower_department: '',
    expected_return_date: '',
    notes: '',
});

watch(() => props.asset, (newAsset) => {
    if (newAsset) {
        form.asset_id = newAsset.id;
    }
}, { immediate: true });

const submit = () => {
    form.post(route('loans.store'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
};

const closeModal = () => {
    emit('close');
};
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
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>
                
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fadeIn">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-blue-600 text-white flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-sm tracking-wide">Proses Pinjam Aset</h3>
                            <p class="text-[10px] text-blue-100 mt-0.5">{{ asset?.name }} ({{ asset?.asset_code }})</p>
                        </div>
                        <button @click="closeModal" class="hover:bg-blue-500 rounded-lg p-1.5 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nama Peminjam *</label>
                            <input v-model="form.borrower_name" type="text" required placeholder="Siapa yang meminjam?"
                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" />
                            <p v-if="form.errors.borrower_name" class="text-[10px] text-red-500 mt-1">{{ form.errors.borrower_name }}</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Departemen Peminjam *</label>
                            <select v-model="form.borrower_department" required
                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition">
                                <option value="">-- Pilih Departemen --</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                            </select>
                            <p v-if="form.errors.borrower_department" class="text-[10px] text-red-500 mt-1">{{ form.errors.borrower_department }}</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Rencana Kembali (Opsional)</label>
                            <input v-model="form.expected_return_date" type="date" 
                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Keperluan / Catatan</label>
                            <textarea v-model="form.notes" rows="3" placeholder="Contoh: Untuk keperluan meeting di luar kantor..."
                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"></textarea>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-4 border-t border-gray-100">
                            <button type="button" @click="closeModal" 
                                class="px-5 py-2.5 text-[11px] font-bold text-gray-500 uppercase tracking-wider rounded-xl border border-gray-200 hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="bg-blue-600 text-white px-8 py-2.5 rounded-xl text-[11px] font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 uppercase tracking-wider transition-all disabled:opacity-50">
                                {{ form.processing ? 'Memproses...' : 'Konfirmasi Pinjam' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.animate-fadeIn { animation: fadeIn 0.2s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
