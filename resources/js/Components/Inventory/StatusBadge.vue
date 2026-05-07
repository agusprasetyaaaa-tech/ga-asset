<script setup>
/**
 * StatusBadge Component
 * Displays a flavored badge with ICON based on asset status.
 */
const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (val) => ['available', 'in_use', 'maintenance', 'damaged', 'borrowed'].includes(val),
    },
    customLabel: {
        type: String,
        default: null
    }
});

const statusConfig = {
    available: {
        label: 'Tersedia',
        classes: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        icon: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`,
    },
    in_use: {
        label: 'Digunakan',
        classes: 'bg-blue-50 text-blue-700 border-blue-100',
        icon: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>`,
    },
    borrowed: {
        label: 'Dipinjam',
        classes: 'bg-indigo-50 text-indigo-700 border-indigo-100',
        icon: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    maintenance: {
        label: 'Perbaikan',
        classes: 'bg-amber-50 text-amber-700 border-amber-100',
        icon: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`,
    },
    damaged: {
        label: 'Rusak',
        classes: 'bg-red-50 text-red-700 border-red-100',
        icon: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
    },
};

const config = statusConfig[props.status] || statusConfig.available;
</script>

<template>
    <span
        :class="config.classes"
        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
    >
        <span v-html="config.icon" class="flex-shrink-0"></span>
        {{ props.customLabel || config.label }}
    </span>
</template>
