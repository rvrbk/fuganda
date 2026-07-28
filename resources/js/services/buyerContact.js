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

function normalizeCheckout(payload) {
    const source = payload?.data ?? payload ?? {};

    const redirectUrl =
        source.checkout_url
        ?? source.checkoutUrl
        ?? source.redirect_url
        ?? source.redirectUrl
        ?? source.url
        ?? source.payment_url
        ?? source.payment?.checkout_url
        ?? source.payment?.checkoutUrl
        ?? null;

    return {
        hasPaid: Boolean(source.has_paid),
        redirectUrl: typeof redirectUrl === 'string' && redirectUrl.trim() ? redirectUrl : null,
        raw: source,
    };
}

function normalizeStatus(payload) {
    const source = payload?.data ?? payload ?? {};
    return {
        hasPaid: Boolean(source.has_paid),
        propertyId: source.property_id,
        contactFeeAmountUgx: source.contact_fee_amount_ugx,
        raw: source,
    };
}

export async function checkBuyerContactPayment(propertyId) {
    // In demo mode, return paid status immediately
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return { hasPaid: true, propertyId, contactFeeAmountUgx: 10000, raw: { has_paid: true } };
    }

    try {
        const { data } = await axios.get(`/api/buyer/contact/status/${propertyId}`);
        return normalizeStatus(data);
    } catch (error) {
        if (error?.response?.status === 404) {
            // If endpoint doesn't exist, assume no payment needed (for backwards compatibility)
            return { hasPaid: true, propertyId, contactFeeAmountUgx: 10000, raw: { has_paid: true } };
        }
        throw error;
    }
}

export async function initiateBuyerContactPayment(propertyId, payload = {}) {
    // In demo mode, return a mock successful checkout
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return {
            hasPaid: true,
            redirectUrl: null,
            raw: { has_paid: true }
        };
    }

    const { data } = await axios.post(`/api/buyer/contact/checkout/${propertyId}`, payload);
    return normalizeCheckout(data);
}

export async function hasPaidForContact(propertyId) {
    // In demo mode, always return true
    const demoEnabled = await isDemoMode();
    if (demoEnabled) {
        return true;
    }

    const status = await checkBuyerContactPayment(propertyId);
    return Boolean(status.hasPaid);
}
