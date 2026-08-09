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
		<p
			v-if="paymentSuccess"
			class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
		>
			{{ $t('userContact.paymentInitiated') || 'Payment request sent to your phone. Please approve to continue.' }}
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
			<div class="overflow-hidden rounded border border-slate-200 bg-slate-100 cursor-pointer" @click="openLightbox(activeMediaIndex)">
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
				<img
					v-else
					:src="placeholderImageUrl"
					:alt="property.title"
					loading="lazy"
					class="h-72 w-full object-cover"
				/>
			</div>

			<div v-if="mediaItems.length" class="flex gap-2 overflow-x-auto pb-1">
				<button
					v-for="(item, index) in mediaItems"
					:key="`${item.path}-${index}`"
					type="button"
					class="relative h-16 w-20 flex-shrink-0 overflow-hidden rounded border border-slate-300 transition sm:h-20 sm:w-24 cursor-pointer"
					:class="index === activeMediaIndex ? 'border-sky-500 ring-2 ring-inset ring-sky-500' : ''"
					@click="openLightbox(index)"
				>
					<img v-if="item.kind === 'image'" :src="item.path" :alt="property.title" loading="lazy" class="h-full w-full object-cover" />
					<video v-else :src="item.path" muted playsinline class="h-full w-full bg-black object-cover"></video>
				</button>
			</div>
		</div>

		<!-- Lightbox Modal -->
		<div v-if="lightboxOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click="closeLightbox">
			<button class="absolute top-4 right-4 rounded bg-white/20 p-2 text-white hover:bg-white/30 transition" @click.stop="closeLightbox">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
				</svg>
			</button>
			<button class="absolute left-4 rounded bg-white/20 p-2 text-white hover:bg-white/30 transition" @click.stop="prevMedia" v-if="mediaItems.length > 1">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
				</svg>
			</button>
			<button class="absolute right-4 rounded bg-white/20 p-2 text-white hover:bg-white/30 transition" @click.stop="nextMedia" v-if="mediaItems.length > 1">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
				</svg>
			</button>
			<div class="max-w-full max-h-full flex items-center justify-center">
				<img
					v-if="activeMedia && activeMedia.kind === 'image'"
					:src="activeMedia.path"
					:alt="property.title"
					class="max-w-[90vw] max-h-[90vh] object-contain"
				/>
				<video
					v-else-if="activeMedia && activeMedia.kind === 'video'"
					:src="activeMedia.path"
					controls
					playsinline
					class="max-w-[90vw] max-h-[90vh] bg-black object-contain"
				></video>
			</div>
		</div>
		<p class="text-slate-700">{{ property.description }}</p>

		<div v-if="property.bedrooms || property.bathrooms || property.propertyType" class="grid grid-cols-2 gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
			<h3 class="col-span-2 font-medium text-slate-900">{{ $t('propertyForm.propertyDetails') }}</h3>
			<div v-if="property.bedrooms" class="flex items-center gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
				</svg>
				<span class="text-sm text-slate-700">{{ property.bedrooms }} {{ $t('filters.bedrooms') }}</span>
			</div>
			<div v-if="property.bathrooms" class="flex items-center gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
				</svg>
				<span class="text-sm text-slate-700">{{ property.bathrooms }} {{ $t('propertyForm.bathroomsLabel') }}</span>
			</div>
			<div v-if="property.propertyType" class="flex items-center gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
				</svg>
				<span class="text-sm text-slate-700">{{ $t(`propertyForm.propertyTypes.${property.propertyType}`) || capitalizeFirst(property.propertyType) }}</span>
			</div>
			<div v-if="property.listingType" class="flex items-center gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
				</svg>
				<span class="text-sm text-slate-700">{{ property.listingType === 'rent' ? $t('propertyForm.listingTypeRent') : $t('propertyForm.listingTypeSale') }}</span>
			</div>
		</div>

		<p class="text-xl font-semibold text-emerald-700">{{ formatPrice(property.price, property.priceCurrency) }}<span v-if="property.listingType === 'rent'">{{ $t('properties.perMonth') }}</span></p>

		<PropertyMap :markers="propertyMarkers" />

		<div v-if="canSendMessage" class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
			<h3 class="font-medium text-slate-900">{{ $t('messages.contactAgent') }}</h3>
			
			<div v-if="isGuestUser" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
				<p>{{ $t('userContact.loginRequired') }}</p>
				<RouterLink
					class="mt-2 inline-block rounded bg-sky-700 px-3 py-1.5 text-xs text-white"
					:to="{ name: 'login', query: { redirect: `/properties/${property?.id}` } }"
				>
					{{ $t('nav.login') }}
				</RouterLink>
			</div>
			
			<div v-else-if="!hasPaidContactFee" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
				<p>{{ $t('userContact.blockingCallout', { amount: formatPrice(10000, 'UGX') }) }}</p>
				<button
					class="mt-2 rounded bg-sky-700 px-3 py-1.5 text-xs text-white"
					@click="showPaymentModal = true"
				>
					{{ $t('userContact.payNow', { amount: formatPrice(10000, 'UGX') }) }}
				</button>
			</div>
			
			<form v-else @submit.prevent="sendMessage">
				<p
					v-if="messageFeedback"
					class="rounded border px-3 py-2 text-sm"
					:class="messageFeedback.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
				>
					{{ messageFeedback.text }}
				</p>
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
		</div>

		<!-- Payment Modal -->
		<div v-if="showPaymentModal" class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/70 p-4" @click="showPaymentModal = false">
			<div class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl isolate" @click.stop>
				<h3 class="text-lg font-semibold text-slate-900">{{ $t('userContact.paymentTitle') || 'Complete Payment' }}</h3>
				
				<!-- Success message (shown after payment is initiated) -->
				<div v-if="paymentInitiatedInModal" class="mt-4 p-4 rounded-lg border border-emerald-200 bg-emerald-50">
					<p class="text-sm text-emerald-800">{{ $t('userContact.paymentInitiated') || 'Payment request sent to your phone. Please approve to continue.' }}</p>
					<button
						class="mt-3 rounded bg-sky-700 px-4 py-2 text-sm text-white"
						@click="showPaymentModal = false; paymentInitiatedInModal = false;"
					>
						{{ $t('actions.close') || 'Close' }}
					</button>
				</div>
				
				<template v-else>
					<p class="mt-1 text-sm text-slate-600">{{ $t('userContact.paymentDescription', { amount: formatPrice(10000, 'UGX') }) || `Pay UGX 10,000 to contact the agent` }}</p>

					<div class="mt-4 space-y-3">
					<div>
						<label class="block text-sm font-medium text-slate-700">{{ $t('userContact.phoneNumber') || 'Phone Number' }}</label>
						<input
							v-model="phoneNumber"
							type="tel"
							class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm"
							:placeholder="$t('userContact.phonePlaceholder') || 'e.g. 256772123456'"
							required
						/>
						<p class="mt-1 text-xs text-slate-500">{{ $t('userContact.phoneHint') || 'Enter your MTN or Airtel number' }}</p>
					</div>

					<div>
						<label class="block text-sm font-medium text-slate-700">{{ $t('userContact.paymentProvider') || 'Payment Provider' }}</label>
						<select
							v-model="selectedPaymentProvider"
							class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm"
						>
							<option value="mtn">MTN Mobile Money</option>
							<option value="airtel">Airtel Money</option>
						</select>
					</div>
				</div>

				<div class="mt-6 flex gap-3">
					<button
						class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
						@click="showPaymentModal = false"
					>
						{{ $t('actions.cancel') || 'Cancel' }}
					</button>
					<button
						class="rounded bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
						@click="initiateContactPayment"
						:disabled="!phoneNumber || isSubmitting"
					>
						<span v-if="!isSubmitting">{{ $t('userContact.confirmPayment') || 'Confirm Payment' }}</span>
						<span v-else>{{ $t('actions.processing') || 'Processing...' }}</span>
					</button>
				</div>

				<p v-if="paymentError" class="mt-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
					{{ paymentError }}
				</p>
				</template>
			</div>
		</div>
	</section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import PropertyMap from '../components/PropertyMap.vue';
import { canManageListings, getProfile } from '../services/authProfile';
import { createMessage } from '../services/messages';
import { getProperty } from '../services/properties';
import { formatPrice } from '../utils/formatters';
import { usePageMeta } from '../composables/usePageMeta';
import { checkBuyerContactPayment, initiateBuyerContactPayment } from '../services/buyerContact';

const route = useRoute();
const { t } = useI18n();
const loading = ref(true);
const property = ref(null);

function capitalizeFirst(str) {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

usePageMeta(() => {
	const p = property.value;
	return {
		title: p?.title ?? '',
		description: p?.description ?? '',
		image: p?.imageUrl ?? '',
		type: 'article',
		jsonLd: p ? {
			'@context': 'https://schema.org',
			'@type': 'RealEstateListing',
			name: p.title,
			description: p.description,
			url: window.location.href,
			...(p.imageUrl ? { image: [p.imageUrl] } : {}),
			address: {
				'@type': 'PostalAddress',
				...(p.address ? { streetAddress: p.address } : {}),
				addressLocality: p.city ?? '',
				addressRegion: p.district ?? '',
				addressCountry: 'UG',
			},
			...(p.latitude != null && p.longitude != null ? {
				geo: {
					'@type': 'GeoCoordinates',
					latitude: p.latitude,
					longitude: p.longitude,
				},
			} : {}),
			...(p.bedrooms ? { numberOfBedrooms: p.bedrooms } : {}),
			...(p.bathrooms ? { numberOfBathroomsTotal: p.bathrooms } : {}),
			offers: {
				'@type': 'Offer',
				price: p.price,
				priceCurrency: p.priceCurrency || 'UGX',
				businessFunction: p.listingType === 'sale'
					? 'http://purl.org/goodrelations/v1#Sell'
					: 'http://purl.org/goodrelations/v1#LeaseOut',
			},
		} : null,
	};
});
const message = ref({ email: '', subject: '', body: '' });
const messageFeedback = ref(null);
const profile = ref(null);
const activeMediaIndex = ref(0);
const hasPaidContactFee = ref(false);
const isCheckingContactFee = ref(false);
const placeholderImageUrl = '/images/property-placeholder.jpg';
const lightboxOpen = ref(false);
const phoneNumber = ref('');
const selectedPaymentProvider = ref('mtn');
const showPaymentModal = ref(false);
const isSubmitting = ref(false);
const paymentError = ref(null);
const paymentSuccess = ref(false);
const paymentCheckInterval = ref(null);
const paymentInitiatedInModal = ref(false);

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

// Auto-clear payment success message after 10 seconds
watch(() => paymentSuccess.value, (newVal) => {
	if (newVal) {
		setTimeout(() => {
			paymentSuccess.value = false;
		}, 10000);
	}
});

// Clear payment check interval when payment is confirmed
watch(() => hasPaidContactFee.value, (newVal) => {
	if (newVal && paymentCheckInterval.value) {
		clearInterval(paymentCheckInterval.value);
		paymentCheckInterval.value = null;
	}
});

async function load() {
	loading.value = true;
	property.value = await getProperty(route.params.id);
	activeMediaIndex.value = 0;
	loading.value = false;
}

async function sendMessage() {
	if (!property.value || !canSendMessage.value || !hasPaidContactFee.value) {
		return;
	}

	try {
		await createMessage({ ...message.value, propertyId: property.value.id });
		message.value = { subject: '', body: '' };
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

function openLightbox(index) {
	activeMediaIndex.value = index;
	lightboxOpen.value = true;
	document.body.style.overflow = 'hidden';
}

function closeLightbox() {
	lightboxOpen.value = false;
	document.body.style.overflow = '';
}

function nextMedia() {
	if (activeMediaIndex.value < mediaItems.value.length - 1) {
		activeMediaIndex.value++;
	}
}

function prevMedia() {
	if (activeMediaIndex.value > 0) {
		activeMediaIndex.value--;
	}
}

function handleKeydown(event) {
	if (!lightboxOpen.value) return;
	
	switch (event.key) {
		case 'Escape':
			closeLightbox();
			break;
		case 'ArrowRight':
			nextMedia();
			break;
		case 'ArrowLeft':
			prevMedia();
			break;
	}
}

async function checkContactFeePayment() {
	if (isGuestUser.value) {
		hasPaidContactFee.value = false;
		return;
	}
	
	if (!property.value) {
		hasPaidContactFee.value = false;
		return;
	}
	
	isCheckingContactFee.value = true;
	try {
		const result = await checkBuyerContactPayment(property.value.id);
		hasPaidContactFee.value = result.hasPaid;
	} catch {
		hasPaidContactFee.value = false;
	} finally {
		isCheckingContactFee.value = false;
	}
}

async function initiateContactPayment() {
	if (!property.value || !phoneNumber.value) {
		return;
	}
	
	isSubmitting.value = true;
	paymentError.value = null;
	paymentInitiatedInModal.value = false;
	
	try {
		const result = await initiateBuyerContactPayment(property.value.id, {
			payment_provider: selectedPaymentProvider.value,
			phone_number: phoneNumber.value,
			billing_email: profile.value?.email || '',
		});
		
		if (result.redirectUrl) {
			window.location.href = result.redirectUrl;
		} else if (result.hasPaid) {
			hasPaidContactFee.value = true;
			paymentSuccess.value = true;
			paymentInitiatedInModal.value = true;
			// Modal stays open until user manually closes it
		} else {
			// For MTN/Airtel Mobile Money, payment request is sent to phone
			paymentSuccess.value = true;
			paymentInitiatedInModal.value = true;
			// Clear any existing interval
			if (paymentCheckInterval.value) {
				clearInterval(paymentCheckInterval.value);
			}
			// Set up polling to check payment status periodically
			paymentCheckInterval.value = setInterval(async () => {
				const status = await checkBuyerContactPayment(property.value.id);
				if (status.hasPaid) {
					clearInterval(paymentCheckInterval.value);
					paymentCheckInterval.value = null;
					hasPaidContactFee.value = true;
					paymentSuccess.value = false;
					paymentInitiatedInModal.value = false;
					showPaymentModal.value = false;
					phoneNumber.value = '';
				}
			}, 10000); // Check every 10 seconds
			// Also do an immediate check after 3 seconds
			setTimeout(() => checkContactFeePayment(), 3000);
		}
	} catch (error) {
		console.error('Failed to initiate contact payment:', error);
		paymentError.value = error.response?.data?.message || error.message || t('userContact.paymentFailed') || 'Payment initiation failed. Please try again.';
	} finally {
		isSubmitting.value = false;
	}
}

onMounted(async () => {
	profile.value = await getProfile();
	
	await load();
	await checkContactFeePayment();
	
	window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
	window.removeEventListener('keydown', handleKeydown);
	document.body.style.overflow = '';
	// Clear payment check interval on unmount
	if (paymentCheckInterval.value) {
		clearInterval(paymentCheckInterval.value);
		paymentCheckInterval.value = null;
	}
});
</script>
