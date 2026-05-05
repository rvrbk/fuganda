import axios from './http';

const FALLBACK_PROPERTY_TYPES = ['apartment', 'house', 'land', 'commercial'];
let cachedCitiesByDistrict = {};

export async function listLocations() {
	const { data } = await axios.get('/api/locations');
	const rows = data?.data ?? data ?? [];
	const districts = [];
	const cityMap = {};

	rows.forEach((row) => {
		const district = row.district;
		const city = row.city;

		if (!district || !city) {
			return;
		}

		if (!cityMap[district]) {
			cityMap[district] = [];
			districts.push(district);
		}

		if (!cityMap[district].includes(city)) {
			cityMap[district].push(city);
		}
	});

	cachedCitiesByDistrict = cityMap;

	return {
		districts,
		propertyTypes: FALLBACK_PROPERTY_TYPES,
		citiesByDistrict: cityMap,
	};
}

export function listCitiesByDistrict(district) {
	return cachedCitiesByDistrict[district] ?? [];
}
