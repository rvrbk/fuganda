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
		senderId: item.sender_id,
		receiverId: item.receiver_id,
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
		receiver_id: input.receiverId ?? input.receiver_id,
	};

	const { data } = await axios.post('/api/messages', payload);
	return normalizeMessage(data?.data ?? data);
}

export async function createGuestPropertyContact(input) {
	const payload = {
		property_id: input.propertyId ?? input.property_id,
		email: input.email,
		subject: input.subject,
		body: input.body,
	};

	const { data } = await axios.post('/api/public/property-contact', payload);
	return data;
}

export async function getUnreadMessageCount() {
	const { data } = await axios.get('/api/messages/unread-count');
	const value = Number(data?.unread_count ?? 0);
	if (!Number.isFinite(value) || value < 0) {
		return 0;
	}

	return value;
}
