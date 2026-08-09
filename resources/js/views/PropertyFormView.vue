<template>
	<section class="rounded-lg border border-slate-200 bg-white p-5">
		<h2 class="mb-4 text-xl font-semibold text-slate-900">{{ isEdit ? $t('actions.editListing') : $t('actions.createListing') }}</h2>



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
						<div class="relative">
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
								class="absolute right-2 top-1 rounded bg-white/80 px-1 py-0.5 text-xs hover:bg-white"
								@click.stop="moveMediaUp(index)"
								:disabled="index === 0"
							>
								▲
							</button>
							<button
								type="button"
								class="absolute right-2 top-6 rounded bg-white/80 px-1 py-0.5 text-xs hover:bg-white"
								@click.stop="moveMediaDown(index)"
								:disabled="index === mediaItems.length - 1"
							>
								▼
							</button>
						</div>
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
			<div class="md:col-span-2 rounded border border-slate-200 p-3">
				<p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $t('propertyForm.mapPickerLabel') }}</p>
				<p class="mt-1 text-xs text-slate-500">{{ $t('propertyForm.mapPickerHint') }}</p>
				<div ref="mapHost" class="mt-3 h-64 w-full rounded border border-slate-200 relative"></div>
				<p v-if="form.latitude && form.longitude" class="mt-2 text-xs text-slate-500">
					{{ $t('propertyForm.latitudeLabel') }}: {{ form.latitude }}, {{ $t('propertyForm.longitudeLabel') }}: {{ form.longitude }}
				</p>
			</div>

			<div class="md:col-span-2">
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-address">{{ $t('propertyForm.addressLabel') }}</label>
				<div class="flex gap-2">
					<input id="property-address" v-model="form.address" class="flex-1 rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.addressPlaceholder')" required />
					<button
						type="button"
						class="rounded bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
						@click="triggerGeocode"
						:disabled="!form.address || form.address.length < 3"
					>
						{{ $t('propertyForm.searchLocation') }}
					</button>
				</div>
				<p class="mt-1 text-xs text-slate-500">{{ $t('propertyForm.addressHint') }}</p>
			</div>

			<div class="md:col-span-2">
				<label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600" for="property-description">{{ $t('propertyForm.description') }}</label>
				<textarea id="property-description" v-model="form.description" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" rows="4" :placeholder="$t('propertyForm.description')" required></textarea>
			</div>
			<div class="md:col-span-2">
				<label class="flex items-center gap-2">
					<input type="checkbox" v-model="form.isVisible" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-0" />
					<span class="text-sm text-slate-700">{{ $t('propertyForm.visibleOnPlatform') }}</span>
				</label>
			</div>
			<div class="md:col-span-2">
				<button class="rounded bg-slate-900 px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:bg-slate-500" type="submit" :disabled="isUploadingMedia">{{ $t('actions.save') }}</button>
				<button v-if="isEdit" type="button" class="ml-2 rounded bg-rose-600 px-4 py-2 text-sm text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-400" :disabled="isUploadingMedia" @click="confirmDelete">{{ $t('actions.delete') }}</button>
			</div>
		</form>
	</section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import L from 'leaflet';
import { getProfile, isBuyerProfile } from '../services/authProfile';
import { createProperty, deleteProperty, extractApiErrorMessage, getProperty, updateProperty, uploadPropertyMedia } from '../services/properties';
import { listLocations } from '../services/locations';
import { usePageMeta } from '../composables/usePageMeta';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const isEdit = computed(() => Boolean(route.params.id));

usePageMeta(() => ({ title: isEdit.value ? 'Edit Listing' : 'Create Listing', robots: 'noindex,nofollow' }));
const mapHost = ref(null);
const propertyTypeOptions = ref(['apartment', 'house', 'land', 'commercial']);
const allCities = ref([]);
const citiesByDistrict = ref({});
const districtOptions = ref([]);
const isUploadingMedia = ref(false);
const mediaItems = ref([]);
const mediaUploadError = ref('');

const mediaInput = ref(null);
const selectedMediaNames = ref([]);

let map = null;
let marker = null;
let resizeObserver = null;
let handleWindowResize = null;

const DEFAULT_CENTER = [0.6, 32.5825];
const SUPPORTED_CURRENCIES = ['UGX', 'USD'];
const MAX_MEDIA_SIZE_MB = 2000;
const MAX_MEDIA_SIZE_BYTES = MAX_MEDIA_SIZE_MB * 1024 * 1024;

let isUpdatingFromMap = false;
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
	isVisible: true,
});

function mapPickerIcon() {
	return L.divIcon({
		className: 'property-form-pin',
		html: '<span class="property-form-pin__body" aria-hidden="true"><span class="property-form-pin__core"></span></span>',
		iconSize: [26, 36],
		iconAnchor: [13, 30],
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
	if (value === '' || value === null || value === undefined) return null;
	const parsed = Number(value);
	return Number.isFinite(parsed) ? parsed : null;
}

function normalizeValue(value) {
	return String(value ?? '').trim().toLowerCase();
}

function normalizeCurrency(value) {
	const normalized = String(value ?? '').trim().toUpperCase();
	return SUPPORTED_CURRENCIES.includes(normalized) ? normalized : 'UGX';
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

function scoreMatch(candidate, option) {
	const normalizedCandidate = normalizeComparableText(candidate);
	const normalizedOption = normalizeComparableText(option);
	if (!normalizedCandidate || !normalizedOption) return 0;
	if (normalizedCandidate === normalizedOption) return 100;
	if (normalizedCandidate.includes(normalizedOption) || normalizedOption.includes(normalizedCandidate)) return 75;
	const candidateTokens = tokenize(candidate);
	const optionTokens = tokenize(option);
	if (!candidateTokens.length || !optionTokens.length) return 0;
	const overlap = optionTokens.filter((token) => candidateTokens.includes(token)).length;
	if (!overlap) return 0;
	const coverage = overlap / optionTokens.length;
	if (coverage >= 1) return 65;
	if (coverage >= 0.5) return 55;
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
			if (score === 100) return option;
		}
	}
	return bestScore >= 55 ? bestOption : '';
}

function findBestMatch(value, options) {
	if (!value || !options?.length) return '';
	const exactMatch = options.find(opt => String(opt).trim() === String(value).trim());
	if (exactMatch) return exactMatch;
	const normalizedValue = String(value).trim().toLowerCase();
	const caseInsensitiveMatch = options.find(opt => String(opt).trim().toLowerCase() === normalizedValue);
	if (caseInsensitiveMatch) return caseInsensitiveMatch;
	const containsMatch = options.find(opt =>
		String(opt).trim().toLowerCase().includes(normalizedValue) ||
		normalizedValue.includes(String(opt).trim().toLowerCase())
	);
	if (containsMatch) return containsMatch;
	const scoredMatch = firstMatchingOption([value], options);
	if (scoredMatch) return scoredMatch;
	return options[0] || '';
}

function findDistrictByCity(city) {
	const normalizedCity = normalizeComparableText(city);
	for (const [district, cities] of Object.entries(citiesByDistrict.value ?? {})) {
		if (!Array.isArray(cities)) continue;
		const hasCity = cities.some((entry) => {
			const normalizedEntry = normalizeComparableText(entry);
			if (!normalizedEntry || !normalizedCity) return false;
			return normalizedEntry === normalizedCity || normalizedEntry.includes(normalizedCity) || normalizedCity.includes(normalizedEntry);
		});
		if (hasCity) return district;
	}
	return '';
}

function applyLocationFromGeocode(data) {
	if (!data?.address) return;
	const address = data.address;
	const districtCandidates = [address.city_district, address.state_district, address.county, address.state, address.municipality];
	const cityCandidates = [address.city, address.town, address.village, address.suburb, address.hamlet, address.neighbourhood, address.municipality];

	let matchedDistrict = findBestMatch(districtCandidates.filter(Boolean).join(' '), districtOptions.value) || '';
	let matchedCity = findBestMatch(cityCandidates.filter(Boolean).join(' '), allCities.value) || '';

	if (!matchedDistrict && matchedCity) {
		matchedDistrict = findDistrictByCity(matchedCity);
	}

	// Fallback: if no matches found, use raw values from geocode data
	const fallbackDistrict = districtCandidates.find(Boolean) || cityCandidates.find(Boolean) || matchedDistrict || '';
	const fallbackCity = cityCandidates.find(Boolean) || matchedCity || '';

	form.value.district = matchedDistrict || fallbackDistrict || districtOptions.value[0] || '';
	form.value.city = matchedCity || fallbackCity || allCities.value[0] || '';
}

function moveMarker(latitude, longitude) {
	if (!map || !validCoordinates(latitude, longitude)) return;
	if (!marker) {
		marker = L.marker([latitude, longitude], { icon: mapPickerIcon() }).addTo(map);
	} else {
		marker.setLatLng([latitude, longitude]);
	}
}

async function forwardGeocode(query) {
	if (!query || !map) return;
	try {
		const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&countrycodes=ug&limit=1`;
		const response = await fetch(url, { headers: { Accept: 'application/json' } });
		if (!response.ok) return;
		const data = await response.json();
		const result = data?.[0];
		if (result && validCoordinates(Number(result.lat), Number(result.lon))) {
			const latitude = Number(result.lat);
			const longitude = Number(result.lon);
			isUpdatingFromMap = true;
			form.value.latitude = latitude;
			form.value.longitude = longitude;
			form.value.address = result.display_name;
			moveMarker(latitude, longitude);
			map.setView([latitude, longitude], 15);
			applyLocationFromGeocode({ address: result });
			await nextTick();
			isUpdatingFromMap = false;
		}
	} catch {
		isUpdatingFromMap = false;
	}
}

function isCoordinateString(input) {
	// Match patterns like: "lat, lng", "lat,lng", "lat  ,  lng", "-0.32, 32.58"
	const coordinatePattern = /^\s*(-?\d+\.?\d*)\s*[,\s]+\s*(-?\d+\.?\d*)\s*$/;
	return coordinatePattern.test(input);
}

function parseCoordinateString(input) {
	const cleaned = input.replace(/[\s,]+/g, ' ').trim();
	const parts = cleaned.split(/\s+/);
	if (parts.length === 2) {
		const lat = parseFloat(parts[0]);
		const lng = parseFloat(parts[1]);
		return { latitude: lat, longitude: lng };
	}
	return null;
}

async function triggerGeocode() {
	if (!form.value.address || form.value.address.length < 3) return;

	const trimmedAddress = form.value.address.trim();

	// Check if input looks like coordinates
	if (isCoordinateString(trimmedAddress)) {
		const coords = parseCoordinateString(trimmedAddress);
		if (coords && validCoordinates(coords.latitude, coords.longitude)) {
			isUpdatingFromMap = true;
			form.value.latitude = coords.latitude;
			form.value.longitude = coords.longitude;
			moveMarker(coords.latitude, coords.longitude);
			map.setView([coords.latitude, coords.longitude], 15);
			await reverseGeocode(coords.latitude, coords.longitude);
			isUpdatingFromMap = false;
			return;
		}
	}

	// Otherwise treat as address and do forward geocode
	await forwardGeocode(trimmedAddress);
}

async function reverseGeocodeFromCoords(latitude, longitude) {
	if (!validCoordinates(latitude, longitude)) return;
	const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`;
	try {
		const response = await fetch(url, { headers: { Accept: 'application/json' } });
		if (!response.ok) return null;
		return await response.json();
	} catch {
		return null;
	}
}

async function reverseGeocode(latitude, longitude) {
	const fallback = fallbackAddress(latitude, longitude);
	const data = await reverseGeocodeFromCoords(latitude, longitude);
	if (!data) {
		form.value.address = fallback;
		return;
	}
	isUpdatingFromMap = true;
	form.value.address = data?.display_name || fallback;
	applyLocationFromGeocode(data);
	await nextTick();
	isUpdatingFromMap = false;
}

function syncMapToCurrentCoordinates() {
	if (!map) return;
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
	if (!mapHost.value) return;
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
	requestAnimationFrame(() => { map?.invalidateSize(); });

	if (window.ResizeObserver) {
		resizeObserver = new ResizeObserver(() => { if (map) map.invalidateSize(); });
		resizeObserver.observe(mapHost.value);
	}
	handleWindowResize = () => { if (map) map.invalidateSize(); };
	window.addEventListener('resize', handleWindowResize);
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
	if (mimeType && SUPPORTED_MEDIA_MIME_TYPES.has(mimeType)) return true;
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

const MAX_IMAGE_WIDTH = 1920;
const MAX_IMAGE_HEIGHT = 1080;
const IMAGE_QUALITY = 0.85;

/**
 * Compress image file using browser Canvas API before upload
 * Returns a Promise that resolves to the file (compressed or original)
 */
async function compressImage(file) {
	// For now, skip client-side compression to avoid potential issues
	// Server-side compression via MediaOptimizer will still be applied
	return file;
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
		if (event?.target) { event.target.value = ''; }
		return;
	}
	isUploadingMedia.value = true;
	try {
		for (const file of files) {
			try {
				const fileToUpload = await compressImage(file);
				const uploadedPath = await uploadPropertyMedia(fileToUpload);
				if (!uploadedPath) continue;
				mediaItems.value.push({ path: uploadedPath, kind: inferMediaKindFromFile(fileToUpload) });
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
		if (event?.target) { event.target.value = ''; }
	}
}

function removeMedia(index) {
	mediaItems.value.splice(index, 1);
	syncMediaPathsFromItems();
}

function moveMediaUp(index) {
	if (index > 0) {
		const items = [...mediaItems.value];
		[items[index], items[index - 1]] = [items[index - 1], items[index]];
		mediaItems.value = items;
		syncMediaPathsFromItems();
	}
}

function moveMediaDown(index) {
	if (index < mediaItems.value.length - 1) {
		const items = [...mediaItems.value];
		[items[index], items[index + 1]] = [items[index + 1], items[index]];
		mediaItems.value = items;
		syncMediaPathsFromItems();
	}
}

async function load() {
	const profile = await getProfile();

	const locations = await listLocations();
	propertyTypeOptions.value = locations.propertyTypes?.length ? locations.propertyTypes : propertyTypeOptions.value;
	districtOptions.value = locations.districts ?? [];
	allCities.value = locations.allCities ?? [];
	citiesByDistrict.value = locations.citiesByDistrict ?? {};

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
				propertyType: String(found.propertyType ?? found.property_type ?? '').trim().toLowerCase(),
				listingType: String(found.listingType ?? found.listing_type ?? '').trim().toLowerCase(),
				isVisible: found.isVisible ?? true,
				mediaPaths: normalizedMedia.map((item) => item.path),
			};
			mediaItems.value = normalizedMedia;
			syncLegacyImageUrl();

			await nextTick();
			if (!map) { initializeMap(); }
			syncMapToCurrentCoordinates();

			// Ensure district and city are populated from coordinates
			if (!form.value.district || !form.value.city) {
				const lat = parseCoordinate(form.value.latitude);
				const lng = parseCoordinate(form.value.longitude);
				if (validCoordinates(lat, lng)) {
					const data = await reverseGeocodeFromCoords(lat, lng);
					if (data) {
						applyLocationFromGeocode(data);
					}
				}
			}
		}
	} else {
		await nextTick();
		if (!map) { initializeMap(); }
		syncMapToCurrentCoordinates();
	}

	if (!form.value.address) {
		if (isEdit.value) {
			const latitude = parseCoordinate(form.value.latitude);
			const longitude = parseCoordinate(form.value.longitude);
			if (validCoordinates(latitude, longitude)) {
				form.value.address = fallbackAddress(latitude, longitude);
			}
		}
	}
}

async function save() {

	// Ensure district and city are set from coordinates before saving
	if (!form.value.district || !form.value.city) {
		const lat = parseCoordinate(form.value.latitude);
		const lng = parseCoordinate(form.value.longitude);
		if (validCoordinates(lat, lng)) {
			const data = await reverseGeocodeFromCoords(lat, lng);
			if (data) {
				applyLocationFromGeocode(data);
				await nextTick();
			}
		}
	}

	// Fallback: if still no district/city, use first available
	if (!form.value.district && districtOptions.value.length > 0) {
		form.value.district = districtOptions.value[0];
	}
	if (!form.value.city && allCities.value.length > 0) {
		form.value.city = allCities.value[0];
	}

	if (isEdit.value) {
		const updated = await updateProperty(route.params.id, form.value);
		router.push({ name: 'property-detail', params: { id: route.params.id } });
		return;
	}

	const created = await createProperty(form.value);
	router.push({ name: 'home', query: { owned: '1', created: '1' } });
}

function confirmDelete() {
	if (confirm(t('actions.deleteConfirm'))) { handleDelete(); }
}

async function handleDelete() {
	try {
		const propertyId = route.params.id;
		await deleteProperty(propertyId);
		router.push({ name: 'home', query: { owned: '1', deleted: '1' } });
	} catch (error) {
		const message = extractApiErrorMessage(error) || t('propertyForm.deleteError');
		alert(message);
	}
}

watch(
	() => [form.value.latitude, form.value.longitude],
	([latitude, longitude]) => {
		const parsedLatitude = parseCoordinate(latitude);
		const parsedLongitude = parseCoordinate(longitude);
		if (!validCoordinates(parsedLatitude, parsedLongitude)) {
			if (marker && map) { map.removeLayer(marker); marker = null; }
			return;
		}
		moveMarker(parsedLatitude, parsedLongitude);
	},
);

// Geocode watcher disabled - geocoding now only triggers on button press
// watch(
// 	() => form.value.address,
// 	(newAddress) => {
// 		if (isUpdatingFromMap) return;
// 		if (geocodeTimeout) clearTimeout(geocodeTimeout);
// 		geocodeTimeout = setTimeout(() => {
// 			if (newAddress && newAddress.length >= 3) {
// 				forwardGeocode(newAddress);
// 			}
// 		}, GEOCODE_DEBOUNCE_MS);
// 	}
// );

onMounted(load);

onBeforeUnmount(() => {
	if (map) { map.remove(); map = null; }
	if (resizeObserver) { resizeObserver.disconnect(); resizeObserver = null; }
	if (handleWindowResize) { window.removeEventListener('resize', handleWindowResize); handleWindowResize = null; }
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