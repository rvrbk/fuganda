import './bootstrap';
import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import 'leaflet/dist/leaflet.css';
import { registerSW } from 'virtual:pwa-register';
import App from './App.vue';
import i18n from './i18n';
import router from './router';
import { bootHttp } from './services/http';
import clickOutside from './directives/clickOutside';

bootHttp();

const app = createApp(App);

app.directive('clickoutside', clickOutside);

app.use(router)
	.use(i18n)
	.use(ElementPlus)
	.mount('#app');

// Only register service worker in production
// In development, the service worker from public/ will interfere with Vite's HMR
if (import.meta.env.PROD) {
	registerSW({ immediate: true });
}

// Fix for iOS Safari select zoom issue
// iOS zooms in on select elements with font-size < 16px and doesn't zoom back out
// Solution: CSS ensures mobile selects have font-size: 16px to prevent the zoom-in entirely
// This is handled in app.css with mobile-specific select styling
