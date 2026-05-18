import { expect, test } from '@playwright/test';

type Role = 'buyer' | 'seller' | 'admin';

async function mockAuthenticatedProfile(page: import('@playwright/test').Page, role: Role) {
    await page.route('**/api/auth/me', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                data: {
                    id: 1,
                    name: `${role} user`,
                    email: `${role}@example.com`,
                    role,
                    corporation_id: null,
                },
            }),
        });
    });
}

async function mockPropertiesList(page: import('@playwright/test').Page) {
    await page.route('**/api/properties**', async (route) => {
        if (route.request().method() !== 'GET') {
            await route.continue();
            return;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                data: [
                    {
                        id: 11,
                        title: 'Sample Listing',
                        description: 'Sample description',
                        district: 'Kampala',
                        city: 'Kampala',
                        price_ugx: 1200000,
                        price_currency: 'UGX',
                        listing_type: 'rent',
                        property_type: 'apartment',
                        bedrooms: 2,
                        bathrooms: 1,
                        latitude: 0.3476,
                        longitude: 32.5825,
                        status: 'published',
                        published_at: null,
                        user: { id: 2, name: 'Seller' },
                        images: [{ path: '/storage/sample.jpg', sort_order: 0 }],
                    },
                ],
            }),
        });
    });
}

async function mockPropertyDetail(page: import('@playwright/test').Page) {
    await page.route('**/api/properties/11', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                id: 11,
                title: 'Sample Listing',
                description: 'Sample description',
                district: 'Kampala',
                city: 'Kampala',
                address: 'Kampala',
                price_ugx: 1200000,
                price_currency: 'UGX',
                listing_type: 'rent',
                property_type: 'apartment',
                bedrooms: 2,
                bathrooms: 1,
                latitude: 0.3476,
                longitude: 32.5825,
                status: 'published',
                published_at: null,
                user: { id: 2, name: 'Seller' },
                images: [{ path: '/storage/sample.jpg', sort_order: 0 }],
            }),
        });
    });
}

test.describe('role-based seller action visibility', () => {
    test('buyer should not see create listing action on properties page', async ({ page }) => {
        await mockAuthenticatedProfile(page, 'buyer');
        await mockPropertiesList(page);

        await page.goto('/properties');

        await expect(page.getByRole('link', { name: /create listing/i })).toHaveCount(0);
    });

    test('seller should still see create listing action on properties page', async ({ page }) => {
        await mockAuthenticatedProfile(page, 'seller');
        await mockPropertiesList(page);

        await page.goto('/properties');

        await expect(page.getByRole('link', { name: /create listing/i })).toBeVisible();
    });

    test('buyer should not see edit action on property detail page', async ({ page }) => {
        await mockAuthenticatedProfile(page, 'buyer');
        await mockPropertyDetail(page);

        await page.goto('/properties/11');

        await expect(page.getByRole('link', { name: /^edit$/i })).toHaveCount(0);
    });
});
