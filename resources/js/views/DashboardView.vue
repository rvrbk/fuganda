<template>
    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Agent Dashboard</h2>



        <div class="grid gap-3 md:grid-cols-3">
            <RouterLink
                class="flex items-center justify-center rounded-lg bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-700"
                :to="{ name: 'property-create' }"
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
import { getProfile, isBuyerProfile } from '../services/authProfile';
import { getUnreadMessageCount } from '../services/messages';
import { formatPrice } from '../utils/formatters';
import { usePageMeta } from '../composables/usePageMeta';

usePageMeta({ title: 'Agent Dashboard', robots: 'noindex,nofollow' });

const unreadCount = ref(0);
const profile = ref(null);

const loadUnreadCount = async () => {
    try {
        unreadCount.value = await getUnreadMessageCount();
    } catch {
        unreadCount.value = 0;
    }
};

onMounted(async () => {
    profile.value = await getProfile(true);
    await loadUnreadCount();
});
</script>
