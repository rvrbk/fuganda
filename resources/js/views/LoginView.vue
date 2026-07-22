<template>
    <section class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-xl font-semibold text-slate-900">{{ mode === 'signin' ? $t('login.titleSignIn') : $t('login.titleSignUp') }}</h2>

        <div class="mb-4 grid grid-cols-2 rounded-md border border-slate-200 p-1 text-sm">
            <button
                class="rounded px-3 py-2 font-medium"
                :class="mode === 'signin' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                type="button"
                @click="setMode('signin')"
            >
                {{ $t('login.tabSignIn') }}
            </button>
            <button
                class="rounded px-3 py-2 font-medium"
                :class="mode === 'signup' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                type="button"
                @click="setMode('signup')"
            >
                {{ $t('login.tabSignUp') }}
            </button>
        </div>

        <form class="space-y-3" @submit.prevent="submit">
            <input
                v-if="mode === 'signup'"
                v-model="form.name"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="text"
                :placeholder="$t('login.name')"
                required
            />
            <input
                v-model="form.email"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="email"
                :placeholder="$t('login.email')"
                required
            />
            <input
                v-model="form.password"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="password"
                :placeholder="$t('login.password')"
                required
            />
            <div v-if="mode === 'signin'" class="-mt-1 text-right">
                <RouterLink :to="{ name: 'forgot-password' }" class="text-xs font-medium text-sky-700 hover:text-sky-800">
                    {{ $t('login.forgotPassword') }}
                </RouterLink>
            </div>
            <input
                v-if="mode === 'signup'"
                v-model="form.password_confirmation"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="password"
                :placeholder="$t('login.passwordConfirmation')"
                required
            />

            <div class="space-y-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <input v-model="wantsSellerRole" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500" />
                    <span>{{ $t('login.sellerOptInLabel') }}</span>
                </label>
            </div>

            <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm text-white" type="submit">
                {{ mode === 'signin' ? $t('login.submitSignIn') : $t('login.submitSignUp') }}
            </button>
        </form>

        <div class="my-4 border-t border-slate-200"></div>

        <div class="space-y-2">
            <button
                class="flex w-full items-center justify-center gap-2 rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                type="button"
                @click="loginWithProvider('google')"
            >
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <path d="M21.6 12.23c0-.73-.07-1.43-.19-2.09H12v3.96h5.39a4.6 4.6 0 0 1-1.99 3.03v2.52h3.22c1.88-1.73 2.98-4.27 2.98-7.42Z" fill="#4285F4"/>
                    <path d="M12 22c2.7 0 4.97-.9 6.63-2.45l-3.22-2.52c-.9.6-2.04.95-3.4.95-2.61 0-4.82-1.76-5.61-4.12H3.08v2.6A9.99 9.99 0 0 0 12 22Z" fill="#34A853"/>
                    <path d="M6.39 13.86A6 6 0 0 1 6.07 12c0-.65.12-1.27.32-1.86V7.54H3.08A10 10 0 0 0 2 12c0 1.62.39 3.16 1.08 4.46l3.31-2.6Z" fill="#FBBC05"/>
                    <path d="M12 6.02c1.47 0 2.78.5 3.82 1.49l2.87-2.88A9.97 9.97 0 0 0 12 2a9.99 9.99 0 0 0-8.92 5.54l3.31 2.6C7.18 7.78 9.39 6.02 12 6.02Z" fill="#EA4335"/>
                </svg>
                {{ $t('login.continueWithGoogle') }}
            </button>
            <button
                class="flex w-full items-center justify-center gap-2 rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                type="button"
                @click="loginWithProvider('apple')"
            >
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.04 12.68c.03 2.64 2.3 3.52 2.32 3.53-.02.06-.36 1.24-1.18 2.46-.71 1.05-1.45 2.09-2.6 2.11-1.13.02-1.5-.67-2.8-.67-1.3 0-1.72.65-2.77.69-1.11.04-1.96-1.12-2.68-2.16-1.47-2.13-2.6-6.01-1.09-8.64.75-1.31 2.1-2.14 3.57-2.16 1.11-.02 2.16.75 2.8.75.64 0 1.86-.93 3.14-.79.54.02 2.05.22 3.02 1.64-.08.05-1.8 1.05-1.77 3.24Zm-2.23-6.11c.59-.72.99-1.71.88-2.71-.85.03-1.88.57-2.49 1.28-.54.62-1.01 1.62-.88 2.58.95.07 1.91-.48 2.49-1.15Z"/>
                </svg>
                {{ $t('login.continueWithApple') }}
            </button>
            <button
                v-if="mode === 'signin'"
                class="w-full rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                type="button"
                @click="setMode('signup')"
            >
                {{ $t('login.signUpWithEmail') }}
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-rose-600">{{ specificError || (mode === 'signin' ? $t('login.errorSignIn') : $t('login.errorSignUp')) }}</p>
        <p v-if="socialError" class="mt-2 text-sm text-rose-600">{{ socialError }}</p>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { canManageListings, login, register } from '../services/authProfile';
import { hasActiveSellerSubscription } from '../services/sellerBilling';
import { usePageMeta } from '../composables/usePageMeta';

usePageMeta({ title: 'Sign in', robots: 'noindex,nofollow' });

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const error = ref(false);
const specificError = ref('');
const mode = ref('signin');
const wantsSellerRole = ref(false);
const socialError = computed(() => {
    if (route.query.social_error === 'role_not_allowed') {
        return t('login.socialErrorRoleNotAllowed');
    }

    if (route.query.social_error === 'email_required') {
        return t('login.socialErrorEmailRequired');
    }

    if (route.query.social_error === 'invalid_role') {
        return t('login.socialErrorInvalidRole');
    }

    if (route.query.social_error === 'auth_failed') {
        return t('login.socialErrorAuthFailed');
    }

    return '';
});

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const emit = defineEmits(['auth-changed']);

function extractApiErrorMessage(err) {
    const data = err?.response?.data;
    const errors = data?.errors;

    if (errors && typeof errors === 'object') {
        for (const fieldErrors of Object.values(errors)) {
            if (Array.isArray(fieldErrors) && fieldErrors.length > 0 && typeof fieldErrors[0] === 'string') {
                return fieldErrors[0];
            }

            if (typeof fieldErrors === 'string' && fieldErrors.length > 0) {
                return fieldErrors;
            }
        }
    }

    if (typeof data?.message === 'string' && data.message.length > 0) {
        return data.message;
    }

    return '';
}

function clearAuthError() {
    error.value = false;
    specificError.value = '';
}

async function submit() {
    clearAuthError();

    try {
        const profile = mode.value === 'signin'
            ? await login({ email: form.value.email, password: form.value.password })
            : await register({
                name: form.value.name,
                email: form.value.email,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation,
                role: wantsSellerRole.value ? 'seller' : 'buyer',
            });

        emit('auth-changed');

        const isSellerAccount = canManageListings(profile);
        if (isSellerAccount) {
            let hasActiveSubscription = false;
            try {
                hasActiveSubscription = await hasActiveSellerSubscription();
            } catch {
                hasActiveSubscription = false;
            }

            if (!hasActiveSubscription) {
                router.push({ name: 'seller-onboarding', query: route.query.redirect ? { redirect: route.query.redirect } : {} });
                return;
            }
        }

        const fallbackRoute = canManageListings(profile)
            ? { name: 'dashboard' }
            : { name: 'home' };

        router.push(route.query.redirect || fallbackRoute);
    } catch (err) {
        specificError.value = extractApiErrorMessage(err);
        error.value = true;
    }
}

function setMode(nextMode) {
    mode.value = nextMode;
    clearAuthError();
}

watch(
    [
        () => form.value.name,
        () => form.value.email,
        () => form.value.password,
        () => form.value.password_confirmation,
        wantsSellerRole,
    ],
    clearAuthError,
);

function loginWithProvider(provider) {
    const encodedRole = encodeURIComponent(wantsSellerRole.value ? 'seller' : 'buyer');
    window.location.assign(`/auth/${provider}/redirect?role=${encodedRole}`);
}
</script>
