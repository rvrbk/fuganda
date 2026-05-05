import axios from './http';

function normalizeProperty(item) {
	if (!item) {
		return null;
	}

	const firstImage = Array.isArray(item.images) && item.images.length > 0 ? item.images[0] : null;

	return {
		id: item.id,
		title: item.title,
		description: item.description,
		district: item.district,
		city: item.city,
		address: item.address,
		price: Number(item.price_ugx ?? 0),
		listingType: item.listing_type,
		propertyType: item.property_type,
		bedrooms: Number(item.bedrooms ?? 0),
		bathrooms: Number(item.bathrooms ?? 0),
		latitude: item.latitude == null ? null : Number(item.latitude),
		longitude: item.longitude == null ? null : Number(item.longitude),
		status: item.status,
		publishedAt: item.published_at,
		imageUrl: firstImage?.path ?? '',
		ownerName: item.user?.name ?? null,
	};
}

function toQueryParams(filters = {}) {
	return {
		district: filters.district,
		city: filters.city,
		listing_type: filters.listingType ?? filters.listing_type,
		property_type: filters.propertyType ?? filters.property_type,
		bedrooms: filters.bedrooms,
		min_price: filters.minPrice ?? filters.min_price,
		max_price: filters.maxPrice ?? filters.max_price,
	};
}

function toPayload(input) {
	const imagePath = (input.imageUrl ?? '').trim();

	return {
		title: input.title,
		description: input.description,
		district: input.district,
		city: input.city,
		address: input.address || `${input.city}, ${input.district}`,
		price_ugx: Number(input.price || 0),
		listing_type: input.listingType,
		bedrooms: Number(input.bedrooms || 0),
		bathrooms: Number(input.bathrooms || 0),
		property_type: input.propertyType,
		latitude: Number(input.latitude || 0),
		longitude: Number(input.longitude || 0),
		status: input.status || 'published',
		images: imagePath ? [{ path: imagePath, sort_order: 0 }] : [],
	};
}

export async function listProperties(filters = {}) {
	const { data } = await axios.get('/api/properties', { params: toQueryParams(filters) });
	const rows = data?.data ?? [];
	return rows.map(normalizeProperty);
}

export async function getProperty(id) {
	const { data } = await axios.get(`/api/properties/${id}`);
	return normalizeProperty(data?.data ?? data);
}

export async function createProperty(form) {
	const payload = toPayload(form);
	const { data } = await axios.post('/api/properties', payload);
	return normalizeProperty(data?.data ?? data);
}

export async function updateProperty(id, form) {
	const payload = toPayload(form);
	const { data } = await axios.put(`/api/properties/${id}`, payload);
	return normalizeProperty(data?.data ?? data);
}
