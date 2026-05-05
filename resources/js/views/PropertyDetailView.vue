<template>
	<section v-if="loading" class="animate-pulse space-y-3 rounded-lg border border-slate-200 bg-white p-5">
		<div class="h-6 w-1/2 rounded bg-slate-200"></div>
		<div class="h-72 rounded bg-slate-200"></div>
	</section>

	<section v-else-if="!property" class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
		{{ $t('properties.notFound') }}
	</section>

	<section v-else class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
		<div class="flex items-start justify-between gap-4">
			<div>
				<h2 class="text-2xl font-semibold text-slate-900">{{ property.title }}</h2>
				<p class="text-sm text-slate-600">{{ property.district }} - {{ property.city }}</p>
			</div>
			<RouterLink :to="{ name: 'property-edit', params: { id: property.id } }" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">
				{{ $t('actions.edit') }}
			</RouterLink>
		</div>

		<img :src="property.imageUrl" :alt="property.title" loading="lazy" class="h-72 w-full rounded object-cover" />
		<p class="text-slate-700">{{ property.description }}</p>
		<p class="text-xl font-semibold text-emerald-700">{{ formatUgx(property.price) }}</p>

		<PropertyMap :markers="[property]" :selected-marker-id="property.id" />

		<form class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4" @submit.prevent="sendMessage">
			<h3 class="font-medium text-slate-900">{{ $t('messages.contactAgent') }}</h3>
			<input v-model="message.subject" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('messages.subject')" required />
			<textarea v-model="message.body" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" rows="3" :placeholder="$t('messages.body')" required></textarea>
			<button class="rounded bg-sky-700 px-3 py-2 text-sm text-white" type="submit">{{ $t('actions.send') }}</button>
		</form>
	</section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import PropertyMap from '../components/PropertyMap.vue';
import { createMessage } from '../services/messages';
import { getProperty } from '../services/properties';
import { formatUgx } from '../utils/formatters';

const route = useRoute();
const loading = ref(true);
const property = ref(null);
const message = ref({ subject: '', body: '' });

async function load() {
	loading.value = true;
	property.value = await getProperty(route.params.id);
	loading.value = false;
}

async function sendMessage() {
	if (!property.value) {
		return;
	}

	await createMessage({ ...message.value, propertyId: property.value.id });
	message.value = { subject: '', body: '' };
}

onMounted(load);
</script>
