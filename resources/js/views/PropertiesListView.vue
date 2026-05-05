<template>
	<section class="space-y-4">
		<div class="flex items-center justify-between">
			<h2 class="text-xl font-semibold text-slate-900">{{ $t('nav.properties') }}</h2>
			<RouterLink class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :to="{ name: 'property-create' }">
				{{ $t('actions.createListing') }}
			</RouterLink>
		</div>

		<div class="rounded-lg border border-slate-200 bg-white p-4">
			<PropertyMap :markers="properties" />
		</div>

		<div v-if="loading" class="grid gap-4 md:grid-cols-2">
			<div v-for="n in 4" :key="n" class="animate-pulse rounded-lg border border-slate-200 bg-white p-4">
				<div class="mb-3 h-40 rounded bg-slate-200"></div>
				<div class="mb-2 h-4 w-3/4 rounded bg-slate-200"></div>
				<div class="h-4 w-1/2 rounded bg-slate-200"></div>
			</div>
		</div>

		<div v-else-if="!properties.length" class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
			{{ $t('properties.empty') }}
		</div>

		<div v-else class="grid gap-4 md:grid-cols-2">
			<article v-for="item in properties" :key="item.id" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
				<img :src="item.imageUrl" :alt="item.title" loading="lazy" class="h-40 w-full object-cover" />
				<div class="space-y-2 p-4">
					<h3 class="text-lg font-medium text-slate-900">{{ item.title }}</h3>
					<p class="text-sm text-slate-600">{{ item.district }} - {{ item.city }}</p>
					<p class="text-sm text-slate-500">{{ item.propertyType }} · {{ item.bedrooms }} {{ $t('filters.bedrooms') }}</p>
					<p class="font-semibold text-emerald-700">{{ formatUgx(item.price) }}</p>
					<RouterLink :to="{ name: 'property-detail', params: { id: item.id } }" class="inline-block text-sm text-sky-700">
						{{ $t('actions.viewDetails') }}
					</RouterLink>
				</div>
			</article>
		</div>
	</section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import PropertyMap from '../components/PropertyMap.vue';
import { listProperties } from '../services/properties';
import { formatUgx } from '../utils/formatters';

const route = useRoute();
const loading = ref(true);
const properties = ref([]);

async function load() {
	loading.value = true;
	properties.value = await listProperties(route.query);
	loading.value = false;
}

onMounted(load);
watch(() => route.query, load);
</script>
