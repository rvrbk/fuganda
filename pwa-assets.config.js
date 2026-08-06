import { defineConfig, minimal2023Preset } from '@vite-pwa/assets-generator/config';

export default defineConfig({
    preset: {
        ...minimal2023Preset,
        apple: {
            sizes: [180],
            padding: 0.15,
            resizeOptions: { background: 'transparent', fit: 'contain' },
        },
        maskable: {
            sizes: [512],
            padding: 0.15,
            resizeOptions: { background: 'transparent', fit: 'contain' },
        },
        transparent: {
            sizes: [64, 192, 512],
            padding: 0.15,
            resizeOptions: { background: 'transparent', fit: 'contain' },
        },
    },
    images: ['public/icon.svg'],
});
