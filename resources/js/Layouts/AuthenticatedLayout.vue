<script setup>
import { computed, ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import Toast from '@/Components/Toast.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage();
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

const totalNotifications = computed(() => {
    const n = page.props.notifications;
    if (!n) return 0;
    return (n.damaged_count || 0) + (n.maintenance_count || 0) + (n.expiring_warranty || 0);
});

// Global Toast Watcher
watch(() => page.props.flash, (flash) => {
    if (flash && flash.success) {
        toastMessage.value = flash.success;
        toastType.value = 'success';
        showToast.value = true;
    } else if (flash && flash.error) {
        toastMessage.value = flash.error;
        toastType.value = 'error';
        showToast.value = true;
    }
}, { deep: true });
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('assets.index')" :active="route().current('assets.*') && !route().current('assets.print*') && !route().current('assets.expirations')">
                                    Asset
                                </NavLink>
                                <NavLink :href="route('assets.print-index')" :active="route().current('assets.print*')">
                                    Cetak Barcode
                                </NavLink>

                                <!-- Aktivitas Group Dropdown -->
                                <div class="flex items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                Aktivitas
                                                <svg class="ms-1 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('loans.index')" :active="route().current('loans.*')">Peminjaman</DropdownLink>
                                            <DropdownLink :href="route('maintenance.index')" :active="route().current('maintenance.*')">Maintenance</DropdownLink>
                                            <DropdownLink :href="route('audits.index')" :active="route().current('audits.*')">Audit / Opname</DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Database Group Dropdown (Hanya Admin) -->
                                <div v-if="$page.props.auth.user.roles?.includes('admin')" class="flex items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <span class="inline-flex rounded-md">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                                    :class="{'text-emerald-600 font-bold': route().current('categories.*') || route().current('locations.*') || route().current('departments.*') || route().current('vendors.*')}"
                                                >
                                                    Database
                                                    <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('categories.index')" :active="route().current('categories.*')">Kategori</DropdownLink>
                                            <DropdownLink :href="route('locations.index')" :active="route().current('locations.*')">Lokasi</DropdownLink>
                                            <DropdownLink :href="route('departments.index')" :active="route().current('departments.*')">Departemen</DropdownLink>
                                            <DropdownLink :href="route('vendors.index')" :active="route().current('vendors.*')">Supplier</DropdownLink>
                                            <div class="border-t border-gray-100"></div>
                                            <DropdownLink :href="route('users.index')" :active="route().current('users.*')">Manajemen User</DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <NavLink :href="route('scanner.index')" :active="route().current('scanner.*')">
                                    Scanner
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.roles?.includes('admin')" :href="route('settings.asset-code')" :active="route().current('settings.*')">
                                    Settings
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-3">
                            <!-- Notifications Dropdown -->
                            <div class="relative">
                                <Dropdown align="right" width="80">
                                    <template #trigger>
                                        <button class="relative p-2 text-gray-400 hover:text-gray-500 transition focus:outline-none">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            <span v-if="totalNotifications > 0" class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-white">
                                                {{ totalNotifications > 9 ? '9+' : totalNotifications }}
                                            </span>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-widest">Notifikasi & Alert</h3>
                                        </div>
                                        
                                        <div class="divide-y divide-gray-100">
                                            <!-- Damaged Assets -->
                                            <Link :href="route('assets.index', { status: 'damaged' })" class="flex items-start gap-4 px-5 py-4 hover:bg-rose-50/30 transition group">
                                                <div class="h-10 w-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[13px] font-bold text-gray-900">Aset Rusak</p>
                                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">{{ $page.props.notifications?.damaged_count || 0 }} aset membutuhkan perbaikan segera.</p>
                                                </div>
                                            </Link>

                                            <!-- Maintenance Assets -->
                                            <Link :href="route('maintenance.index')" class="flex items-start gap-4 px-5 py-4 hover:bg-amber-50/30 transition group">
                                                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[13px] font-bold text-gray-900">Dalam Perbaikan</p>
                                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">{{ $page.props.notifications?.maintenance_count || 0 }} aset sedang dalam proses maintenance.</p>
                                                </div>
                                            </Link>

                                            <!-- Expiring Warranty & License -->
                                            <Link :href="route('assets.expirations')" class="flex items-start gap-4 px-5 py-4 hover:bg-blue-50/30 transition group">
                                                <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[13px] font-bold text-gray-900">Garansi & Lisensi</p>
                                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">{{ $page.props.notifications?.expiring_warranty || 0 }} item mendekati batas masa berlaku.</p>
                                                </div>
                                            </Link>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                <img :src="$page.props.auth.user.profile_photo_url" class="h-8 w-8 rounded-full object-cover border border-gray-100" alt="Avatar">
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('assets.index')"
                            :active="route().current('assets.*') && !route().current('assets.print*') && !route().current('assets.expirations')"
                        >
                            Asset
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('assets.print-index')"
                            :active="route().current('assets.print*')"
                        >
                            Cetak Barcode
                        </ResponsiveNavLink>
                        <!-- Database Group (Mobile) -->
                        <div class="border-t border-gray-100 pt-2 pb-1">
                            <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Database
                            </div>
                            <ResponsiveNavLink
                                :href="route('categories.index')"
                                :active="route().current('categories.*')"
                            >
                                Kategori
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('locations.index')"
                                :active="route().current('locations.*')"
                            >
                                Lokasi
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('departments.index')"
                                :active="route().current('departments.*')"
                            >
                                Departemen
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('vendors.index')"
                                :active="route().current('vendors.*')"
                            >
                                Supplier
                            </ResponsiveNavLink>
                        </div>

                        <!-- Aktivitas Group (Mobile) -->
                        <div class="border-t border-gray-100 pt-2 pb-1">
                            <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Aktivitas
                            </div>
                            <ResponsiveNavLink
                                :href="route('loans.index')"
                                :active="route().current('loans.*')"
                            >
                                Peminjaman
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('maintenance.index')"
                                :active="route().current('maintenance.*')"
                            >
                                Maintenance
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('audits.index')"
                                :active="route().current('audits.*')"
                            >
                                Audit / Opname
                            </ResponsiveNavLink>
                        </div>
                        <ResponsiveNavLink
                            :href="route('scanner.index')"
                            :active="route().current('scanner.*')"
                        >
                            Scanner
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('settings.asset-code')"
                            :active="route().current('settings.*')"
                        >
                            Settings
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4 flex items-center gap-3">
                            <img :src="$page.props.auth.user.profile_photo_url" class="h-10 w-10 rounded-full object-cover border border-gray-200" alt="Avatar">
                            <div>
                                <div class="text-base font-medium text-gray-800">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-sm font-medium text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-[1cm] sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <!-- Global Toast -->
            <Toast 
                :show="showToast" 
                :message="toastMessage" 
                :type="toastType" 
                @close="showToast = false" 
            />
        </div>
    </div>
</template>
