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
// Primary fix: CSS prevents the zoom with -webkit-text-size-adjust and font-size: 12px
// Fallback: Force viewport reset after selection
document.addEventListener('change', (e) => {
	if (e.target.tagName === 'SELECT') {
		resetIosViewport();
	}
});

document.addEventListener('click', (e) => {
	if (e.target.closest('.searchable-select__button, .searchable-select__option')) {
		resetIosViewport();
	}
});

function resetIosViewport() {
	const viewport = document.querySelector('meta[name="viewport"]');
	if (viewport) {
		const original = viewport.getAttribute('content');
		if (!original.includes('maximum-scale=1.0')) {
			viewport.setAttribute('content', original + ',maximum-scale=1.0');
			void document.body.offsetHeight;
			setTimeout(() => {
				viewport.setAttribute('content', original);
			}, 100);
		}
	}
}
