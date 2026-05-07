<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    assets: Array,
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};

const getStatusClass = (date) => {
    if (!date) return '';
    const diff = new Date(date) - new Date();
    const days = diff / (1000 * 60 * 60 * 24);
    
    if (days < 0) return 'bg-rose-100 text-rose-700 border-rose-200'; // Expired
    if (days <= 30) return 'bg-amber-100 text-amber-700 border-amber-200 animate-pulse'; // Expiring soon
    return 'bg-blue-100 text-blue-700 border-blue-200';
};
</script>

<template>
    <Head title="Monitoring Garansi & Lisensi" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Monitoring Garansi & Lisensi</h2>
                <p class="text-xs text-gray-500 mt-1">Daftar aset dengan masa berlaku yang akan berakhir.</p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="min-w-full text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Asset</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Serial / License</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Garansi Habis</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Lisensi Habis</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-900">{{ asset.name }}</span>
                                        <span class="text-[10px] text-emerald-700 font-mono">{{ asset.asset_code }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span v-if="asset.serial_number" class="text-[10px] text-gray-500"><span class="font-bold">SN:</span> {{ asset.serial_number }}</span>
                                        <span v-if="asset.license_key" class="text-[10px] text-emerald-600"><span class="font-bold">Key:</span> {{ asset.license_key }}</span>
                                        <span v-if="!asset.serial_number && !asset.license_key" class="text-xs text-gray-400">-</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="asset.warranty_date" 
                                        class="text-[10px] font-bold px-2 py-1 rounded-full border"
                                        :class="getStatusClass(asset.warranty_date)">
                                        {{ formatDate(asset.warranty_date) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-300">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="asset.license_expiration_date" 
                                        class="text-[10px] font-bold px-2 py-1 rounded-full border"
                                        :class="getStatusClass(asset.license_expiration_date)">
                                        {{ formatDate(asset.license_expiration_date) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-300">-</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('assets.show', asset.id)" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition">
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!assets.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-xs">Semua aman. Tidak ada aset yang masa berlakunya mendekati batas dalam 60 hari.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
