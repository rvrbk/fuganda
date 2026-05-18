<template>
	<div ref="mapHost" :class="mapClasses"></div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';

const props = defineProps({
	markers: {
		type: Array,
		default: () => [],
	},
	selectedMarkerId: {
		type: [String, Number],
		default: null,
	},
	center: {
		type: Array,
		default: () => [0.3476, 32.5825],
	},
	zoom: {
		type: Number,
		default: 12,
	},
	fullViewport: {
		type: Boolean,
		default: false,
	},
});

const emit = defineEmits(['marker-selected']);

const mapHost = ref(null);
let map = null;
let layerGroup = null;

const mapClasses = computed(() => {
	if (props.fullViewport) {
		return 'h-screen w-full';
	}

	return 'h-72 w-full rounded-lg border border-slate-200';
});

function escapeHtml(value) {
	return String(value ?? '')
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#39;');
}

function formatPrice(value, currency = 'UGX') {
	const amount = Number(value ?? 0);
	const safeAmount = Number.isFinite(amount) ? amount : 0;
	const normalizedCurrency = String(currency ?? 'UGX').trim().toUpperCase() || 'UGX';

	return `${normalizedCurrency} ${new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(safeAmount)}`;
}

function markerIcon(isSelected) {
	const stateClass = isSelected ? 'property-pin--selected' : '';

	return L.divIcon({
		className: `property-pin ${stateClass}`.trim(),
		html: '<span class="property-pin__body" aria-hidden="true"><span class="property-pin__core"></span></span>',
		iconSize: [26, 36],
		iconAnchor: [13, 35],
		popupAnchor: [0, -30],
	});
}

function buildPopupContent(item) {
	const title = escapeHtml(item?.title || 'Property');
	const place = escapeHtml(`${item?.district ?? ''}${item?.district && item?.city ? ' - ' : ''}${item?.city ?? ''}`.trim());
	const detailsHref = `/properties/${encodeURIComponent(item?.id ?? '')}`;
	const price = escapeHtml(formatPrice(item?.price, item?.priceCurrency));

	return `
		<div class="property-popup-card">
			<p class="property-popup-card__title">${title}</p>
			${place ? `<p class="property-popup-card__meta">${place}</p>` : ''}
			<p class="property-popup-card__price">${price}</p>
			<a href="${detailsHref}" class="property-popup-card__link">View details</a>
		</div>
	`;
}

function drawMarkers() {
	if (!map || !layerGroup) {
		return;
	}

	layerGroup.clearLayers();

	if (!props.markers.length) {
		map.setView(props.center, props.zoom);
		return;
	}

	const bounds = [];

	props.markers.forEach((item) => {
		if (item.latitude == null || item.longitude == null) {
			return;
		}

		const isSelected = props.selectedMarkerId != null && String(item.id) === String(props.selectedMarkerId);

		const marker = L.marker([item.latitude, item.longitude], {
			icon: markerIcon(isSelected),
		});

		marker.bindPopup(buildPopupContent(item), {
			className: 'property-map-popup',
		});

		marker.on('click', () => {
			emit('marker-selected', item.id);
		});

		marker.addTo(layerGroup);
		bounds.push([item.latitude, item.longitude]);

		if (isSelected) {
			marker.openPopup();
		}
	});

	if (bounds.length === 1) {
		map.setView(bounds[0], props.zoom);
		return;
	}

	if (bounds.length > 1) {
		map.fitBounds(bounds, { padding: [24, 24] });
	}
}

onMounted(() => {
	if (!mapHost.value) {
		return;
	}

	map = L.map(mapHost.value);

	L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
		maxZoom: 19,
		attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
	}).addTo(map);

	layerGroup = L.layerGroup().addTo(map);
	drawMarkers();

	// Leaflet needs a size recalculation when the map container is viewport-sized.
	requestAnimationFrame(() => {
		map?.invalidateSize();
	});
});

watch(
	() => [props.markers, props.selectedMarkerId],
	() => {
		drawMarkers();
	},
	{ deep: true },
);

onBeforeUnmount(() => {
	if (map) {
		map.remove();
		map = null;
	}
});
</script>

<style scoped>
:deep(.property-pin) {
	background: transparent;
	border: 0;
	position: relative;
}

:deep(.property-pin__body) {
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


:deep(.property-pin__core) {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 8px;
	height: 8px;
	border-radius: 9999px;
	background: #ffffff;
	transform: translate(-50%, -50%) rotate(45deg);
}

:deep(.property-pin--selected .property-pin__body) {
	background: linear-gradient(160deg, #2563eb 0%, #1d4ed8 100%);
	box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.22), 0 8px 18px rgba(29, 78, 216, 0.35);
}

:deep(.property-pin--selected .property-pin__core) {
	background: #e0e7ff;
}

:deep(.property-map-popup .leaflet-popup-content-wrapper) {
	border-radius: 12px;
	padding: 0;
	border: 1px solid #e2e8f0;
	box-shadow: 0 20px 35px rgba(15, 23, 42, 0.15);
}

:deep(.property-map-popup .leaflet-popup-content) {
	margin: 0;
	min-width: 180px;
}

:deep(.property-map-popup .leaflet-popup-tip) {
	background: #ffffff;
	border: 1px solid #e2e8f0;
	box-shadow: none;
}

:deep(.property-popup-card) {
	padding: 12px;
	background: #ffffff;
	font-family: inherit;
}

:deep(.property-popup-card__title) {
	margin: 0;
	font-size: 0.95rem;
	font-weight: 700;
	line-height: 1.3;
	color: #0f172a;
}

:deep(.property-popup-card__meta) {
	margin: 4px 0 0;
	font-size: 0.75rem;
	color: #475569;
}

:deep(.property-popup-card__price) {
	margin: 8px 0 0;
	font-size: 0.875rem;
	font-weight: 700;
	color: #047857;
}

:deep(.property-popup-card__link) {
	display: inline-block;
	margin-top: 8px;
	font-size: 0.75rem;
	font-weight: 600;
	color: #0369a1;
	text-decoration: none;
	border-bottom: 1px solid rgba(3, 105, 161, 0.35);
}

:deep(.property-popup-card__link:hover) {
	color: #075985;
	border-bottom-color: rgba(7, 89, 133, 0.5);
}
</style>
