<template>
	<section class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
		<h2 class="text-xl font-semibold text-slate-900">{{ $t('nav.messages') }}</h2>

		<div v-if="loading" class="space-y-2">
			<div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded bg-slate-200"></div>
		</div>

		<div v-else-if="!items.length" class="rounded border border-dashed border-slate-300 p-6 text-sm text-slate-500">
			{{ $t('messages.empty') }}
		</div>

		<ul v-else class="space-y-2">
			<li v-for="item in items" :key="item.id" class="rounded border border-slate-200 p-3">
				<p class="font-medium text-slate-900">{{ item.subject }}</p>
				<p class="mt-1 text-sm text-slate-600">{{ item.body }}</p>
				<p class="mt-1 text-xs text-slate-500">{{ item.from }} - {{ new Date(item.createdAt).toLocaleString() }}</p>
			</li>
		</ul>

		<form class="grid gap-2 md:grid-cols-4" @submit.prevent="send">
			<input v-model.number="draft.propertyId" type="number" min="1" class="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Property ID" required />
			<input v-model="draft.subject" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('messages.subject')" required />
			<input v-model="draft.body" class="rounded border border-slate-300 px-3 py-2 text-sm md:col-span-2" :placeholder="$t('messages.body')" required />
			<button class="w-fit rounded bg-sky-700 px-3 py-2 text-sm text-white" type="submit">{{ $t('actions.send') }}</button>
		</form>
	</section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { createMessage, listMessages } from '../services/messages';

const loading = ref(true);
const items = ref([]);
const draft = ref({ propertyId: null, subject: '', body: '' });

async function load() {
	loading.value = true;
	items.value = await listMessages();
	loading.value = false;
}

async function send() {
	await createMessage({
		propertyId: draft.value.propertyId,
		body: draft.value.body,
	});
	draft.value = { propertyId: null, subject: '', body: '' };
	await load();
}

onMounted(load);
</script>
