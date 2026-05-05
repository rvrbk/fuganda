<template>
    <main class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ $t('title') }}</h1>
                    <p class="text-sm text-slate-500">{{ $t('tagline') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700"
                        type="button"
                        @click="switchLocale"
                    >
                        {{ locale.toUpperCase() }}
                    </button>
                    <button
                        v-if="profile"
                        class="rounded bg-slate-900 px-3 py-2 text-sm text-white"
                        type="button"
                        @click="handleLogout"
                    >
                        {{ $t('nav.logout') }}
                    </button>
                </div>
            </div>
            <nav class="mx-auto flex max-w-6xl gap-3 px-4 pb-4 text-sm">
                <RouterLink class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'home' }">{{ $t('nav.home') }}</RouterLink>
                <RouterLink class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'properties' }">{{ $t('nav.properties') }}</RouterLink>
                <RouterLink v-if="profile" class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'messages' }">{{ $t('nav.messages') }}</RouterLink>
                <RouterLink v-if="profile" class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'dashboard' }">{{ $t('nav.dashboard') }}</RouterLink>
                <RouterLink v-else class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'login' }">{{ $t('nav.login') }}</RouterLink>
            </nav>
        </header>

        <div class="mx-auto max-w-6xl p-4">
            <RouterView @auth-changed="refreshProfile" />
        </div>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { getProfile, logout } from './services/authProfile';

const router = useRouter();
const profile = ref(null);
const { locale } = useI18n();

const refreshProfile = async () => {
    profile.value = await getProfile(true);
};

const switchLocale = () => {
    locale.value = locale.value === 'en' ? 'lg' : 'en';
};

const handleLogout = async () => {
    await logout();
    profile.value = null;
    router.push({ name: 'home' });
};

onMounted(async () => {
    profile.value = await getProfile();
});
</script>
