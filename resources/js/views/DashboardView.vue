<template>
    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Tenant Dashboard</h2>

        <div class="grid gap-3 md:grid-cols-3">
            <RouterLink class="rounded border border-slate-300 px-3 py-3 text-sm" :to="{ name: 'property-create' }">
                {{ $t('actions.createListing') }}
            </RouterLink>
            <RouterLink class="rounded border border-slate-300 px-3 py-3 text-sm" :to="{ name: 'properties' }">
                {{ $t('nav.properties') }}
            </RouterLink>
            <RouterLink class="rounded border border-slate-300 px-3 py-3 text-sm" :to="{ name: 'messages' }">
                {{ $t('nav.messages') }}
            </RouterLink>
        </div>

        <p class="text-sm text-slate-600">
            Protected backend route: /api/tenant/dashboard.
        </p>
        <pre class="overflow-auto rounded bg-slate-900 p-3 text-xs text-slate-100">{{ payload }}</pre>
        <button class="rounded bg-emerald-700 px-3 py-2 text-sm text-white" type="button" @click="load">
            {{ $t('actions.loadPayload') }}
        </button>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const payload = ref('Click "Load Tenant Payload" after authenticating.');

const load = async () => {
    try {
        const { data } = await window.axios.get('/api/tenant/dashboard');
        payload.value = JSON.stringify(data, null, 2);
    } catch (error) {
        payload.value = JSON.stringify(error.response?.data ?? { message: 'Unauthorized' }, null, 2);
    }
};
</script>
