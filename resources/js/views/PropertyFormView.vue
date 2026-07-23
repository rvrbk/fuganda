<template>
	<section class="rounded-lg border border-slate-200 bg-white p-5">
		<h2 class="mb-4 text-xl font-semibold text-slate-900">{{ isEdit ? $t('actions.editListing') : $t('actions.createListing') }}</h2>

		<div v-if="showSubscriptionBlock" class="mb-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
			<p>{{ $t('sellerOnboarding.blockingCallout') }}</p>
			<RouterLink class="mt-2 inline-block font-semibold underline" :to="{ name: 'seller-onboarding', query: { redirect: route.fullPath } }">
				{{ $t('sellerOnboarding.openOnboarding') }}
			</RouterLink>
		</div>

		<form class="grid gap-3 md:grid-cols-2" @submit.prevent="save">
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-title">{{ $t('propertyForm.title') }}</label>
				<input id="property-title" v-model="form.title" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.title')" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-media-upload">{{ $t('propertyForm.mediaUploadLabel') }}</label>
				<div class="flex items-center gap-2">
					<button
						type="button"
						class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
						@click="openMediaPicker"
					>
						{{ $t('propertyForm.mediaChooseFiles') }}
					</button>
					<span class="min-w-0 truncate text-xs text-slate-500">{{ selectedMediaLabel }}</span>
				</div>
				<input
					id="property-media-upload"
					ref="mediaInput"
					type="file"
					multiple
					accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,video/x-m4v,.jpg,.jpeg,.png,.webp,.mp4,.webm,.mov,.m4v"
					class="sr-only"
					@change="onMediaSelected"
				/>
				<p class="mt-1 text-xs text-slate-500">{{ $t('propertyForm.mediaUploadHint') }}</p>
				<p v-if="isUploadingMedia" class="mt-1 text-xs text-slate-600">{{ $t('propertyForm.uploadingMedia') }}</p>
				<p v-if="mediaUploadError" class="mt-1 text-xs text-rose-600">{{ mediaUploadError }}</p>
				<div v-if="mediaItems.length" class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-3">
					<div v-for="(media, index) in mediaItems" :key="`${media.path}-${index}`" class="rounded border border-slate-200 p-2">
						<img
							v-if="media.kind === 'image'"
							:src="media.path"
							alt="Property media preview"
							class="h-24 w-full rounded border border-slate-200 object-cover"
						/>
						<video
							v-else
							:src="media.path"
							class="h-24 w-full rounded border border-slate-200 bg-slate-900"
							controls
							muted
							preload="metadata"
						></video>
						<button
							type="button"
							class="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"
							@click="removeMedia(index)"
						>
							{{ $t('propertyForm.removeMedia') }}
						</button>
					</div>
				</div>
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-district">{{ $t('propertyForm.districtLabel') }}</label>
				<select id="property-district" v-model="form.district" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
					<option value="">{{ $t('propertyForm.districtLabel') }}</option>
					<option v-for="district in districtOptions" :key="district" :value="district">{{ district }}</option>
				</select>
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-city">{{ $t('propertyForm.cityLabel') }}</label>
				<select id="property-city" v-model="form.city" class="w-full rounded border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100 disabled:text-slate-400" :disabled="!form.district" required>
					<option value="">{{ $t('propertyForm.cityLabel') }}</option>
					<option v-for="city in cityOptions" :key="city" :value="city">{{ city }}</option>
				</select>
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-listing-type">{{ $t('propertyForm.listingTypeLabel') }}</label>
				<select id="property-listing-type" v-model="form.listingType" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
					<option value="">{{ $t('propertyForm.listingTypeLabel') }}</option>
					<option value="rent">{{ $t('propertyForm.listingTypeRent') }}</option>
					<option value="sale">{{ $t('propertyForm.listingTypeSale') }}</option>
				</select>
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-property-type">{{ $t('propertyForm.propertyTypeLabel') }}</label>
				<select id="property-property-type" v-model="form.propertyType" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
					<option value="">{{ $t('propertyForm.propertyTypeLabel') }}</option>
					<option v-for="propertyType in propertyTypeOptions" :key="propertyType" :value="propertyType">{{ propertyType }}</option>
				</select>
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-price">{{ $t('propertyForm.priceLabel') }}</label>
				<div class="grid grid-cols-3 gap-2">
					<input
						id="property-price"
						v-model.number="form.price"
						type="number"
						class="col-span-2 w-full rounded border border-slate-300 px-3 py-2 text-sm"
						:placeholder="$t('propertyForm.pricePlaceholder')"
						required
					/>
					<select
						id="property-price-currency"
						v-model="form.priceCurrency"
						class="col-span-1 w-full rounded border border-slate-300 px-3 py-2 text-sm"
						required
					>
						<option value="">{{ $t('propertyForm.priceCurrencyPlaceholder') }}</option>
						<option value="UGX">{{ $t('propertyForm.currencyUGX') }}</option>
						<option value="USD">{{ $t('propertyForm.currencyUSD') }}</option>
					</select>
				</div>
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-bedrooms">{{ $t('propertyForm.bedroomsLabel') }}</label>
				<input id="property-bedrooms" v-model.number="form.bedrooms" type="number" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.bedroomsLabel')" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-bathrooms">{{ $t('propertyForm.bathroomsLabel') }}</label>
				<input id="property-bathrooms" v-model.number="form.bathrooms" type="number" min="0" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.bathroomsLabel')" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-latitude">{{ $t('propertyForm.latitudeLabel') }}</label>
				<input id="property-latitude" v-model.number="form.latitude" type="number" step="0.000001" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.latitudeLabel')" required />
			</div>
			<div>
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-longitude">{{ $t('propertyForm.longitudeLabel') }}</label>
				<input id="property-longitude" v-model.number="form.longitude" type="number" step="0.000001" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.longitudeLabel')" required />
			</div>

			<div class="md:col-span-2 rounded border border-slate-200 p-3">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $t('propertyForm.mapPickerLabel') }}</p>
				<p class="mt-1 text-xs text-slate-500">{{ $t('propertyForm.mapPickerHint') }}</p>
				<div ref="mapHost" class="mt-3 h-64 w-full rounded border border-slate-200"></div>
			</div>

			<div class="md:col-span-2">
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-address">{{ $t('propertyForm.addressLabel') }}</label>
				<input id="property-address" v-model="form.address" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.addressLabel')" required />
			</div>

			<div class="md:col-span-2">
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-description">{{ $t('propertyForm.description') }}</label>
				<textarea id="property-description" v-model="form.description" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" rows="4" :placeholder="$t('propertyForm.description')" required></textarea>
			</div>
			<div class="md:col-span-2">
				<button class="rounded bg-slate-900 px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:bg-slate-500" type="submit" :disabled="isUploadingMedia || showSubscriptionBlock">{{ $t('actions.save') }}</button>
			</div>
		</form>
	</section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import L from 'leaflet';
import { hasActiveSellerSubscription } from '../services/sellerBilling';
import { getProfile, isBuyerProfile } from '../services/authProfile';
import { createProperty, extractApiErrorMessage, getProperty, updateProperty, uploadPropertyMedia } from '../services/properties';
import { listCitiesByDistrict, listLocations } from '../services/locations';
import { usePageMeta } from '../composables/usePageMeta';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const isEdit = computed(() => Boolean(route.params.id));

usePageMeta(() => ({ title: isEdit.value ? 'Edit Listing' : 'Create Listing', robots: 'noindex,nofollow' }));
const mapHost = ref(null);
const districtOptions = ref([]);
const citiesByDistrict = ref({});
const propertyTypeOptions = ref(['apartment', 'house', 'land', 'commercial']);
const isUploadingMedia = ref(false);
const mediaItems = ref([]);
const mediaUploadError = ref('');
const showSubscriptionBlock = ref(false);
const mediaInput = ref(null);
const selectedMediaNames = ref([]);

let map = null;
let marker = null;

const DEFAULT_CENTER = [0.3476, 32.5825];
const SUPPORTED_CURRENCIES = ['UGX', 'USD'];
const MAX_MEDIA_SIZE_MB = 100;
const MAX_MEDIA_SIZE_BYTES = MAX_MEDIA_SIZE_MB * 1024 * 1024;
const SUPPORTED_MEDIA_MIME_TYPES = new Set([
	'image/jpeg',
	'image/png',
	'image/webp',
	'video/mp4',
	'video/webm',
	'video/quicktime',
	'video/x-m4v',
	'video/m4v',
]);
const SUPPORTED_MEDIA_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov', 'm4v']);

const selectedMediaLabel = computed(() => {
	if (!selectedMediaNames.value.length) {
		return t('propertyForm.mediaNoFileChosen');
	}

	return selectedMediaNames.value.join(', ');
});

const form = ref({
	title: '',
	description: '',
	district: '',
	city: '',
	address: '',
	price: 0,
	priceCurrency: 'UGX',
	listingType: '',
	bedrooms: 1,
	bathrooms: 1,
	propertyType: '',
	latitude: '',
	longitude: '',
	imageUrl: '',
	mediaPaths: [],
});

const cityOptions = computed(() => listCitiesByDistrict(form.value.district));

function mapPickerIcon() {
	return L.divIcon({
		className: 'property-form-pin',
		html: '<span class="property-form-pin__body" aria-hidden="true"><span class="property-form-pin__core"></span></span>',
		iconSize: [26, 36],
		iconAnchor: [13, 35],
		popupAnchor: [0, -30],
	});
}

function fallbackAddress(lat, lng) {
	return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
}

function validCoordinates(latitude, longitude) {
	return Number.isFinite(latitude) && Number.isFinite(longitude);
}

function parseCoordinate(value) {
	if (value === '' || value === null || value === undefined) {
		return null;
	}

	const parsed = Number(value);
	return Number.isFinite(parsed) ? parsed : null;
}

function normalizeValue(value) {
	return String(value ?? '').trim().toLowerCase();
}

function normalizeComparableText(value) {
	return String(value ?? '')
		.normalize('NFKD')
		.replace(/[\u0300-\u036f]/g, '')
		.toLowerCase()
		.replace(/[^a-z0-9\s]/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();
}

function tokenize(value) {
	return normalizeComparableText(value).split(' ').filter(Boolean);
}

function normalizeCurrency(value) {
	const normalized = String(value ?? '').trim().toUpperCase();
	return SUPPORTED_CURRENCIES.includes(normalized) ? normalized : 'UGX';
}

function scoreMatch(candidate, option) {
	const normalizedCandidate = normalizeComparableText(candidate);
	const normalizedOption = normalizeComparableText(option);

	if (!normalizedCandidate || !normalizedOption) {
		return 0;
	}

	if (normalizedCandidate === normalizedOption) {
		return 100;
	}

	if (normalizedCandidate.includes(normalizedOption) || normalizedOption.includes(normalizedCandidate)) {
		return 75;
	}

	const candidateTokens = tokenize(candidate);
	const optionTokens = tokenize(option);

	if (!candidateTokens.length || !optionTokens.length) {
		return 0;
	}

	const overlap = optionTokens.filter((token) => candidateTokens.includes(token)).length;
	if (!overlap) {
		return 0;
	}

	const coverage = overlap / optionTokens.length;
	if (coverage >= 1) {
		return 65;
	}

	if (coverage >= 0.5) {
		return 55;
	}

	return 0;
}

function firstMatchingOption(candidates, options) {
	let bestOption = '';
	let bestScore = 0;

	for (const candidate of candidates) {
		for (const option of options) {
			const score = scoreMatch(candidate, option);
			if (score > bestScore) {
				bestScore = score;
				bestOption = option;
			}

			if (score === 100) {
				return option;
			}
		}
	}

	return bestScore >= 55 ? bestOption : '';
}

function findDistrictByCity(city) {
	const normalizedCity = normalizeComparableText(city);

	for (const [district, cities] of Object.entries(citiesByDistrict.value ?? {})) {
		if (!Array.isArray(cities)) {
			continue;
		}

		const hasCity = cities.some((entry) => {
			const normalizedEntry = normalizeComparableText(entry);
			if (!normalizedEntry || !normalizedCity) {
				return false;
			}

			return normalizedEntry === normalizedCity || normalizedEntry.includes(normalizedCity) || normalizedCity.includes(normalizedEntry);
		});
		if (hasCity) {
			return district;
		}
	}

	return '';
}

function applyLocationFromGeocode(data) {
	const address = data?.address ?? {};

	const districtCandidates = [
		address.city_district,
		address.state_district,
		address.county,
		address.state,
		address.municipality,
	];

	const cityCandidates = [
		address.city,
		address.town,
		address.village,
		address.suburb,
		address.hamlet,
		address.neighbourhood,
		address.municipality,
	];

	const matchedCity = firstMatchingOption(cityCandidates, Object.values(citiesByDistrict.value ?? {}).flat());
	let matchedDistrict = firstMatchingOption(districtCandidates, districtOptions.value);

	if (!matchedDistrict && matchedCity) {
		matchedDistrict = findDistrictByCity(matchedCity);
	}

	if (matchedDistrict) {
		form.value.district = matchedDistrict;
	} else {
		// Prevent stale district selection when geocode has no known district match.
		form.value.district = '';
	}

	const districtCityOptions = matchedDistrict
		? (citiesByDistrict.value?.[matchedDistrict] ?? [])
		: [];

	const cityFromDistrict = firstMatchingOption(cityCandidates, districtCityOptions);
	if (cityFromDistrict) {
		form.value.city = cityFromDistrict;
	} else if (!form.value.city && matchedCity) {
		form.value.city = matchedCity;
	} else if (!matchedCity) {
		// Prevent stale city selection when geocode has no known city match.
		form.value.city = '';
	}
}

function moveMarker(latitude, longitude) {
	if (!map || !validCoordinates(latitude, longitude)) {
		return;
	}

	if (!marker) {
		marker = L.marker([latitude, longitude], { icon: mapPickerIcon() }).addTo(map);
	} else {
		marker.setLatLng([latitude, longitude]);
	}
}

async function reverseGeocode(latitude, longitude) {
	const fallback = fallbackAddress(latitude, longitude);

	try {
		const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`;
		const response = await fetch(url, {
			headers: {
				Accept: 'application/json',
			},
		});

		if (!response.ok) {
			form.value.address = fallback;
			return;
		}

		const data = await response.json();
		applyLocationFromGeocode(data);
		form.value.address = data?.display_name || fallback;
	} catch {
		form.value.address = fallback;
	}
}

function syncMapToCurrentCoordinates() {
	if (!map) {
		return;
	}

	const latitude = parseCoordinate(form.value.latitude);
	const longitude = parseCoordinate(form.value.longitude);

	if (!validCoordinates(latitude, longitude)) {
		map.setView(DEFAULT_CENTER, 7);
		if (marker) {
			map.removeLayer(marker);
			marker = null;
		}
		return;
	}

	moveMarker(latitude, longitude);
	map.setView([latitude, longitude], 13);
}

function initializeMap() {
	if (!mapHost.value) {
		return;
	}

	map = L.map(mapHost.value).setView(DEFAULT_CENTER, 7);

	L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
		maxZoom: 19,
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
	}).addTo(map);

	map.on('click', async (event) => {
		const latitude = Number(event.latlng.lat.toFixed(6));
		const longitude = Number(event.latlng.lng.toFixed(6));

		form.value.latitude = latitude;
		form.value.longitude = longitude;
		moveMarker(latitude, longitude);

		await reverseGeocode(latitude, longitude);
	});

	syncMapToCurrentCoordinates();

	requestAnimationFrame(() => {
		map?.invalidateSize();
	});
}

function inferMediaKindFromPath(path) {
	const extension = String(path ?? '').split('.').pop()?.toLowerCase() ?? '';
	return ['mp4', 'webm', 'mov', 'm4v'].includes(extension) ? 'video' : 'image';
}

function inferMediaKindFromFile(file) {
	return file?.type?.startsWith('video/') ? 'video' : 'image';
}

function getFileExtension(fileName) {
	const extension = String(fileName ?? '').split('.').pop()?.toLowerCase();
	return extension || '';
}

function isSupportedMediaFile(file) {
	const mimeType = String(file?.type ?? '').toLowerCase();
	if (mimeType && SUPPORTED_MEDIA_MIME_TYPES.has(mimeType)) {
		return true;
	}

	const extension = getFileExtension(file?.name);
	return SUPPORTED_MEDIA_EXTENSIONS.has(extension);
}

function validateMediaFile(file) {
	if (!isSupportedMediaFile(file)) {
		return t('propertyForm.mediaUploadValidationType', { fileName: file?.name || '' });
	}

	if (Number(file?.size ?? 0) > MAX_MEDIA_SIZE_BYTES) {
		return t('propertyForm.mediaUploadValidationSize', {
			fileName: file?.name || '',
			maxSizeMb: MAX_MEDIA_SIZE_MB,
		});
	}

	return '';
}

function syncLegacyImageUrl() {
	form.value.imageUrl = form.value.mediaPaths[0] ?? '';
}

function syncMediaPathsFromItems() {
	form.value.mediaPaths = mediaItems.value.map((item) => item.path);
	syncLegacyImageUrl();
}

function openMediaPicker() {
	mediaInput.value?.click();
}

async function onMediaSelected(event) {
	const files = Array.from(event?.target?.files ?? []);

	if (!files.length) {
		selectedMediaNames.value = [];
		return;
	}

	selectedMediaNames.value = files.map((file) => String(file?.name ?? '')).filter(Boolean);

	mediaUploadError.value = '';

	const firstValidationError = files.map((file) => validateMediaFile(file)).find(Boolean);
	if (firstValidationError) {
		mediaUploadError.value = firstValidationError;
		if (event?.target) {
			event.target.value = '';
		}
		return;
	}

	isUploadingMedia.value = true;

	try {
		for (const file of files) {
			try {
				const uploadedPath = await uploadPropertyMedia(file);
				if (!uploadedPath) {
					continue;
				}

				mediaItems.value.push({
					path: uploadedPath,
					kind: inferMediaKindFromFile(file),
				});
			} catch (error) {
				const status = error?.response?.status;
				const apiMessage = extractApiErrorMessage(error);
				if (status === 413) {
					mediaUploadError.value = t('propertyForm.mediaUploadFailedTooLarge');
				} else if (!error?.response) {
					mediaUploadError.value = t('propertyForm.mediaUploadFailedServerLimit');
				} else if (apiMessage) {
					mediaUploadError.value = apiMessage;
				} else {
					mediaUploadError.value = t('propertyForm.mediaUploadFailed');
				}
			}
		}

		syncMediaPathsFromItems();
	} finally {
		isUploadingMedia.value = false;
		if (event?.target) {
			event.target.value = '';
		}
	}
}

function removeMedia(index) {
	mediaItems.value.splice(index, 1);
	syncMediaPathsFromItems();
}

async function load() {
	const profile = await getProfile();
	if (!isBuyerProfile(profile)) {
		try {
			showSubscriptionBlock.value = !(await hasActiveSellerSubscription());
		} catch {
			showSubscriptionBlock.value = true;
		}
	}

	const locations = await listLocations();
	districtOptions.value = locations.districts ?? [];
	citiesByDistrict.value = locations.citiesByDistrict ?? {};
	propertyTypeOptions.value = locations.propertyTypes?.length ? locations.propertyTypes : propertyTypeOptions.value;

	if (isEdit.value) {
		const found = await getProperty(route.params.id);
		if (found) {
			const normalizedMedia = Array.isArray(found.media) && found.media.length
				? found.media.map((item) => ({
					path: item.path,
					kind: item.kind || inferMediaKindFromPath(item.path),
				}))
				: found.imageUrl
					? [{ path: found.imageUrl, kind: inferMediaKindFromPath(found.imageUrl) }]
					: [];

			form.value = {
				...form.value,
				...found,
				priceCurrency: normalizeCurrency(found.priceCurrency ?? found.price_currency ?? found.currency),
				mediaPaths: normalizedMedia.map((item) => item.path),
			};
			mediaItems.value = normalizedMedia;
			syncLegacyImageUrl();
		}
	}

	await nextTick();

	if (!map) {
		initializeMap();
	}

	syncMapToCurrentCoordinates();

	if (!form.value.address) {
		if (form.value.city && form.value.district) {
			form.value.address = `${form.value.city}, ${form.value.district}`;
		} else if (isEdit.value) {
			const latitude = parseCoordinate(form.value.latitude);
			const longitude = parseCoordinate(form.value.longitude);
			if (validCoordinates(latitude, longitude)) {
				form.value.address = fallbackAddress(latitude, longitude);
			}
		}
	}
}

async function save() {
	if (showSubscriptionBlock.value) {
		router.push({ name: 'seller-onboarding', query: { redirect: route.fullPath } });
		return;
	}

	if (isEdit.value) {
		const updated = await updateProperty(route.params.id, form.value);
		if (updated?.publishFeePaymentRequired && updated?.publishFeeCheckoutUrl) {
			window.location.assign(updated.publishFeeCheckoutUrl);
			return;
		}
		router.push({ name: 'property-detail', params: { id: route.params.id } });
		return;
	}

	const created = await createProperty(form.value);
	if (created?.publishFeePaymentRequired && created?.publishFeeCheckoutUrl) {
		window.location.assign(created.publishFeeCheckoutUrl);
		return;
	}
	router.push({ name: 'home', query: { owned: '1', created: '1' } });
}

watch(
	() => form.value.district,
	(newDistrict) => {
		if (!newDistrict) {
			form.value.city = '';
			return;
		}

		if (!cityOptions.value.includes(form.value.city)) {
			form.value.city = '';
		}
	},
);

watch(
	() => [form.value.latitude, form.value.longitude],
	([latitude, longitude]) => {
		const parsedLatitude = parseCoordinate(latitude);
		const parsedLongitude = parseCoordinate(longitude);

		if (!validCoordinates(parsedLatitude, parsedLongitude)) {
			if (marker && map) {
				map.removeLayer(marker);
				marker = null;
			}
			return;
		}

		moveMarker(parsedLatitude, parsedLongitude);
	},
);

onMounted(load);

onBeforeUnmount(() => {
	if (map) {
		map.remove();
		map = null;
	}
});
</script>

<style scoped>
:deep(.property-form-pin) {
	background: transparent;
	border: 0;
	position: relative;
}

:deep(.property-form-pin__body) {
	position: relative;
	display: block;
	width: 24px;
	height: 24px;
	border-radius: 9999px 9999px 9999px 4px;
	transform: rotate(-45deg);
	background: linear-gradient(160deg, #0ea5e9 0%, #0284c7 100%);
	border: 2px solid #ffffff;
	box-shadow: 0 8px 16px rgba(2, 132, 199, 0.28);
}

:deep(.property-form-pin__core) {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 8px;
	height: 8px;
	border-radius: 9999px;
	background: #ffffff;
	transform: translate(-50%, -50%) rotate(45deg);
}
</style>
