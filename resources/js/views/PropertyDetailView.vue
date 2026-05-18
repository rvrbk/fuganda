<template>
	<section v-if="loading" class="animate-pulse space-y-3 rounded-lg border border-slate-200 bg-white p-5">
		<div class="h-6 w-1/2 rounded bg-slate-200"></div>
		<div class="h-72 rounded bg-slate-200"></div>
	</section>

	<section v-else-if="!property" class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
		{{ $t('properties.notFound') }}
	</section>

	<section v-else class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
		<p
			v-if="showCreatedSuccess"
			class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
		>
			{{ $t('properties.createdSuccess') }}
		</p>

		<div class="flex items-start justify-between gap-4">
			<div>
				<h2 class="text-2xl font-semibold text-slate-900">{{ property.title }}</h2>
				<p class="text-sm text-slate-600">{{ property.district }} - {{ property.city }}</p>
			</div>
			<RouterLink
				v-if="canEditListing"
				:to="{ name: 'property-edit', params: { id: property.id } }"
				class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700"
			>
				{{ $t('actions.edit') }}
			</RouterLink>
		</div>

		<div class="space-y-3">
			<div class="overflow-hidden rounded border border-slate-200 bg-slate-100">
				<img
					v-if="activeMedia && activeMedia.kind === 'image'"
					:src="activeMedia.path"
					:alt="property.title"
					loading="lazy"
					class="h-72 w-full object-cover"
				/>
				<video
					v-else-if="activeMedia && activeMedia.kind === 'video'"
					:key="activeMedia.path"
					:src="activeMedia.path"
					controls
					playsinline
					class="h-72 w-full bg-black object-contain"
				></video>
				<div v-else class="flex h-72 items-center justify-center text-sm text-slate-500">{{ $t('properties.notFound') }}</div>
			</div>

			<div v-if="mediaItems.length" class="flex gap-2 overflow-x-auto pb-1">
				<button
					v-for="(item, index) in mediaItems"
					:key="`${item.path}-${index}`"
					type="button"
					class="relative h-16 w-20 flex-shrink-0 overflow-hidden rounded border border-slate-300 transition sm:h-20 sm:w-24"
					:class="index === activeMediaIndex ? 'border-sky-500 ring-2 ring-inset ring-sky-500' : ''"
					@click="activeMediaIndex = index"
				>
					<img v-if="item.kind === 'image'" :src="item.path" :alt="property.title" loading="lazy" class="h-full w-full object-cover" />
					<video v-else :src="item.path" muted playsinline class="h-full w-full bg-black object-cover"></video>
				</button>
			</div>
		</div>
		<p class="text-slate-700">{{ property.description }}</p>
		<p class="text-xl font-semibold text-emerald-700">{{ formatPrice(property.price, property.priceCurrency) }}</p>

		<PropertyMap :markers="propertyMarkers" />

		<form v-if="canSendMessage" class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4" @submit.prevent="sendMessage">
			<h3 class="font-medium text-slate-900">{{ $t('messages.contactAgent') }}</h3>
			<p
				v-if="messageFeedback"
				class="rounded border px-3 py-2 text-sm"
				:class="messageFeedback.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
			>
				{{ messageFeedback.text }}
			</p>
			<input
				v-if="isGuestUser"
				v-model="message.email"
				class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
				type="email"
				:placeholder="$t('messages.guestEmail')"
				required
				@input="clearMessageFeedback"
			/>
			<input
				v-model="message.subject"
				class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
				:placeholder="$t('messages.subject')"
				required
				@input="clearMessageFeedback"
			/>
			<textarea
				v-model="message.body"
				class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
				rows="3"
				:placeholder="$t('messages.body')"
				required
				@input="clearMessageFeedback"
			></textarea>
			<button class="rounded bg-sky-700 px-3 py-2 text-sm text-white" type="submit">{{ $t('actions.send') }}</button>
		</form>
	</section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import PropertyMap from '../components/PropertyMap.vue';
import { canManageListings, getProfile } from '../services/authProfile';
import { createGuestPropertyContact, createMessage } from '../services/messages';
import { getProperty } from '../services/properties';
import { formatPrice } from '../utils/formatters';

const route = useRoute();
const { t } = useI18n();
const loading = ref(true);
const property = ref(null);
const message = ref({ email: '', subject: '', body: '' });
const messageFeedback = ref(null);
const profile = ref(null);
const activeMediaIndex = ref(0);

const mediaItems = computed(() => {
	if (!property.value) {
		return [];
	}

	if (Array.isArray(property.value.media) && property.value.media.length > 0) {
		return property.value.media;
	}

	if (property.value.imageUrl) {
		return [{ path: property.value.imageUrl, kind: 'image' }];
	}

	return [];
});

const activeMedia = computed(() => mediaItems.value[activeMediaIndex.value] ?? mediaItems.value[0] ?? null);

const propertyMarkers = computed(() => {
	if (!property.value) {
		return [];
	}

	return [property.value];
});

const canEditListing = computed(() => {
	if (!property.value || !profile.value) {
		return false;
	}

	if (!canManageListings(profile.value)) {
		return false;
	}

	return Number(profile.value.id) === Number(property.value.ownerId);
});

const canSendMessage = computed(() => {
	if (!property.value || !profile.value) {
		return true;
	}

	return Number(profile.value.id) !== Number(property.value.ownerId);
});

const isGuestUser = computed(() => !profile.value);
const showCreatedSuccess = computed(() => String(route.query.created ?? '') === '1');

async function load() {
	loading.value = true;
	property.value = await getProperty(route.params.id);
	activeMediaIndex.value = 0;
	loading.value = false;
}

async function sendMessage() {
	if (!property.value || !canSendMessage.value) {
		return;
	}

	try {
		if (isGuestUser.value) {
			await createGuestPropertyContact({
				...message.value,
				propertyId: property.value.id,
			});

			message.value = { email: '', subject: '', body: '' };
			messageFeedback.value = {
				type: 'success',
				text: t('messages.guestSendSuccess'),
			};
			return;
		}

		await createMessage({ ...message.value, propertyId: property.value.id });
		message.value = { email: '', subject: '', body: '' };
		messageFeedback.value = {
			type: 'success',
			text: t('messages.sendSuccess'),
		};
	} catch {
		messageFeedback.value = {
			type: 'error',
			text: t('messages.sendError'),
		};
	}
}

function clearMessageFeedback() {
	if (messageFeedback.value) {
		messageFeedback.value = null;
	}
}

onMounted(async () => {
	profile.value = await getProfile();

	await load();
});
</script>
