import axios from './http';

let cachedProfile = null;

// Cache for demo mode status
let demoModeCache = null;
let demoModePromise = null;

/**
 * Check if demo mode is enabled by fetching from backend
 * Caches the result for the session
 */
async function isDemoMode() {
    // Return cached value if available
    if (demoModeCache !== null) {
        return demoModeCache;
    }

    // If a fetch is already in progress, return its promise
    if (demoModePromise) {
        return demoModePromise;
    }

    demoModePromise = axios.get('/api/demo-mode')
        .then(response => {
            demoModeCache = Boolean(response.data?.demo_mode);
            demoModePromise = null;
            return demoModeCache;
        })
        .catch(() => {
            demoModePromise = null;
            demoModeCache = false;
            return false;
        });

    return demoModePromise;
}

/**
 * Reset demo mode cache (useful after login/logout)
 */
export function resetDemoModeCache() {
    demoModeCache = null;
    demoModePromise = null;
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

export async function canManageListings(profile) {
	// In demo mode, allow anyone to manage listings
	const demoEnabled = await isDemoMode();
	if (demoEnabled) {
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
	// Reset demo mode cache on login
	resetDemoModeCache();
	return getProfile(true);
}

export async function register(payload) {
	await axios.get('/sanctum/csrf-cookie');
	await axios.post('/register', payload);

	const profile = await getProfile(true);
	if (profile) {
		return profile;
	}

	// Reset demo mode cache on register
	resetDemoModeCache();
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
	// Reset demo mode cache on logout
	resetDemoModeCache();
}
