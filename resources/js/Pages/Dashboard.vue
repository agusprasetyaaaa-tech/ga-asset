<script setup>
/**
 * Dashboard Page
 * Main landing page showing inventory statistics, charts, recent movements, and maintenance logs.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Inventory/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Doughnut, Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement,
    Title, Tooltip, Legend, ArcElement, PointElement, LineElement, Filler
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement, PointElement, LineElement, Filler);

const props = defineProps({
    stats: Object,
    chartData: Object,
    recentMovements: Array,
    recentMaintenance: Array,
    recentLogs: Array,
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

const formatCurrency = (value) => {
    if (!value) return 'Rp 0';
    return 'Rp ' + Number(value).toLocaleString('id-ID');
};

// --- Chart Configurations ---
const chartColors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316'];

const statusChartData = computed(() => ({
    labels: props.chartData?.assetsByStatus?.map(i => i.label) || [],
    datasets: [{
        data: props.chartData?.assetsByStatus?.map(i => i.value) || [],
        backgroundColor: props.chartData?.assetsByStatus?.map(i => i.color) || [],
        borderWidth: 0,
        hoverOffset: 8,
    }],
}));

const statusChartOptions = {
    responsive: true, maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 11, weight: 'bold' } } },
        tooltip: { backgroundColor: '#1f2937', titleFont: { size: 12 }, bodyFont: { size: 11 }, padding: 10, cornerRadius: 8 },
    },
};

const categoryChartData = computed(() => ({
    labels: props.chartData?.assetsByCategory?.map(i => i.label) || [],
    datasets: [{
        label: 'Jumlah Aset',
        data: props.chartData?.assetsByCategory?.map(i => i.value) || [],
        backgroundColor: chartColors.slice(0, props.chartData?.assetsByCategory?.length || 0).map(c => c + '22'),
        borderColor: chartColors.slice(0, props.chartData?.assetsByCategory?.length || 0),
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

const categoryChartOptions = {
    responsive: true, maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1f2937', cornerRadius: 8, padding: 10 },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } },
        y: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, color: '#4b5563' } },
    },
};

const departmentChartData = computed(() => ({
    labels: props.chartData?.assetsByDepartment?.map(i => i.label) || [],
    datasets: [{
        label: 'Jumlah Aset',
        data: props.chartData?.assetsByDepartment?.map(i => i.value) || [],
        backgroundColor: chartColors.slice(0, props.chartData?.assetsByDepartment?.length || 0).map(c => c + '22'),
        borderColor: chartColors.slice(0, props.chartData?.assetsByDepartment?.length || 0),
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

const departmentChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1f2937', cornerRadius: 8, padding: 10 },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, color: '#4b5563' } },
        y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10, weight: 'bold' } } },
    },
};

const trendChartData = computed(() => ({
    labels: props.chartData?.monthlyTrend?.map(i => i.month) || [],
    datasets: [{
        label: 'Aset Baru',
        data: props.chartData?.monthlyTrend?.map(i => i.count) || [],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.08)',
        fill: true,
        tension: 0.4,
        borderWidth: 2.5,
        pointBackgroundColor: '#10b981',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 7,
    }],
}));

const trendChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1f2937', cornerRadius: 8, padding: 10 },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 9, weight: 'bold' }, color: '#9ca3af', maxRotation: 45 } },
        y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10, weight: 'bold' }, stepSize: 1 }, beginAtZero: true },
    },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-baseline sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">Dashboard Asset Management</h2>
                    <p class="text-xs text-gray-500 mt-1">Ringkasan Statistik dan Aktivitas Aset Terbaru</p>
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

                <!-- Total Asset Value Card -->
                <div class="rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 p-6 shadow-lg border border-emerald-500/20">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider">Total Nilai Aset</p>
                                <p class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ formatCurrency(stats.totalValue) }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-emerald-100 font-medium bg-white/10 px-3 py-1.5 rounded-lg">
                            {{ stats.total || 0 }} aset tercatat
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1: Status Doughnut + Category Bar -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Status Distribution -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 text-sm">Distribusi Status Aset</h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Proporsi aset berdasarkan status saat ini</p>
                        </div>
                        <div class="p-6 flex items-center justify-center" style="height: 280px;">
                            <Doughnut v-if="chartData?.assetsByStatus?.length" :data="statusChartData" :options="statusChartOptions" />
                            <p v-else class="text-sm text-gray-400">Belum ada data aset</p>
                        </div>
                    </div>

                    <!-- By Category -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 text-sm">Aset per Kategori</h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Jumlah aset pada setiap kategori utama</p>
                        </div>
                        <div class="p-6" style="height: 280px;">
                            <Bar v-if="chartData?.assetsByCategory?.length" :data="categoryChartData" :options="categoryChartOptions" />
                            <div v-else class="flex items-center justify-center h-full">
                                <p class="text-sm text-gray-400">Belum ada data kategori</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2: Department Bar + Monthly Trend Line -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- By Department -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 text-sm">Aset per Departemen</h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Top 8 departemen dengan aset terbanyak</p>
                        </div>
                        <div class="p-6" style="height: 280px;">
                            <Bar v-if="chartData?.assetsByDepartment?.length" :data="departmentChartData" :options="departmentChartOptions" />
                            <div v-else class="flex items-center justify-center h-full">
                                <p class="text-sm text-gray-400">Belum ada data departemen</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trend -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 text-sm">Tren Penambahan Aset</h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Jumlah aset baru dalam 12 bulan terakhir</p>
                        </div>
                        <div class="p-6" style="height: 280px;">
                            <Line v-if="chartData?.monthlyTrend?.length" :data="trendChartData" :options="trendChartOptions" />
                            <div v-else class="flex items-center justify-center h-full">
                                <p class="text-sm text-gray-400">Belum ada data tren</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Grid -->
                <div class="grid gap-6 lg:grid-cols-3">

                    <!-- Recent Movements -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between border-b px-6 py-4 bg-gray-50/50">
                            <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Perpindahan</h3>
                            <Link :href="route('assets.index')" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold uppercase">
                                Lihat Semua
                            </Link>
                        </div>
                        <div class="divide-y max-h-[400px] overflow-y-auto">
                            <div v-for="movement in recentMovements" :key="movement.id"
                                class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-gray-900 truncate">{{ movement.asset?.name }}</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5 truncate">
                                        → {{ movement.to_location?.name || '-' }}
                                        <span v-if="movement.to_holder" class="text-indigo-600"> • {{ movement.to_holder }}</span>
                                    </p>
                                    <p class="text-[9px] text-gray-400 mt-1 font-medium">{{ formatDate(movement.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="!recentMovements?.length" class="px-6 py-8 text-center text-xs text-gray-400">
                                Belum ada perpindahan.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Maintenance -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between border-b px-6 py-4 bg-gray-50/50">
                            <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Maintenance</h3>
                            <Link :href="route('maintenance.index')" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold uppercase">
                                Lihat Semua
                            </Link>
                        </div>
                        <div class="divide-y max-h-[400px] overflow-y-auto">
                            <div v-for="log in recentMaintenance" :key="log.id"
                                class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-gray-900 truncate">{{ log.asset?.name }}</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5 truncate">{{ log.description }}</p>
                                    <p class="text-[9px] text-gray-400 mt-1 font-medium">
                                        {{ log.type === 'repair' ? 'Perbaikan' : 'Inspeksi' }} • {{ formatDate(log.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!recentMaintenance?.length" class="px-6 py-8 text-center text-xs text-gray-400">
                                Belum ada log.
                            </div>
                        </div>
                    </div>

                    <!-- Audit Log (Recent Activity) -->
                    <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between border-b px-6 py-4 bg-gray-50/50">
                            <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Aktivitas Sistem</h3>
                            <span class="text-[9px] font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Live Feed</span>
                        </div>
                        <div class="divide-y max-h-[400px] overflow-y-auto">
                            <div v-for="log in recentLogs" :key="log.id"
                                class="px-5 py-3.5 hover:bg-gray-50 transition">
                                <div class="flex items-start gap-3">
                                    <div class="h-2 w-2 rounded-full mt-1.5 shrink-0" 
                                        :class="{
                                            'bg-emerald-500': log.action === 'created',
                                            'bg-blue-500': log.action === 'updated',
                                            'bg-rose-500': log.action === 'deleted',
                                            'bg-purple-500': log.action === 'restored'
                                        }">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] text-gray-700 leading-relaxed">
                                            <span class="font-bold text-gray-900">{{ log.user?.name || 'System' }}</span> 
                                            {{ log.description }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1.5">
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 uppercase tracking-tight">
                                                {{ log.model_name }}
                                            </span>
                                            <span class="text-[9px] text-gray-400 font-medium">{{ formatDate(log.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!recentLogs?.length" class="px-6 py-8 text-center text-xs text-gray-400">
                                Belum ada aktivitas tercatat.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
