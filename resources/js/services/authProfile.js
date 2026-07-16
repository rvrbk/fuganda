import axios from './http';

let cachedProfile = null;

// Check if demo mode is enabled from frontend environment
function isDemoMode() {
    return import.meta.env.VITE_DEMO_MODE === 'true' || import.meta.env.VITE_DEMO_MODE === true;
}

function normalizeRole(value) {
	return String(value ?? '').trim().toLowerCase();
}

export function getUserRole(profile) {
	if (!profile || typeof profile !== 'object') {
		return '';
	}

	if (typeof profile.role === 'string') {
		return normalizeRole(profile.role);
	}

	if (Array.isArray(profile.roles) && profile.roles.length > 0) {
		const firstRole = profile.roles.find((role) => typeof role === 'string');
		if (firstRole) {
			return normalizeRole(firstRole);
		}
	}

	return '';
}

export function isBuyerProfile(profile) {
	return getUserRole(profile) === 'buyer';
}

export function canManageListings(profile) {
	// In demo mode, allow anyone to manage listings
	if (isDemoMode()) {
		return true;
	}

	const role = getUserRole(profile);
	return role === 'seller' || role === 'admin';
}

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

export async function register(payload) {
	await axios.get('/sanctum/csrf-cookie');
	await axios.post('/register', payload);

	const profile = await getProfile(true);
	if (profile) {
		return profile;
	}

	return login({ email: payload.email, password: payload.password });
}

export async function requestPasswordResetLink(email) {
	await axios.get('/sanctum/csrf-cookie');
	const { data } = await axios.post('/forgot-password', { email });

	return data?.status ?? null;
}

export async function resetPassword(payload) {
	await axios.get('/sanctum/csrf-cookie');
	const { data } = await axios.post('/reset-password', payload);

	return data?.status ?? null;
}

export async function logout() {
	await axios.post('/logout');
	cachedProfile = null;
}
