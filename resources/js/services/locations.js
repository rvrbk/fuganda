import axios from './http';

const FALLBACK_PROPERTY_TYPES = ['apartment', 'house', 'land', 'commercial'];
let cachedCitiesByDistrict = {};
let cachedAllCities = [];
let cachedCityToDistrict = {};

export async function listLocations() {
	const { data } = await axios.get('/api/locations');
	const rows = data?.data ?? data ?? [];
	const districts = [];
	const cityMap = {};
	const allCities = [];
	const cityToDistrict = {};

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
		
		// Build all cities list and city-to-district mapping
		if (!allCities.includes(city)) {
			allCities.push(city);
		}
		
		// Map each city to its district (last district wins if city appears in multiple)
		cityToDistrict[city] = district;
	});

	cachedCitiesByDistrict = cityMap;
	cachedAllCities = allCities;
	cachedCityToDistrict = cityToDistrict;

	return {
		districts,
		propertyTypes: FALLBACK_PROPERTY_TYPES,
		citiesByDistrict: cityMap,
		allCities: allCities,
	};
}

export function listCitiesByDistrict(district) {
	return cachedCitiesByDistrict[district] ?? [];
}

export function listAllCities() {
	return cachedAllCities;
}

export function getDistrictByCity(city) {
	return cachedCityToDistrict[city] ?? '';
}
