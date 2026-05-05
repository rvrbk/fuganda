import axios from './http';

function normalizeMessage(item) {
	if (!item) {
		return null;
	}

	return {
		id: item.id,
		subject: item.property?.title ? `Inquiry: ${item.property.title}` : 'Property inquiry',
		body: item.body,
		from: item.sender?.name ?? `User #${item.sender_id}`,
		to: item.receiver?.name ?? `User #${item.receiver_id}`,
		createdAt: item.created_at,
		propertyId: item.property_id,
	};
}

export async function listMessages() {
	const { data } = await axios.get('/api/messages');
	const rows = data?.data ?? [];
	return rows.map(normalizeMessage);
}

export async function createMessage(input) {
	const payload = {
		body: input.body,
		property_id: input.propertyId ?? input.property_id,
	};

	const { data } = await axios.post('/api/messages', payload);
	return normalizeMessage(data?.data ?? data);
}
