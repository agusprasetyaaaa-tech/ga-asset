<script setup>
/**
 * MovementFormModal Component
 * Modal form for recording asset movements (location/holder changes).
 */
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    asset: { type: Object, default: null },
    locations: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    asset_id: '',
    to_location_id: '',
    to_holder: '',
    notes: '',
});

watch(() => props.show, (val) => {
    if (val && props.asset) {
        form.asset_id = props.asset.id;
        form.to_location_id = '';
        form.to_holder = '';
        form.notes = '';
    }
});

const submit = () => {
    form.post(route('movements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};

const close = () => {
    form.clearErrors();
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
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close"></div>

                <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Catat Perpindahan Asset</h3>
                        <button @click="close" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Current Info -->
                    <div v-if="asset" class="mx-6 mt-4 rounded-xl bg-gray-50 p-4 border border-gray-200">
                        <p class="text-sm text-gray-500">Asset: <strong class="text-gray-900">{{ asset.name }}</strong></p>
                        <p class="text-sm text-gray-500">Lokasi saat ini: <strong class="text-gray-900">{{ asset.location?.name || '-' }}</strong></p>
                        <p class="text-sm text-gray-500">Pemegang saat ini: <strong class="text-gray-900">{{ asset.current_holder || '-' }}</strong></p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Tujuan *</label>
                            <select v-model="form.to_location_id" required
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition">
                                <option value="">Pilih Lokasi</option>
                                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Penerima / Pemegang Baru</label>
                            <input v-model="form.to_holder" type="text"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition"
                                placeholder="Nama penerima" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea v-model="form.notes" rows="3"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition"
                                placeholder="Alasan/catatan perpindahan..."></textarea>
                        </div>

                        <div class="flex justify-end gap-3 border-t pt-4">
                            <button type="button" @click="close"
                                class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Perpindahan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
