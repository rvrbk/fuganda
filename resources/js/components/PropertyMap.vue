<template>
	<div ref="mapHost" class="h-72 w-full rounded-lg border border-slate-200"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

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
});

const mapHost = ref(null);
let map = null;
let layerGroup = null;

L.Icon.Default.mergeOptions({
	iconRetinaUrl: markerIcon2x,
	iconUrl: markerIcon,
	shadowUrl: markerShadow,
});

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

		const marker = L.marker([item.latitude, item.longitude]);
		marker.bindPopup(item.title || 'Property');
		marker.addTo(layerGroup);
		bounds.push([item.latitude, item.longitude]);

		if (props.selectedMarkerId != null && String(item.id) === String(props.selectedMarkerId)) {
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

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 19,
		attribution: '&copy; OpenStreetMap contributors',
	}).addTo(map);

	layerGroup = L.layerGroup().addTo(map);
	drawMarkers();
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
