<template>
    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Seller Dashboard</h2>

        <div v-if="billingSummary" class="rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900">
            <p class="font-semibold">{{ $t('dashboard.billingTitle') }}</p>
            <p class="mt-1">{{ billingSummary }}</p>
        </div>

        <div v-if="showSubscriptionBlock" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
            <p>{{ $t('sellerOnboarding.blockingCallout') }}</p>
            <RouterLink class="mt-2 inline-block font-semibold text-amber-900 underline" :to="{ name: 'seller-onboarding' }">
                {{ $t('sellerOnboarding.openOnboarding') }}
            </RouterLink>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <RouterLink
                class="flex items-center justify-center rounded-lg bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-700"
                :class="showSubscriptionBlock ? 'pointer-events-none cursor-not-allowed !bg-slate-200 !text-slate-400 shadow-none' : ''"
                :to="showSubscriptionBlock ? { name: 'seller-onboarding', query: { redirect: '/properties/new' } } : { name: 'property-create' }"
            >
                {{ $t('actions.createListing') }}
            </RouterLink>
            <RouterLink class="flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50" :to="{ name: 'home', query: { owned: '1' } }">
                {{ $t('nav.properties') }}
            </RouterLink>
            <RouterLink class="flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50" :to="{ name: 'messages' }">
                <span>{{ $t('nav.messages') }}</span>
                <span
                    v-if="unreadCount > 0"
                    class="ml-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white"
                >
                    {{ unreadCount }}
                </span>
            </RouterLink>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { getProfile } from '../services/authProfile';
import { getUnreadMessageCount } from '../services/messages';
import { hasActiveSellerSubscription } from '../services/sellerBilling';
import { formatPrice } from '../utils/formatters';

const unreadCount = ref(0);
const showSubscriptionBlock = ref(false);
const profile = ref(null);

const billingSummary = computed(() => {
    const data = profile.value;
    if (!data) {
        return '';
    }

    if (!data.seller_has_active_subscription) {
        return '';
    }

    const amount = Number(data.seller_subscription_amount ?? 0);
    const currency = String(data.seller_subscription_currency ?? 'UGX').toUpperCase();
    const planCode = String(data.seller_subscription_plan_code ?? 'starter_monthly');
    const planLabel = planCode.replaceAll('_', ' ');

    return `${formatPrice(amount, currency)} / month · ${planLabel}`;
});

const loadUnreadCount = async () => {
    try {
        unreadCount.value = await getUnreadMessageCount();
    } catch {
        unreadCount.value = 0;
    }
};

onMounted(async () => {
    profile.value = await getProfile(true);

    try {
        showSubscriptionBlock.value = !(await hasActiveSellerSubscription());
    } catch {
        showSubscriptionBlock.value = true;
    }

    await loadUnreadCount();
});
</script>
