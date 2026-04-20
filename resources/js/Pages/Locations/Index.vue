<script setup>
/**
 * Locations Index Page - Standardized UI
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    locations: Array,
});

const showModal = ref(false);
const editingLocation = ref(null);

const form = useForm({
    name: '',
    description: '',
});

const openCreate = () => {
    editingLocation.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (location) => {
    editingLocation.value = location;
    form.name = location.name;
    form.description = location.description || '';
    showModal.value = true;
};

const submit = () => {
    if (editingLocation.value) {
        form.put(route('locations.update', editingLocation.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('locations.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const deleteLocation = (location) => {
    if (confirm(`Hapus lokasi "${location.name}"?`)) {
        router.delete(route('locations.destroy', location.id));
    }
};
</script>

<template>
    <Head title="Manajemen Lokasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-baseline sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manajemen Lokasi</h2>
                    <p class="text-xs text-gray-400 font-medium mt-1">Definisikan Lokasi dan Area Penyimpanan Aset Perusahaan</p>
                </div>
                <button @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-all active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Lokasi
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="location in locations" :key="location.id"
                        class="group rounded-xl bg-white p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 shadow-sm">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 leading-tight">{{ location.name }}</h3>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ location.assets_count || 0 }} Aset Terdaftar</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openEdit(location)"
                                    class="rounded-lg p-2 text-gray-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Edit">
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteLocation(location)"
                                    class="rounded-lg p-2 text-gray-400 hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus">
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-if="location.description" class="mt-4 pt-4 border-t border-gray-50">
                            <p class="text-[11px] text-gray-400 leading-relaxed italic line-clamp-2">"{{ location.description }}"</p>
                        </div>
                    </div>

                    <div v-if="!locations?.length" class="col-span-full rounded-xl border-2 border-dashed border-gray-100 p-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="h-14 w-14 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="h-8 w-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Belum Ada Lokasi</p>
                            <p class="text-[10px] text-gray-300 mt-1">Klik tombol diatas untuk menambahkan lokasi baru</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl overflow-hidden border border-gray-100">
                        <div class="flex items-center justify-between bg-emerald-600 px-6 py-5 shadow-sm">
                            <h3 class="text-sm font-bold text-white uppercase tracking-widest">{{ editingLocation ? 'Edit Lokasi' : 'Tambah Lokasi' }}</h3>
                            <button @click="showModal = false" class="rounded-lg p-1.5 text-white hover:bg-emerald-500 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <form @submit.prevent="submit" class="p-8 space-y-6">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Nama Lokasi *</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm py-2.5"
                                    placeholder="Contoh: Gudang Utama" />
                                <p v-if="form.errors.name" class="mt-2 text-[10px] font-bold text-rose-600 uppercase">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Deskripsi (Opsional)</label>
                                <textarea v-model="form.description" rows="3"
                                    class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm"
                                    placeholder="Berikan detail singkat lokasi..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-6 border-t border-gray-50">
                                <button type="button" @click="showModal = false"
                                    class="rounded-lg border border-gray-200 px-6 py-3 text-[11px] font-bold text-gray-400 hover:bg-gray-50 transition uppercase tracking-widest">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="rounded-lg bg-emerald-600 px-8 py-3 text-[11px] font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition uppercase tracking-widest active:scale-95">
                                    {{ form.processing ? '...' : (editingLocation ? 'Perbarui' : 'Simpan') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
