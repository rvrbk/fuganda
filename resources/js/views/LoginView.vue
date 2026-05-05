<template>
    <section class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-xl font-semibold text-slate-900">{{ $t('login.title') }}</h2>

        <form class="space-y-3" @submit.prevent="submit">
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
            <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm text-white" type="submit">
                {{ $t('login.submit') }}
            </button>
        </form>

        <p v-if="error" class="mt-3 text-sm text-rose-600">{{ $t('login.error') }}</p>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { login } from '../services/authProfile';

const route = useRoute();
const router = useRouter();
const error = ref(false);

const form = ref({
    email: '',
    password: '',
});

const emit = defineEmits(['auth-changed']);

async function submit() {
    error.value = false;

    try {
        await login(form.value);
        emit('auth-changed');
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch {
        error.value = true;
    }
}
</script>
