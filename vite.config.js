import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const isProduction = mode === 'production';
    const appUrl = isProduction ? 'https://ga.interprima.co.id' : 'http://192.168.0.11:8085';

    return {
        // base: isProduction ? '/build/' : '/', // Tetap pertahankan ini

        plugins: [
            VitePWA({
                registerType: 'autoUpdate',
                injectRegister: false,
                manifest: {
                    name: 'Inventory Interprima',
                    short_name: 'Inventory',
                    description: 'Sistem Manajemen Aset & Inventaris Interprima',
                    theme_color: '#059669',
                    background_color: '#ffffff',
                    display: 'standalone',
                    icons: [
                        {
                            src: '/icons/logo.png',
                            sizes: '192x192',
                            type: 'image/png'
                        },
                        {
                            src: '/icons/logo.png',
                            sizes: '512x512',
                            type: 'image/png'
                        }
                    ]
                }
            }),
            laravel({
                input: 'resources/js/app.js',
                refresh: !isProduction,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],

        build: {
            outDir: 'public/build',
            minify: isProduction ? 'terser' : false,
            rollupOptions: {
                // output: {
                //     manualChunks: {
                //         vendor: ['vue', '@inertiajs/vue3', 'axios'],
                //         ui: ['@headlessui/vue', 'sweetalert2'],
                //     },
                // },
            },
            sourcemap: !isProduction,
            chunkSizeWarningLimit: 1000,
        },

        server: {
            host: '0.0.0.0',
            port: 5175,
            hmr: {
                host: isProduction ? 'ga.interprima.co.id' : 'localhost',
                protocol: isProduction ? 'wss' : 'ws',
            },
        },

        define: {
            'process.env.VITE_APP_URL': JSON.stringify(appUrl),
        },

        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
    };
});
