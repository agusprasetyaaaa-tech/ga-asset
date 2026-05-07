<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-4 sm:p-6"
    >
        <!-- Full Screen Background Image Container -->
        <div 
            class="absolute inset-0 z-0 bg-no-repeat bg-center"
            style="background-image: url('/logo/portallogin.png'); background-size: 100% 100%;"
        >
            <div class="absolute inset-0 bg-black/30"></div>
        </div>

        <!-- Compact Login Card -->
        <div
            class="relative z-10 w-full overflow-hidden bg-white/95 backdrop-blur-md px-6 py-8 shadow-2xl sm:max-w-[420px] sm:px-10 sm:py-12 sm:rounded-3xl border border-white/40"
        >
            <div class="flex flex-col items-center mb-8">
                <Link href="/">
                    <ApplicationLogo class="h-16 w-16 fill-current text-emerald-600 transition-transform hover:scale-105 duration-300" />
                </Link>
                <h2 class="mt-4 text-2xl font-bold text-gray-800">Asset Management App</h2>
                <p class="text-sm text-gray-500 text-center">Silakan masuk ke akun Anda</p>
            </div>

            <slot />
        </div>
    </div>
</template>
