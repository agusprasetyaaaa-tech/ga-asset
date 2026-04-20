<script setup>
/**
 * Vendors Index Page
 * Manage vendors for asset procurement.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    vendors: Array,
});

const showModal = ref(false);
const editingVendor = ref(null);

const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    description: '',
});

const openCreate = () => {
    editingVendor.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (vendor) => {
    editingVendor.value = vendor;
    form.name = vendor.name;
    form.contact_person = vendor.contact_person || '';
    form.phone = vendor.phone || '';
    form.email = vendor.email || '';
    form.address = vendor.address || '';
    form.description = vendor.description || '';
    showModal.value = true;
};

const submit = () => {
    if (editingVendor.value) {
        form.put(route('vendors.update', editingVendor.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('vendors.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
};

const deleteVendor = (vendor) => {
    if (confirm(`Hapus vendor "${vendor.name}"?`)) {
        router.delete(route('vendors.destroy', vendor.id));
    }
};

const inputClass = 'w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition text-sm';
const labelClass = 'block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5';
</script>

<template>
    <Head title="Manajemen Supplier" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen Supplier</h2>
                    <p class="text-xs text-gray-500 mt-1">Kelola daftar penyuplai, kontak person, dan informasi pembelian aset perusahaan.</p>
                </div>
                <button @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-all duration-200 uppercase tracking-widest">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Supplier
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
                    <div v-for="vendor in vendors" :key="vendor.id"
                        class="group rounded-2xl bg-white p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-100 text-orange-600 group-hover:bg-orange-200 transition">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900 leading-tight">{{ vendor.name }}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span v-if="vendor.contact_person" class="text-xs font-semibold text-gray-500">{{ vendor.contact_person }}</span>
                                        <span v-if="vendor.phone" class="text-xs text-emerald-600 font-bold">• {{ vendor.phone }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openEdit(vendor)"
                                    class="rounded-lg p-2 text-gray-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteVendor(vendor)"
                                    class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 transition" title="Hapus">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-if="vendor.email" class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                {{ vendor.email }}
                            </div>
                            <div v-if="vendor.address" class="flex items-start gap-2 text-xs text-gray-500">
                                <svg class="h-4 w-4 text-gray-300 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                <span class="line-clamp-1">{{ vendor.address }}</span>
                            </div>
                        </div>

                        <p v-if="vendor.description" class="mt-3 text-xs text-gray-400 italic">{{ vendor.description }}</p>
                    </div>

                    <div v-if="!vendors?.length" class="col-span-full rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                        <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <p class="text-sm text-gray-400">Belum ada supplier. Klik "Tambah Supplier" untuk memulai.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden">
                        <div class="flex items-center justify-between bg-emerald-600 px-6 py-5 shadow-sm">
                            <h3 class="text-lg font-bold text-white tracking-tight">{{ editingVendor ? 'Edit Supplier' : 'Tambah Supplier' }}</h3>
                            <button @click="showModal = false" class="rounded-lg p-1.5 text-white hover:bg-emerald-500 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <form @submit.prevent="submit" class="p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-full">
                                    <label :class="labelClass">Nama Supplier *</label>
                                    <input v-model="form.name" type="text" required :class="inputClass" placeholder="Contoh: PT. Sumber Makmur" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-medium">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label :class="labelClass">Kontak Person</label>
                                    <input v-model="form.contact_person" type="text" :class="inputClass" placeholder="Nama PIC" />
                                </div>
                                <div>
                                    <label :class="labelClass">No. Telepon / WA</label>
                                    <input v-model="form.phone" type="text" :class="inputClass" placeholder="0812xxxx" />
                                </div>
                                <div class="col-span-full">
                                    <label :class="labelClass">Email</label>
                                    <input v-model="form.email" type="email" :class="inputClass" placeholder="vendor@email.com" />
                                </div>
                                <div class="col-span-full">
                                    <label :class="labelClass">Alamat</label>
                                    <textarea v-model="form.address" rows="2" :class="inputClass" placeholder="Alamat lengkap..."></textarea>
                                </div>
                                <div class="col-span-full">
                                    <label :class="labelClass">Deskripsi / Produk</label>
                                    <textarea v-model="form.description" rows="2" :class="inputClass" placeholder="Deskripsi singkat..."></textarea>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 border-t pt-5 mt-2">
                                <button type="button" @click="showModal = false"
                                    class="rounded-lg border border-gray-300 px-6 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition uppercase tracking-widest">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="rounded-lg bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-700 disabled:opacity-50 transition uppercase tracking-widest">
                                    {{ form.processing ? 'Menyimpan...' : (editingVendor ? 'Perbarui' : 'Simpan') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
