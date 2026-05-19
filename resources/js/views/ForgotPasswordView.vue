<template>
    <section class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-xl font-semibold text-slate-900">{{ $t('passwordReset.requestTitle') }}</h2>
        <p class="mb-4 text-sm text-slate-600">{{ $t('passwordReset.requestSubtitle') }}</p>

        <form class="space-y-3" @submit.prevent="submit">
            <input
                v-model="email"
                class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                type="email"
                :placeholder="$t('login.email')"
                required
            />

            <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm text-white" type="submit" :disabled="submitting">
                {{ $t('passwordReset.sendLink') }}
            </button>
        </form>

        <p v-if="success" class="mt-3 text-sm text-emerald-700">{{ $t('passwordReset.requestSuccess') }}</p>
        <p v-if="error" class="mt-3 text-sm text-rose-600">{{ error }}</p>

        <div class="mt-4 text-right">
            <RouterLink :to="{ name: 'login' }" class="text-xs font-medium text-sky-700 hover:text-sky-800">
                {{ $t('passwordReset.backToLogin') }}
            </RouterLink>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { requestPasswordResetLink } from '../services/authProfile';
import { usePageMeta } from '../composables/usePageMeta';

usePageMeta({ title: 'Forgot Password', robots: 'noindex,nofollow' });

const { t } = useI18n();
const email = ref('');
const submitting = ref(false);
const success = ref(false);
const error = ref('');

async function submit() {
    submitting.value = true;
    success.value = false;
    error.value = '';

    try {
        await requestPasswordResetLink(email.value);
        success.value = true;
    } catch (exception) {
        const message = exception?.response?.data?.message;
        error.value = typeof message === 'string' && message.trim()
            ? message
            : t('passwordReset.requestError');
    } finally {
        submitting.value = false;
    }
}
</script>
