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
// iOS zooms in on select elements and doesn't zoom back out on selection
// Primary fix: CSS prevents the zoom with -webkit-text-size-adjust and font-size: 18px
// Fallback: Reset viewport scale on select change
document.addEventListener('change', (e) => {
	if (e.target.tagName === 'SELECT') {
		// Force iOS to reset viewport by briefly setting maximum-scale=1
		const viewport = document.querySelector('meta[name="viewport"]');
		if (viewport) {
			const original = viewport.getAttribute('content');
			viewport.setAttribute('content', original + ',maximum-scale=1.0');
			// Force reflow
			void document.body.offsetHeight;
			// Restore after a short delay
			setTimeout(() => {
				viewport.setAttribute('content', original);
			}, 100);
		}
	}
});
