export const mockLocations = {
	districts: ['Kampala', 'Wakiso', 'Mukono', 'Mbarara'],
	citiesByDistrict: {
		Kampala: ['Kampala Central', 'Nakawa', 'Makindye'],
		Wakiso: ['Entebbe', 'Nansana', 'Kira'],
		Mukono: ['Mukono Town', 'Seeta'],
		Mbarara: ['Mbarara City'],
	},
	listingTypes: ['rent', 'sale'],
	propertyTypes: ['apartment', 'house', 'land', 'commercial'],
};

export const mockProperties = [
	{
		id: 1,
		title: 'Modern Apartment in Kololo',
		description: 'Two-bedroom apartment with balcony and parking.',
		district: 'Kampala',
		city: 'Kampala Central',
		price: 1800000,
		listingType: 'rent',
		bedrooms: 2,
		propertyType: 'apartment',
		latitude: 0.337,
		longitude: 32.585,
		imageUrl: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=60',
	},
	{
		id: 2,
		title: 'Family House in Kira',
		description: 'Standalone home with a compound and servant quarter.',
		district: 'Wakiso',
		city: 'Kira',
		price: 320000000,
		listingType: 'sale',
		bedrooms: 4,
		propertyType: 'house',
		latitude: 0.395,
		longitude: 32.649,
		imageUrl: 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=60',
	},
	{
		id: 3,
		title: 'Retail Unit near Entebbe Road',
		description: 'Ground-floor commercial space with high foot traffic.',
		district: 'Wakiso',
		city: 'Entebbe',
		price: 2500000,
		listingType: 'rent',
		bedrooms: 0,
		propertyType: 'commercial',
		latitude: 0.12,
		longitude: 32.48,
		imageUrl: 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=60',
	},
];

export const mockMessages = [
	{
		id: 1,
		subject: 'Inquiry: Modern Apartment in Kololo',
		body: 'Is this property still available for immediate move-in?',
		propertyId: 1,
		createdAt: '2026-05-01T10:30:00Z',
		from: 'tenant@example.com',
		to: 'agent@fuganda.test',
	},
];
