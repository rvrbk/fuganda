<template>
    <section class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-xl font-semibold text-slate-900">{{ $t('passwordReset.resetTitle') }}</h2>

        <form class="space-y-3" @submit.prevent="submit">
            <input
                v-model="email"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="email"
                :placeholder="$t('login.email')"
                required
            />
            <input
                v-model="password"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="password"
                :placeholder="$t('login.password')"
                required
            />
            <input
                v-model="passwordConfirmation"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="password"
                :placeholder="$t('login.passwordConfirmation')"
                required
            />

            <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm text-white" type="submit" :disabled="submitting">
                {{ $t('passwordReset.resetAction') }}
            </button>
        </form>

        <p v-if="success" class="mt-3 text-sm text-emerald-700">{{ $t('passwordReset.resetSuccess') }}</p>
        <p v-if="error" class="mt-3 text-sm text-rose-600">{{ error }}</p>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { resetPassword } from '../services/authProfile';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
const password = ref('');
const passwordConfirmation = ref('');
const submitting = ref(false);
const success = ref(false);
const error = ref('');

async function submit() {
    submitting.value = true;
    success.value = false;
    error.value = '';

    try {
        await resetPassword({
            token: route.params.token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        success.value = true;
        window.setTimeout(() => {
            router.push({ name: 'login' });
        }, 900);
    } catch (exception) {
        const message = exception?.response?.data?.message;
        error.value = typeof message === 'string' && message.trim()
            ? message
            : t('passwordReset.resetError');
    } finally {
        submitting.value = false;
    }
}
</script>
