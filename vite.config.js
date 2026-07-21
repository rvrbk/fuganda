import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            // Registration is handled explicitly in app.js via virtual:pwa-register
            injectRegister: null,
            // Output SW and manifest directly to public/ so they're served at /sw.js and /manifest.webmanifest
            outDir: 'public',
            filename: 'sw.js',
            manifestFilename: 'manifest.webmanifest',
            includeAssets: [
                'favicon.ico',
                'icon.svg',
                'apple-touch-icon-180x180.png',
                'pwa-64x64.png',
                'pwa-192x192.png',
                'pwa-512x512.png',
                'maskable-icon-512x512.png',
            ],
            manifest: {
                name: 'Verbeek.ug Real Estates — Uganda Property Listings',
                short_name: 'Verbeek.ug Real Estates',
                description: 'Find apartments, houses, land and commercial properties for rent and sale across Uganda.',
                start_url: '/',
                display: 'standalone',
                orientation: 'portrait-primary',
                background_color: '#ffffff',
                theme_color: '#0ea5e9',
                lang: 'en',
                scope: '/',
                icons: [
                    { src: '/pwa-64x64.png', sizes: '64x64', type: 'image/png' },
                    { src: '/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
                    {
                        src: '/maskable-icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
                categories: ['business'],
            },
            workbox: {
                // Precache the compiled JS/CSS bundles
                globDirectory: 'public/build',
                globPatterns: ['**/*.{js,css,woff2}'],
                // Don't set a navigate fallback — Laravel handles SPA routing server-side
                navigateFallback: null,
                runtimeCaching: [
                    {
                        // Cache API property listings for 5 minutes (network-first)
                        urlPattern: /^\/api\/properties/,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'properties-api',
                            expiration: { maxEntries: 100, maxAgeSeconds: 300 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        // Cache property images for 30 days (cache-first)
                        urlPattern: /\.(?:png|jpe?g|svg|gif|webp|ico)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images',
                            expiration: {
                                maxEntries: 300,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

