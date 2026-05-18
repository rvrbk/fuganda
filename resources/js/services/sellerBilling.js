import axios from './http';

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
    const { data } = await axios.post('/api/seller/billing/subscribe', payload);
    return normalizeStatus(data);
}

export async function initiateSellerBillingCheckout(payload = {}) {
    const { data } = await axios.post('/api/seller/billing/subscribe', payload);
    return normalizeCheckout(data);
}

export async function cancelSellerBilling() {
    const { data } = await axios.post('/api/seller/billing/cancel');
    return normalizeStatus(data);
}

export async function hasActiveSellerSubscription() {
    const status = await getSellerBillingStatus();
    return Boolean(status.active);
}
