import axios from 'axios';

export function bootHttp() {
	window.axios = axios;
	window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
	window.axios.defaults.withCredentials = true;

	return window.axios;
}

export default axios;
