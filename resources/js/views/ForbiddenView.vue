<template>
    <section class="mx-auto max-w-2xl rounded-lg border border-rose-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ $t('forbidden.title') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $t('forbidden.description') }}</p>

        <p v-if="fromPath" class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
            {{ $t('forbidden.source') }} {{ fromPath }}
        </p>

        <div class="mt-5 flex flex-wrap gap-2">
            <RouterLink class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :to="{ name: 'home' }">
                {{ $t('forbidden.browseProperties') }}
            </RouterLink>
            <RouterLink class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700" :to="{ name: 'home' }">
                {{ $t('forbidden.goHome') }}
            </RouterLink>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { usePageMeta } from '../composables/usePageMeta';

usePageMeta({ title: 'Access Denied', robots: 'noindex,nofollow' });

const route = useRoute();

const fromPath = computed(() => {
    const raw = route.query.from;
    if (typeof raw !== 'string' || !raw.trim()) {
        return '';
    }

    return raw;
});
</script>
