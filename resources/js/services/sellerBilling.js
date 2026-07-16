import axios from './http';

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

function toBoolean(value) {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value > 0;
    }

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();
        return normalized === '1' || normalized === 'true' || normalized === 'active' || normalized === 'enabled';
    }

    return false;
}

function normalizeStatus(payload) {
    const source = payload?.data ?? payload ?? {};

    const active =
        toBoolean(source.seller_has_active_subscription)
        || toBoolean(source.subscription_active)
        || toBoolean(source.is_subscription_active)
        || toBoolean(source.active)
        || toBoolean(source.has_active_subscription)
        || toBoolean(source.subscribed)
        || String(source.seller_subscription_status ?? '').toLowerCase() === 'active';

    return {
        active,
        raw: source,
    };
}

function normalizeCheckout(payload) {
    const status = normalizeStatus(payload);
    const source = status.raw ?? {};

    const redirectUrl =
        source.checkout_url
        ?? source.checkoutUrl
        ?? source.redirect_url
        ?? source.redirectUrl
        ?? source.url
        ?? source.payment_url
        ?? source.payment?.checkout_url
        ?? source.payment?.checkoutUrl
        ?? source.subscription?.checkout_url
        ?? source.subscription?.checkoutUrl
        ?? null;

    return {
        ...status,
        redirectUrl: typeof redirectUrl === 'string' && redirectUrl.trim() ? redirectUrl : null,
    };
}

export async function getSellerBillingStatus() {
    // In demo mode, return active status immediately
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return { active: true, raw: { seller_has_active_subscription: true, seller_subscription_status: 'active' } };
    }

    try {
        const { data } = await axios.get('/api/seller/billing/status');
        return normalizeStatus(data);
    } catch (error) {
        if (error?.response?.status === 404) {
            try {
                const { data } = await axios.get('/api/auth/me');
                return normalizeStatus(data);
            } catch {
                return { active: false, raw: null };
            }
        }

        throw error;
    }
}

export async function subscribeSellerBilling(payload = {}) {
    // In demo mode, just return active
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return { active: true, raw: { seller_has_active_subscription: true } };
    }

    const { data } = await axios.post('/api/seller/billing/subscribe', payload);
    return normalizeStatus(data);
}

export async function initiateSellerBillingCheckout(payload = {}) {
    // In demo mode, return a mock successful checkout
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return {
            active: true,
            redirectUrl: null,
            raw: { seller_has_active_subscription: true }
        };
    }

    const { data } = await axios.post('/api/seller/billing/subscribe', payload);
    return normalizeCheckout(data);
}

export async function cancelSellerBilling() {
    // In demo mode, just return inactive
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return { active: false, raw: { seller_has_active_subscription: false } };
    }

    const { data } = await axios.post('/api/seller/billing/cancel');
    return normalizeStatus(data);
}

export async function hasActiveSellerSubscription() {
    // In demo mode, always return true
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return true;
    }

    const status = await getSellerBillingStatus();
    return Boolean(status.active);
}
