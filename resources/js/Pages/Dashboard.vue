<script setup>
/**
 * Dashboard Page
 * Main landing page showing inventory statistics, recent movements, and maintenance logs.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentMovements: Array,
    recentMaintenance: Array,
});

const statCards = [
    { key: 'total', label: 'Total Asset', icon: '📦', color: 'from-indigo-500 to-indigo-600', bg: 'bg-indigo-50' },
    { key: 'available', label: 'Tersedia', icon: '✅', color: 'from-emerald-500 to-emerald-600', bg: 'bg-emerald-50' },
    { key: 'in_use', label: 'Digunakan', icon: '👤', color: 'from-blue-500 to-blue-600', bg: 'bg-blue-50' },
    { key: 'maintenance', label: 'Perbaikan', icon: '🔧', color: 'from-amber-500 to-amber-600', bg: 'bg-amber-50' },
    { key: 'damaged', label: 'Rusak', icon: '⚠️', color: 'from-red-500 to-red-600', bg: 'bg-red-50' },
    { key: 'locations', label: 'Lokasi', icon: '📍', color: 'from-purple-500 to-purple-600', bg: 'bg-purple-50' },
];

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-baseline sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Dashboard Inventory</h2>
                    <p class="text-xs text-gray-400 font-medium">Ringkasan Statistik dan Aktivitas Aset Terbaru</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('scanner.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-gray-800 transition-all active:scale-95">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        Scan Barcode
                    </Link>
                    <Link :href="route('assets.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-all active:scale-95">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Kelola Asset
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <div v-for="card in statCards" :key="card.key"
                        class="group relative overflow-hidden rounded-xl bg-white p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                        <div class="flex flex-col">
                            <span class="text-2xl mb-2">{{ card.icon }}</span>
                            <span class="text-3xl font-bold text-gray-900">{{ stats[card.key] || 0 }}</span>
                            <span class="mt-1 text-sm text-gray-500">{{ card.label }}</span>
                        </div>
                        <div :class="card.bg" class="absolute -right-3 -top-3 h-16 w-16 rounded-full opacity-50 group-hover:opacity-80 transition"></div>
                    </div>
                </div>

                <!-- Recent Activity Grid -->
                <div class="grid gap-6 lg:grid-cols-2">

                    <!-- Recent Movements -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between border-b px-6 py-4">
                            <h3 class="font-bold text-gray-900">Perpindahan Terbaru</h3>
                            <Link :href="route('assets.index')" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                Lihat Semua →
                            </Link>
                        </div>
                        <div class="divide-y">
                            <div v-for="movement in recentMovements" :key="movement.id"
                                class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ movement.asset?.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        → {{ movement.to_location?.name || '-' }}
                                        <span v-if="movement.to_holder"> • {{ movement.to_holder }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(movement.created_at) }} oleh {{ movement.user?.name }}</p>
                                </div>
                            </div>
                            <div v-if="!recentMovements?.length" class="px-6 py-8 text-center text-sm text-gray-400">
                                Belum ada perpindahan tercatat.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Maintenance -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between border-b px-6 py-4">
                            <h3 class="font-bold text-gray-900">Maintenance Terbaru</h3>
                            <Link :href="route('maintenance.index')" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                Lihat Semua →
                            </Link>
                        </div>
                        <div class="divide-y">
                            <div v-for="log in recentMaintenance" :key="log.id"
                                class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ log.asset?.name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ log.description }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ log.type === 'repair' ? 'Perbaikan' : log.type === 'replacement' ? 'Pergantian' : 'Inspeksi' }}
                                        • {{ formatDate(log.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!recentMaintenance?.length" class="px-6 py-8 text-center text-sm text-gray-400">
                                Belum ada log maintenance.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
