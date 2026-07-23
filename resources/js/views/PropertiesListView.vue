<template>
	<div class="fixed inset-x-0 bottom-0 top-[var(--app-header-height,0px)] z-0">
		<PropertyMap
			:markers="properties"
			:selected-marker-id="selectedPropertyId"
			full-viewport
			class="h-full w-full"
			@marker-selected="handleMarkerSelected"
		/>

		<div
			class="properties-filters-pane absolute left-1/2 top-4 z-[1000] w-[calc(100%-1rem)] max-w-3xl -translate-x-1/2 rounded-lg border border-slate-200 bg-white/95 p-2.5 shadow-xl backdrop-blur sm:w-auto sm:p-4"
		>
			<div v-if="showCreatedSuccess" class="mb-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 sm:mb-3 sm:text-sm">
				<p>{{ $t('properties.placedSuccess') }}</p>
			</div>

			<div v-if="showSubscriptionBlock" class="mb-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 sm:mb-3 sm:text-sm">
				<p>{{ $t('sellerOnboarding.blockingCallout') }}</p>
				<RouterLink class="mt-1 inline-block font-semibold underline" :to="{ name: 'seller-onboarding', query: { redirect: '/properties/new' } }">
					{{ $t('sellerOnboarding.openOnboarding') }}
				</RouterLink>
			</div>

			<div class="flex h-10 items-center justify-between gap-3 sm:mb-3 sm:h-auto">
				<p class="truncate text-sm font-semibold text-slate-800">{{ $t('filters.search') }}</p>
				<div class="flex items-center gap-2">
					<button
						type="button"
						class="w-24 rounded border border-slate-300 px-2 py-1.5 text-center text-xs text-slate-700 sm:hidden"
						@click="mobileFiltersOpen = !mobileFiltersOpen"
					>
						{{ mobileFiltersOpen ? $t('filters.hide') : $t('filters.show') }}
					</button>
					<RouterLink
						v-if="canCreateListing"
						:to="showSubscriptionBlock ? { name: 'seller-onboarding', query: { redirect: '/properties/new' } } : { name: 'property-create' }"
						class="rounded bg-slate-900 px-3 py-1.5 text-xs text-white shadow hover:bg-slate-800"
						:class="showSubscriptionBlock ? 'bg-slate-500 hover:bg-slate-500' : ''"
					>
						{{ $t('actions.createListing') }}
					</RouterLink>
				</div>
			</div>

			<form
				class="gap-2 sm:grid sm:grid-cols-2 md:grid-cols-4"
				:class="mobileFiltersOpen
					? 'absolute left-0 right-0 top-full z-[1010] mt-2 grid rounded-lg border border-slate-200 bg-white p-3 shadow-xl sm:static sm:mt-0 sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none'
					: 'hidden sm:grid'"
				@submit.prevent="search"
			>
				<select v-model="filters.district" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
					<option value="">{{ $t('filters.district') }}</option>
					<option v-for="district in locations.districts" :key="district" :value="district">{{ district }}</option>
				</select>

				<select v-model="filters.city" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
					<option value="">{{ $t('filters.city') }}</option>
					<option v-for="city in cityOptions" :key="city" :value="city">{{ city }}</option>
				</select>

				<select v-model="filters.listingType" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
					<option value="">{{ $t('filters.listingType') }}</option>
					<option value="rent">Rent</option>
					<option value="sale">Sale</option>
				</select>

				<select v-model="filters.propertyType" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
					<option value="">{{ $t('filters.propertyType') }}</option>
					<option v-for="propertyType in locations.propertyTypes" :key="propertyType" :value="propertyType">{{ propertyType }}</option>
				</select>

				<input v-model.number="filters.minPrice" class="rounded border border-slate-300 px-2 py-1.5 text-sm" type="number" :placeholder="$t('filters.minPrice')" />
				<input v-model.number="filters.maxPrice" class="rounded border border-slate-300 px-2 py-1.5 text-sm" type="number" :placeholder="$t('filters.maxPrice')" />
				<input v-model.number="filters.bedrooms" class="rounded border border-slate-300 px-2 py-1.5 text-sm" type="number" min="0" :placeholder="$t('filters.bedrooms')" />

				<div class="flex gap-2 md:col-span-2">
					<button class="flex-1 rounded bg-sky-700 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-800" type="submit">
						{{ $t('filters.search') }}
					</button>
					<button class="flex-1 rounded border border-slate-300 bg-white px-4 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50" type="button" @click="clearFilters">
						{{ $t('filters.clear') }}
					</button>
				</div>
			</form>
		</div>

		<div class="absolute inset-x-0 bottom-0 z-[1000]">
			<div class="border-t border-slate-200 bg-white/95 px-3 pb-3 pt-2 shadow-2xl backdrop-blur sm:px-4 sm:pb-4">
				<div class="mb-2 flex items-center justify-between">
					<p class="text-xs font-semibold text-slate-700 sm:text-sm">{{ $t('properties.results', { n: properties.length }) }}</p>
				</div>

				<div class="min-h-[6.5rem]">
					<div v-if="loading" class="flex gap-2.5 overflow-x-auto pb-1 sm:gap-3">
						<div v-for="n in 4" :key="n" class="w-56 flex-shrink-0 animate-pulse rounded-lg border border-slate-200 bg-white p-2.5 sm:w-64 sm:p-3">
							<div class="mb-2 h-20 rounded bg-slate-200 sm:h-24"></div>
							<div class="mb-1 h-3 w-3/4 rounded bg-slate-200"></div>
							<div class="h-3 w-1/2 rounded bg-slate-200"></div>
						</div>
					</div>

					<div v-else-if="!properties.length" class="rounded-lg border border-dashed border-slate-300 bg-white p-4 text-center text-sm text-slate-500">
						{{ $t('properties.empty') }}
					</div>

					<div class="-mx-1 flex gap-2.5 overflow-x-auto px-1 py-1.5 sm:gap-3">
						<article
							v-for="item in properties"
							:key="item.id"
							:ref="(element) => setCardRef(item.id, element)"
							class="flex w-56 flex-shrink-0 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition-all duration-300 sm:w-64"
							:class="String(item.id) === String(selectedPropertyId) ? 'ring-2 ring-inset ring-sky-500' : ''"
						>
							<video
								v-if="getCardMediaKind(item) === 'video'"
								:src="getCardMediaPath(item)"
								class="h-20 w-full bg-slate-900 object-cover sm:h-24"
								muted
								playsinline
								preload="metadata"
							></video>
							<img
								v-else
								:src="getCardMediaPath(item)"
								:alt="item.title"
								loading="lazy"
								class="h-20 w-full object-cover sm:h-24"
							/>
							<div class="space-y-1 p-2.5 sm:p-3">
								<h3 class="truncate text-sm font-medium text-slate-900">{{ item.title }}</h3>
								<p class="truncate text-xs text-slate-600">{{ item.district }} - {{ item.city }}</p>
								<p class="truncate text-xs text-slate-500">{{ item.propertyType }} · {{ item.bedrooms }} {{ $t('filters.bedrooms') }}</p>
								<div class="flex items-center justify-between gap-2 pt-0.5">
									<p class="truncate text-sm font-semibold text-emerald-700">{{ formatPrice(item.price, item.priceCurrency) }}</p>
									<RouterLink :to="{ name: 'property-detail', params: { id: item.id } }" class="inline-block text-xs text-sky-700">
										{{ $t('actions.viewDetails') }}
									</RouterLink>
								</div>
							</div>
						</article>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PropertyMap from '../components/PropertyMap.vue';
import { canManageListings, getProfile, getUserRole } from '../services/authProfile';
import { hasActiveSellerSubscription } from '../services/sellerBilling';
import { listProperties } from '../services/properties';
import { listLocations, listCitiesByDistrict } from '../services/locations';
import { formatPrice } from '../utils/formatters';
import { usePageMeta } from '../composables/usePageMeta';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const properties = ref([]);

usePageMeta(() => ({
	title: 'Properties for Rent & Sale in Uganda',
	description: 'Browse apartments, houses, land and commercial properties across Uganda. Search by district, city, type and price.',
	jsonLd: properties.value.length ? {
		'@context': 'https://schema.org',
		'@type': 'ItemList',
		name: 'Properties for Rent & Sale in Uganda',
		url: window.location.origin + '/properties',
		numberOfItems: properties.value.length,
		itemListElement: properties.value.slice(0, 50).map((p, index) => ({
			'@type': 'ListItem',
			position: index + 1,
			url: `${window.location.origin}/properties/${p.id}`,
			name: p.title,
		})),
	} : null,
}));
const locations = ref({ districts: [], propertyTypes: [] });
const canCreateListing = ref(false);
const showSubscriptionBlock = ref(false);
const mobileFiltersOpen = ref(false);
const selectedPropertyId = ref(null);
const cardRefs = new Map();
const showCreatedSuccess = computed(() => route.query.created === '1');

function setCardRef(id, element) {
	if (!element) {
		cardRefs.delete(String(id));
		return;
	}

	cardRefs.set(String(id), element);
}

function getCardMediaKind(item) {
	const primaryMedia = Array.isArray(item?.media) && item.media.length > 0 ? item.media[0] : null;
	if (primaryMedia?.kind === 'video') {
		return 'video';
	}

	return 'image';
}

function getCardMediaPath(item) {
	const primaryMedia = Array.isArray(item?.media) && item.media.length > 0 ? item.media[0] : null;
	if (primaryMedia?.path) {
		return primaryMedia.path;
	}

	return item?.imageUrl || '';
}

async function handleMarkerSelected(id) {
	selectedPropertyId.value = id;

	await nextTick();

	const target = cardRefs.get(String(id));
	if (!target) {
		return;
	}

	target.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
}

function defaultFilters() {
	return {
		district: '',
		city: '',
		listingType: '',
		propertyType: '',
		minPrice: null,
		maxPrice: null,
		bedrooms: null,
		owned: null,
	};
}

function filtersFromQuery(query) {
	return {
		...defaultFilters(),
		district: query.district ?? '',
		city: query.city ?? '',
		listingType: query.listingType ?? '',
		propertyType: query.propertyType ?? '',
		minPrice: query.minPrice != null && query.minPrice !== '' ? Number(query.minPrice) : null,
		maxPrice: query.maxPrice != null && query.maxPrice !== '' ? Number(query.maxPrice) : null,
		bedrooms: query.bedrooms != null && query.bedrooms !== '' ? Number(query.bedrooms) : null,
		owned: query.owned === '1' || query.owned === 'true' ? 1 : null,
	};
}

const filters = ref(filtersFromQuery(route.query));

const cityOptions = computed(() => listCitiesByDistrict(filters.value.district));

function search() {
	const query = Object.fromEntries(
		Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && !Number.isNaN(value)),
	);
	router.replace({ name: 'home', query });
}

function clearFilters() {
	filters.value = {
		...defaultFilters(),
		owned: route.query.owned === '1' || route.query.owned === 'true' ? 1 : null,
	};

	const query = {};
	if (filters.value.owned) {
		query.owned = '1';
	}

	router.replace({ name: 'home', query });
}

async function load() {
	loading.value = true;
	properties.value = await listProperties(route.query);
	if (!properties.value.some((item) => String(item.id) === String(selectedPropertyId.value))) {
		selectedPropertyId.value = null;
	}
	loading.value = false;
}

onMounted(async () => {
	const profile = await getProfile();
	const role = getUserRole(profile);
	canCreateListing.value = Boolean(profile) && (role === 'seller' || role === 'admin');
	if (canCreateListing.value) {
		try {
			showSubscriptionBlock.value = !(await hasActiveSellerSubscription());
		} catch {
			showSubscriptionBlock.value = true;
		}
	}

	locations.value = await listLocations();
	await load();
});

watch(() => route.query, (query) => {
	filters.value = filtersFromQuery(query);
	load();
});
</script>

<style scoped>
@media (max-width: 639px) {
	.properties-filters-pane {
		max-width: calc(100% - 0.75rem);
	}
}

:deep(.leaflet-control-container .leaflet-top) {
	z-index: 1100;
}

@media (max-width: 639px) {
	:deep(.leaflet-control-zoom) {
		display: none;
	}

	:deep(.leaflet-control-container .leaflet-top.leaflet-left),
	:deep(.leaflet-control-container .leaflet-top.leaflet-right) {
		top: 4.25rem;
	}
}
</style>
