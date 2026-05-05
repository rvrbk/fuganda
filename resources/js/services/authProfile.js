import axios from './http';

let cachedProfile = null;

export async function getProfile(force = false) {
	if (!force && cachedProfile) {
		return cachedProfile;
	}

	try {
		const { data } = await axios.get('/api/auth/me');
		cachedProfile = data.data ?? data;
		return cachedProfile;
	} catch {
		cachedProfile = null;
		return null;
	}
}

export async function login(credentials) {
	await axios.get('/sanctum/csrf-cookie');
	await axios.post('/login', credentials);
	return getProfile(true);
}

export async function logout() {
	await axios.post('/logout');
	cachedProfile = null;
}
