export function formatUgx(value) {
	const amount = Number(value ?? 0);
	return new Intl.NumberFormat('en-UG', {
		style: 'currency',
		currency: 'UGX',
		maximumFractionDigits: 0,
	}).format(Number.isFinite(amount) ? amount : 0);
}
