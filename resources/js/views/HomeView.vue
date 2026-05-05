<template>
    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ $t('filters.search') }}</h2>

        <form class="grid gap-3 md:grid-cols-3" @submit.prevent="search">
            <select v-model="filters.district" class="rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">{{ $t('filters.district') }}</option>
                <option v-for="district in locations.districts" :key="district" :value="district">{{ district }}</option>
            </select>

            <select v-model="filters.city" class="rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">{{ $t('filters.city') }}</option>
                <option v-for="city in cityOptions" :key="city" :value="city">{{ city }}</option>
            </select>

            <select v-model="filters.listingType" class="rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">{{ $t('filters.listingType') }}</option>
                <option value="rent">Rent</option>
                <option value="sale">Sale</option>
            </select>

            <input v-model.number="filters.minPrice" class="rounded border border-slate-300 px-3 py-2 text-sm" type="number" :placeholder="$t('filters.minPrice')" />
            <input v-model.number="filters.maxPrice" class="rounded border border-slate-300 px-3 py-2 text-sm" type="number" :placeholder="$t('filters.maxPrice')" />
            <input v-model.number="filters.bedrooms" class="rounded border border-slate-300 px-3 py-2 text-sm" type="number" min="0" :placeholder="$t('filters.bedrooms')" />

            <select v-model="filters.propertyType" class="rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">{{ $t('filters.propertyType') }}</option>
                <option v-for="propertyType in locations.propertyTypes" :key="propertyType" :value="propertyType">{{ propertyType }}</option>
            </select>

            <div class="md:col-span-2">
                <button class="rounded bg-sky-700 px-4 py-2 text-sm text-white" type="submit">{{ $t('filters.search') }}</button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { listLocations, listCitiesByDistrict } from '../services/locations';

const router = useRouter();
const locations = ref({ districts: [], propertyTypes: [] });
const filters = ref({
    district: '',
    city: '',
    minPrice: null,
    maxPrice: null,
    listingType: '',
    bedrooms: null,
    propertyType: '',
});

const cityOptions = computed(() => listCitiesByDistrict(filters.value.district));

const search = () => {
    const query = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null),
    );
    router.push({ name: 'properties', query });
};

onMounted(async () => {
    locations.value = await listLocations();
});
</script>
