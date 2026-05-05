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

bootHttp();

createApp(App)
	.use(router)
	.use(i18n)
	.use(ElementPlus)
	.mount('#app');

registerSW({ immediate: true });
