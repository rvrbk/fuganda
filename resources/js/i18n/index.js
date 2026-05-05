import { createI18n } from 'vue-i18n';

const i18n = createI18n({
	legacy: false,
	locale: 'en',
	fallbackLocale: 'en',
	messages: {
		en: {
			title: 'Fuganda Property Discovery',
			tagline: 'Discover listings across Uganda with quick map search.',
			nav: {
				home: 'Home',
				properties: 'Properties',
				messages: 'Messages',
				dashboard: 'Dashboard',
				login: 'Login',
				logout: 'Logout',
			},
			filters: {
				district: 'District',
				city: 'City',
				minPrice: 'Min Price',
				maxPrice: 'Max Price',
				listingType: 'Listing Type',
				bedrooms: 'Bedrooms',
				propertyType: 'Property Type',
				search: 'Search Properties',
			},
			actions: {
				viewDetails: 'View details',
				createListing: 'Create listing',
				editListing: 'Edit listing',
				edit: 'Edit',
				send: 'Send',
				save: 'Save',
				loadPayload: 'Load Tenant Payload',
			},
			properties: {
				empty: 'No properties matched your filters.',
				notFound: 'Property not found.',
			},
			messages: {
				empty: 'Your inbox is empty.',
				subject: 'Subject',
				body: 'Message',
				contactAgent: 'Contact agent',
			},
			propertyForm: {
				title: 'Property title',
				description: 'Description',
				imageUrl: 'Image URL',
			},
			login: {
				title: 'Sign in',
				email: 'Email',
				password: 'Password',
				submit: 'Sign in now',
				error: 'Login failed. Check credentials and try again.',
			},
		},
		lg: {
			title: 'Fuganda Okunoonya Ebizimbe',
			tagline: 'Ebigambo bya Luganda bikyali bya kusembera.',
			nav: {
				home: 'Awatandikira',
				properties: 'Ebizimbe',
				messages: 'Obubaka',
				dashboard: 'Pawulo',
				login: 'Yingira',
				logout: 'Fuluma',
			},
			filters: {
				district: 'Disitulikiti',
				city: 'Ekibuga',
				minPrice: 'Ssente ezitandika',
				maxPrice: 'Ssente ezisinga',
				listingType: 'Ekika kyokutunda',
				bedrooms: 'Ebisenge',
				propertyType: 'Ekika kyekizimbe',
				search: 'Noonya ebizimbe',
			},
			actions: {
				viewDetails: 'Laba ebisingawo',
				createListing: 'Teeka ekizimbe',
				editListing: 'Kyusa ekizimbe',
				edit: 'Kyusa',
				send: 'Sindika',
				save: 'Tereka',
				loadPayload: 'Funa data ya tenant',
			},
			properties: {
				empty: 'Tewali bizimbe bisangiddwa.',
				notFound: 'Ekizimbe tekisangiddwa.',
			},
			messages: {
				empty: 'Tewali bubaka bukyajja.',
				subject: 'Omulamwa',
				body: 'Obubaka',
				contactAgent: 'Tuukirira agent',
			},
			propertyForm: {
				title: 'Mutwe gwekizimbe',
				description: 'Ennyinyonnyola',
				imageUrl: 'URL yifaananyi',
			},
			login: {
				title: 'Yingira',
				email: 'Email',
				password: 'Password',
				submit: 'Yingira kati',
				error: 'Okuyingira kulemye.',
			},
		},
	},
});

export default i18n;
