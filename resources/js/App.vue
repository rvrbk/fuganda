<template>
    <main class="min-h-screen bg-slate-50">
        <header ref="appHeader" class="relative z-40 border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ $t('title') }}</h1>
                    <p class="text-sm text-slate-500">{{ $t('tagline') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        v-model="locale"
                        class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700"
                        aria-label="Select language"
                    >
                        <option value="en">EN</option>
                        <option value="lg">LG</option>
                    </select>
                    <button
                        v-if="profile"
                        class="hidden rounded bg-slate-900 px-3 py-2 text-sm text-white sm:block"
                        type="button"
                        @click="handleLogout"
                    >
                        {{ $t('nav.logout') }}
                    </button>
                    <button
                        class="inline-flex h-9 w-9 items-center justify-center rounded border border-slate-300 text-slate-700 sm:hidden"
                        type="button"
                        :aria-expanded="mobileMenuOpen"
                        aria-controls="app-mobile-menu"
                        aria-label="Toggle navigation menu"
                        @click="toggleMobileMenu"
                    >
                        <span v-if="!mobileMenuOpen" aria-hidden="true">☰</span>
                        <span v-else aria-hidden="true">✕</span>
                    </button>
                </div>
            </div>
            <nav class="mx-auto hidden max-w-6xl gap-3 px-4 pb-4 text-sm sm:flex">
                <RouterLink class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'home' }">{{ $t('nav.home') }}</RouterLink>
                <RouterLink v-if="profile" class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'messages' }">
                    {{ $t('nav.messages') }}
                    <span
                        v-if="unreadMessagesCount > 0"
                        class="ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white"
                    >
                        {{ unreadMessagesCount }}
                    </span>
                </RouterLink>
                <RouterLink v-if="profile && canManageListings(profile)" class="rounded px-2 py-1 text-slate-700 hover:bg-slate-100" :to="{ name: 'dashboard' }">{{ $t('nav.dashboard') }}</RouterLink>
                <RouterLink v-if="!profile" :class="'rounded px-2 py-1 ' + getLoginLinkClass('signin')" :to="{ name: 'login' }">{{ $t('nav.login') }}</RouterLink>
                <RouterLink v-if="!profile" :class="'rounded px-2 py-1 ' + getLoginLinkClass('signup')" :to="{ name: 'login', query: { mode: 'signup' } }">{{ $t('nav.signup') }}</RouterLink>
            </nav>
            <div v-if="mobileMenuOpen" id="app-mobile-menu" class="border-t border-slate-200 px-4 pb-4 sm:hidden">
                <nav class="grid gap-2 pt-3 text-sm">
                    <RouterLink class="rounded px-2 py-2 text-slate-700 hover:bg-slate-100" :to="{ name: 'home' }">{{ $t('nav.home') }}</RouterLink>
                    <RouterLink v-if="profile" class="rounded px-2 py-2 text-slate-700 hover:bg-slate-100" :to="{ name: 'messages' }">
                        {{ $t('nav.messages') }}
                        <span
                            v-if="unreadMessagesCount > 0"
                            class="ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white"
                        >
                            {{ unreadMessagesCount }}
                        </span>
                    </RouterLink>
                    <RouterLink v-if="profile && canManageListings(profile)" class="rounded px-2 py-2 text-slate-700 hover:bg-slate-100" :to="{ name: 'dashboard' }">{{ $t('nav.dashboard') }}</RouterLink>
                    <RouterLink v-if="!profile" :class="'rounded px-2 py-2 ' + getLoginLinkClass('signin')" :to="{ name: 'login' }">{{ $t('nav.login') }}</RouterLink>
                    <RouterLink v-if="!profile" :class="'rounded px-2 py-2 ' + getLoginLinkClass('signup')" :to="{ name: 'login', query: { mode: 'signup' } }">{{ $t('nav.signup') }}</RouterLink>
                    <button
                        v-if="profile"
                        class="rounded bg-slate-900 px-3 py-2 text-left text-sm text-white"
                        type="button"
                        @click="handleLogout"
                    >
                        {{ $t('nav.logout') }}
                    </button>
                </nav>
            </div>
        </header>

        <div class="mx-auto max-w-6xl p-4">
            <RouterView @auth-changed="refreshProfile" />
        </div>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { canManageListings, getProfile, logout } from './services/authProfile';
import { getUnreadMessageCount } from './services/messages';

const route = useRoute();
const router = useRouter();
const profile = ref(null);
const unreadCount = ref(0);
const appHeader = ref(null);
const mobileMenuOpen = ref(false);
const { locale } = useI18n();

function getLoginLinkClass(linkMode) {
	if (route.name !== 'login') return 'text-slate-700 hover:bg-slate-100';
	
	const currentMode = route.query.mode;
	
	// 'signin' link is active when current mode is NOT 'signup' (or no mode)
	if (linkMode === 'signin') {
		return currentMode !== 'signup' ? 'bg-slate-100 !text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-100';
	}
	
	// 'signup' link is active when current mode IS 'signup'
	return currentMode === 'signup' ? 'bg-slate-100 !text-slate-900 font-semibold' : 'text-slate-700 hover:bg-slate-100';
}
let headerObserver = null;
let removeRouteGuard = null;
let unreadRefreshInterval = null;

const refreshProfile = async () => {
    profile.value = await getProfile(true);
    await refreshUnreadCount();
};

const unreadMessagesCount = computed(() => {
    const value = Number(unreadCount.value ?? 0);
    if (!Number.isFinite(value) || value < 0) {
        return 0;
    }

    return value;
});

const refreshUnreadCount = async () => {
    if (!profile.value) {
        unreadCount.value = 0;
        return;
    }

    try {
        unreadCount.value = await getUnreadMessageCount();
    } catch {
        unreadCount.value = 0;
    }
};

const handleWindowFocus = async () => {
    await refreshUnreadCount();
};

const handleLogout = async () => {
    await logout();
    profile.value = null;
    unreadCount.value = 0;
    mobileMenuOpen.value = false;
    router.push({ name: 'home' });
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const updateHeaderHeight = () => {
    const height = appHeader.value?.offsetHeight ?? 0;
    document.documentElement.style.setProperty('--app-header-height', `${height}px`);
};

onMounted(async () => {
    profile.value = await getProfile();
    await refreshUnreadCount();

    updateHeaderHeight();
    if (appHeader.value && typeof ResizeObserver !== 'undefined') {
        headerObserver = new ResizeObserver(() => {
            updateHeaderHeight();
        });
        headerObserver.observe(appHeader.value);
    }

    window.addEventListener('focus', handleWindowFocus);
    unreadRefreshInterval = window.setInterval(() => {
        void refreshUnreadCount();
    }, 30000);
});

removeRouteGuard = router.afterEach(() => {
    mobileMenuOpen.value = false;
    void refreshUnreadCount();
});

onBeforeUnmount(() => {
    if (headerObserver) {
        headerObserver.disconnect();
        headerObserver = null;
    }

    if (removeRouteGuard) {
        removeRouteGuard();
        removeRouteGuard = null;
    }

    window.removeEventListener('focus', handleWindowFocus);

    if (unreadRefreshInterval) {
        window.clearInterval(unreadRefreshInterval);
        unreadRefreshInterval = null;
    }
});
</script>
