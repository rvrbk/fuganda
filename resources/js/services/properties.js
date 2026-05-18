import axios from './http';

const VIDEO_EXTENSIONS = new Set(['mp4', 'webm', 'mov']);
const SUPPORTED_CURRENCIES = new Set(['UGX', 'USD']);

function normalizeImagePath(path) {
	if (!path) {
		return '';
	}

	if (path.startsWith('/')) {
		return path;
	}

	try {
		const parsed = new URL(path);
		if (parsed.pathname.startsWith('/storage/')) {
			return parsed.pathname;
		}
	} catch {
		return path;
	}

	return path;
}

function normalizeCurrencyCode(value) {
	const normalized = String(value ?? '').trim().toUpperCase();
	return SUPPORTED_CURRENCIES.has(normalized) ? normalized : 'UGX';
}

function toNullableNumber(value) {
	if (value === '' || value === null || value === undefined) {
		return null;
	}

	const parsed = Number(value);
	return Number.isFinite(parsed) ? parsed : null;
}

function normalizeProperty(item) {
	if (!item) {
		return null;
	}

	const firstImage = Array.isArray(item.images) && item.images.length > 0 ? item.images[0] : null;
	const media = Array.isArray(item.images)
		? item.images
			.map((image) => {
				const path = normalizeImagePath(image?.path ?? '');
				if (!path) {
					return null;
				}

				const mimeType = image?.mime_type ?? '';
				const extension = path.split('.').pop()?.toLowerCase() ?? '';
				const kind = mimeType.startsWith('video/') || VIDEO_EXTENSIONS.has(extension) ? 'video' : 'image';

				return {
					path,
					kind,
				};
			})
			.filter(Boolean)
		: [];

	return {
		id: item.id,
		title: item.title,
		description: item.description,
		district: item.district,
		city: item.city,
		address: item.address,
		price: Number(item.price_ugx ?? 0),
		priceCurrency: normalizeCurrencyCode(item.price_currency ?? item.currency),
		listingType: item.listing_type,
		propertyType: item.property_type,
		bedrooms: Number(item.bedrooms ?? 0),
		bathrooms: Number(item.bathrooms ?? 0),
		latitude: item.latitude == null ? null : Number(item.latitude),
		longitude: item.longitude == null ? null : Number(item.longitude),
		status: item.status,
		publishedAt: item.published_at,
		imageUrl: normalizeImagePath(firstImage?.path ?? ''),
		media,
		ownerId: item.user?.id ?? item.user_id ?? null,
		ownerName: item.user?.name ?? null,
		publishFeePaymentRequired: Boolean(item.publish_fee_payment_required ?? false),
		publishFeeCheckoutUrl: item.publish_fee_checkout_url ?? null,
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
		owned: filters.owned,
	};
}

function toPayload(input) {
	const imagePath = (input.imageUrl ?? '').trim();
	const mediaPaths = Array.isArray(input.mediaPaths)
		? input.mediaPaths.map((path) => String(path ?? '').trim()).filter(Boolean)
		: [];
	const images = mediaPaths.length
		? mediaPaths.map((path, index) => ({ path, sort_order: index }))
		: imagePath
			? [{ path: imagePath, sort_order: 0 }]
			: [];

	return {
		title: input.title,
		description: input.description,
		district: input.district,
		city: input.city,
		address: input.address || `${input.city}, ${input.district}`,
		price_ugx: Number(input.price || 0),
		price_currency: normalizeCurrencyCode(input.priceCurrency),
		listing_type: input.listingType,
		bedrooms: Number(input.bedrooms || 0),
		bathrooms: Number(input.bathrooms || 0),
		property_type: input.propertyType,
		latitude: toNullableNumber(input.latitude),
		longitude: toNullableNumber(input.longitude),
		status: input.status || 'published',
		images,
	};
}

export function extractApiErrorMessage(error) {
	const data = error?.response?.data;

	if (typeof data?.message === 'string' && data.message.trim()) {
		return data.message.trim();
	}

	const errors = data?.errors;
	if (errors && typeof errors === 'object') {
		for (const value of Object.values(errors)) {
			if (Array.isArray(value)) {
				const firstText = value.find((entry) => typeof entry === 'string' && entry.trim());
				if (firstText) {
					return firstText.trim();
				}
				continue;
			}

			if (typeof value === 'string' && value.trim()) {
				return value.trim();
			}
		}
	}

	if (typeof data?.error === 'string' && data.error.trim()) {
		return data.error.trim();
	}

	if (typeof data?.detail === 'string' && data.detail.trim()) {
		return data.detail.trim();
	}

	return '';
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

export async function uploadPropertyMedia(file) {
	const payload = new FormData();
	payload.append('file', file);

	const { data } = await axios.post('/api/uploads/media', payload, {
		headers: {
			'Content-Type': 'multipart/form-data',
		},
	});

	return data?.path ?? '';
}

export const uploadPropertyImage = uploadPropertyMedia;
