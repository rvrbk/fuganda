<template>
    <section class="mx-auto max-w-2xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">{{ $t('sellerOnboarding.badge') }}</p>
        <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $t('sellerOnboarding.title') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $t('sellerOnboarding.intro') }}</p>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <article class="rounded-md border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ $t('sellerOnboarding.monthlyTitle') }}</h3>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ $t('sellerOnboarding.monthlyPrice') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $t('sellerOnboarding.monthlyCopy') }}</p>
            </article>
            <article class="rounded-md border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ $t('sellerOnboarding.publishFeeTitle') }}</h3>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ $t('sellerOnboarding.publishFeePrice') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $t('sellerOnboarding.publishFeeCopy') }}</p>
            </article>
        </div>

        <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
            <p class="text-sm font-medium text-slate-800">{{ $t('sellerOnboarding.paymentDetailsTitle') }}</p>
            <div class="mt-3 grid gap-3">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ $t('sellerOnboarding.billingEmailLabel') }}</span>
                    <input
                        v-model.trim="billingEmail"
                        type="email"
                        class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-700"
                        :disabled="isLoading || isActive"
                        :placeholder="$t('sellerOnboarding.billingEmailPlaceholder')"
                    >
                </label>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $t('sellerOnboarding.paymentDetailsHint') }}</p>
        </div>

        <div class="mt-4 rounded-md border border-sky-100 bg-sky-50 p-3">
            <p class="text-sm font-semibold text-sky-900">{{ $t('sellerOnboarding.checkoutTitle') }}</p>
            <p class="mt-1 text-xs text-sky-800">{{ $t('sellerOnboarding.checkoutHint') }}</p>
            <p v-if="isPaymentPending" class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                {{ $t('sellerOnboarding.processingMessage') }}
            </p>
        </div>

        <p class="mt-3 text-sm text-slate-600">{{ $t('sellerOnboarding.billingSummary') }}</p>

        <p v-if="errorMessage" class="mt-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ errorMessage }}</p>
        <p v-if="successMessage" class="mt-4 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ successMessage }}</p>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="rounded bg-slate-900 px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:bg-slate-500"
                :disabled="isLoading || isActive || isPaymentPending"
                @click="handleActivate"
            >
                {{ isLoading ? $t('sellerOnboarding.activating') : $t('sellerOnboarding.payWithPesapalButton') }}
            </button>
            <button
                type="button"
                class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                :disabled="isLoading || !isActive"
                @click="handleContinue"
            >
                {{ $t('sellerOnboarding.continueButton') }}
            </button>
            <button
                type="button"
                class="rounded border border-rose-300 px-4 py-2 text-sm text-rose-700 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                :disabled="isLoading || !isActive"
                @click="handleCancel"
            >
                {{ $t('sellerOnboarding.cancelButton') }}
            </button>
        </div>
    </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { cancelSellerBilling, getSellerBillingStatus, initiateSellerBillingCheckout } from '../services/sellerBilling';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const isLoading = ref(false);
const isActive = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const paymentMethod = 'mobile_money';
const billingEmail = ref('');
const isPaymentPending = ref(false);
let pendingStatusInterval = null;

function hasPendingPayment(rawStatus) {
    const paymentStatus = String(
        rawStatus?.subscription?.payment_status
        ?? rawStatus?.payment_status
        ?? ''
    ).toLowerCase();

    const subscriptionStatus = String(rawStatus?.seller_subscription_status ?? '').toLowerCase();
    const pendingStates = ['pending', 'processing', 'requires_action', 'queued'];

    return pendingStates.includes(paymentStatus) || pendingStates.includes(subscriptionStatus);
}

function hasFailedPayment(rawStatus) {
    const paymentStatus = String(
        rawStatus?.subscription?.payment_status
        ?? rawStatus?.payment_status
        ?? ''
    ).toLowerCase();

    const subscriptionStatus = String(rawStatus?.seller_subscription_status ?? '').toLowerCase();
    const failedStates = ['failed', 'declined', 'past_due', 'invalid', 'expired', 'canceled', 'cancelled'];

    return failedStates.includes(paymentStatus) || failedStates.includes(subscriptionStatus);
}

function stopPendingStatusPolling() {
    if (pendingStatusInterval) {
        window.clearInterval(pendingStatusInterval);
        pendingStatusInterval = null;
    }
}

function startPendingStatusPolling() {
    if (pendingStatusInterval) {
        return;
    }

    pendingStatusInterval = window.setInterval(async () => {
        if (!isPaymentPending.value) {
            stopPendingStatusPolling();
            return;
        }

        await refreshStatus();
        if (isActive.value) {
            isPaymentPending.value = false;
            successMessage.value = t('sellerOnboarding.activatedSuccess');
            stopPendingStatusPolling();
        }
    }, 10000);
}

function processReturnState() {
    const returnState = String(
        route.query.billing_result
        ?? route.query.payment_status
        ?? route.query.status
        ?? ''
    ).toLowerCase();

    if (!returnState) {
        return;
    }

    const successStates = ['success', 'succeeded', 'paid', 'completed'];
    const cancelStates = ['cancel', 'canceled', 'cancelled'];
    const pendingStates = ['pending', 'processing', 'requires_action'];

    if (successStates.includes(returnState)) {
        // A return flag alone does not guarantee settlement; confirm via status polling.
        successMessage.value = t('sellerOnboarding.returnPending');
        errorMessage.value = '';
        isPaymentPending.value = true;
        startPendingStatusPolling();
    } else if (cancelStates.includes(returnState)) {
        errorMessage.value = t('sellerOnboarding.returnCanceled');
        successMessage.value = '';
        isPaymentPending.value = false;
        stopPendingStatusPolling();
    } else if (pendingStates.includes(returnState)) {
        successMessage.value = t('sellerOnboarding.returnPending');
        errorMessage.value = '';
        isPaymentPending.value = true;
        startPendingStatusPolling();
    }

    const sanitizedQuery = { ...route.query };
    delete sanitizedQuery.billing_result;
    delete sanitizedQuery.payment_status;
    delete sanitizedQuery.status;
    router.replace({ query: sanitizedQuery });
}

async function refreshStatus() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const status = await getSellerBillingStatus();
        isActive.value = Boolean(status.active);
        const resolvedBillingEmail = String(
            status.raw?.subscription?.billing_email
            ?? status.raw?.account_email
            ?? ''
        ).trim();

        if (!billingEmail.value && resolvedBillingEmail) {
            billingEmail.value = resolvedBillingEmail;
        }

        const pending = hasPendingPayment(status.raw);
        const failed = hasFailedPayment(status.raw);
        if (isActive.value) {
            isPaymentPending.value = false;
            stopPendingStatusPolling();
        } else if (pending) {
            isPaymentPending.value = true;
            if (!successMessage.value) {
                successMessage.value = t('sellerOnboarding.activationPending');
            }
            startPendingStatusPolling();
        } else if (failed) {
            isPaymentPending.value = false;
            stopPendingStatusPolling();
            errorMessage.value = 'Last payment attempt failed. Please try Pay now again.';
        } else {
            isPaymentPending.value = false;
            stopPendingStatusPolling();
        }
    } catch {
        errorMessage.value = t('sellerOnboarding.statusError');
    } finally {
        isLoading.value = false;
    }
}

async function handleActivate() {
    const wasPendingBefore = isPaymentPending.value;

    isLoading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    if (wasPendingBefore) {
        await refreshStatus();
        if (!isActive.value) {
            successMessage.value = t('sellerOnboarding.activationPending');
            isPaymentPending.value = true;
            startPendingStatusPolling();
            isLoading.value = false;
            return;
        }
    }

    if (!billingEmail.value) {
        errorMessage.value = t('sellerOnboarding.paymentDetailsRequired');
        isLoading.value = false;
        return;
    }

    try {
        const subscriptionAmount = 39000;
        const origin = typeof window !== 'undefined' ? window.location.origin : '';
        const onboardingPath = route.path || '/seller/onboarding';

        const checkout = await initiateSellerBillingCheckout({
            currency: 'UGX',
            amount_ugx: subscriptionAmount,
            payment_method: paymentMethod,
            billing_email: billingEmail.value,
            success_url: `${origin}${onboardingPath}?billing_result=success`,
            cancel_url: `${origin}${onboardingPath}?billing_result=cancel`,
        });

        if (checkout.redirectUrl) {
            window.location.assign(checkout.redirectUrl);
            return;
        }

        isActive.value = Boolean(checkout.active);
        if (isActive.value) {
            successMessage.value = t('sellerOnboarding.activatedSuccess');
            stopPendingStatusPolling();
        } else {
            isPaymentPending.value = true;
            successMessage.value = t('sellerOnboarding.activationPending');
            startPendingStatusPolling();
        }
    } catch (error) {
        const apiError =
            error?.response?.data?.errors?.payment?.[0]
            || error?.response?.data?.errors?.pesapal?.[0]
            || error?.response?.data?.message
            || null;

        errorMessage.value = apiError || t('sellerOnboarding.activateError');
    } finally {
        isLoading.value = false;
    }
}

async function handleContinue() {
    errorMessage.value = '';

    if (!isActive.value) {
        await refreshStatus();
    }

    if (!isActive.value) {
        errorMessage.value = t('sellerOnboarding.continueBlocked');
        return;
    }

    const redirectPath = typeof route.query.redirect === 'string' && route.query.redirect.trim()
        ? route.query.redirect
        : null;

    if (redirectPath) {
        router.push(redirectPath);
        return;
    }

    router.push({ name: 'dashboard' });
}

async function handleCancel() {
    isLoading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const status = await cancelSellerBilling();
        isActive.value = Boolean(status.active);
        successMessage.value = t('sellerOnboarding.cancelledSuccess');
    } catch {
        errorMessage.value = t('sellerOnboarding.cancelError');
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    billingEmail.value = '';
    processReturnState();
    void refreshStatus();
});

onUnmounted(() => {
    stopPendingStatusPolling();
});
</script>
