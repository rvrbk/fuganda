const SUPPORTED_CURRENCIES = new Set(['UGX', 'USD']);
const NUMBER_FORMATTER = new Intl.NumberFormat('en-US', {
	maximumFractionDigits: 0,
});

export function formatPrice(value, currency = 'UGX') {
	const amount = Number(value ?? 0);
	const normalizedCurrency = String(currency ?? '').trim().toUpperCase();
	const activeCurrency = SUPPORTED_CURRENCIES.has(normalizedCurrency) ? normalizedCurrency : 'UGX';
	const safeAmount = Number.isFinite(amount) ? amount : 0;

	return `${activeCurrency} ${NUMBER_FORMATTER.format(safeAmount)}`;
}

export function formatUgx(value) {
	return formatPrice(value, 'UGX');
}
