<template>
	<section class="rounded-lg border border-slate-200 bg-white p-5">
		<h2 class="mb-4 text-xl font-semibold text-slate-900">{{ isEdit ? $t('actions.editListing') : $t('actions.createListing') }}</h2>

		<form class="grid gap-3 md:grid-cols-2" @submit.prevent="save">
			<input v-model="form.title" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.title')" required />
			<input v-model="form.imageUrl" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('propertyForm.imageUrl')" />
			<input v-model="form.district" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('filters.district')" required />
			<input v-model="form.city" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('filters.city')" required />
			<select v-model="form.listingType" class="rounded border border-slate-300 px-3 py-2 text-sm" required>
				<option value="">{{ $t('filters.listingType') }}</option>
				<option value="rent">Rent</option>
				<option value="sale">Sale</option>
			</select>
			<select v-model="form.propertyType" class="rounded border border-slate-300 px-3 py-2 text-sm" required>
				<option value="">{{ $t('filters.propertyType') }}</option>
				<option value="apartment">Apartment</option>
				<option value="house">House</option>
				<option value="land">Land</option>
				<option value="commercial">Commercial</option>
			</select>
			<input v-model.number="form.price" type="number" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('filters.minPrice')" required />
			<input v-model.number="form.bedrooms" type="number" min="0" class="rounded border border-slate-300 px-3 py-2 text-sm" :placeholder="$t('filters.bedrooms')" required />
			<input v-model.number="form.latitude" type="number" step="0.0001" class="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Latitude" required />
			<input v-model.number="form.longitude" type="number" step="0.0001" class="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Longitude" required />
			<textarea v-model="form.description" class="md:col-span-2 rounded border border-slate-300 px-3 py-2 text-sm" rows="4" :placeholder="$t('propertyForm.description')" required></textarea>
			<div class="md:col-span-2">
				<button class="rounded bg-slate-900 px-4 py-2 text-sm text-white" type="submit">{{ $t('actions.save') }}</button>
			</div>
		</form>
	</section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { createProperty, getProperty, updateProperty } from '../services/properties';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));

const form = ref({
	title: '',
	description: '',
	district: '',
	city: '',
	price: 0,
	listingType: '',
	bedrooms: 1,
	propertyType: '',
	latitude: 0.3476,
	longitude: 32.5825,
	imageUrl: '',
});

async function load() {
	if (!isEdit.value) {
		return;
	}
	const found = await getProperty(route.params.id);
	if (found) {
		form.value = { ...form.value, ...found };
	}
}

async function save() {
	if (isEdit.value) {
		await updateProperty(route.params.id, form.value);
		router.push({ name: 'property-detail', params: { id: route.params.id } });
		return;
	}

	const created = await createProperty(form.value);
	router.push({ name: 'property-detail', params: { id: created.id } });
}

onMounted(load);
</script>
