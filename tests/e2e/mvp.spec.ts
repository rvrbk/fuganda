import { expect, test } from '@playwright/test';

test('spa shell loads with primary app heading', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('#app')).toBeVisible();
    await expect(page.getByRole('heading', { name: /fuganda tenant portal/i })).toBeVisible();
});

test('properties page renders filter controls', async ({ page }) => {
    await page.goto('/properties');

    await expect(page.getByRole('heading', { name: /properties|property/i })).toBeVisible();
    await expect(page.getByLabel(/district/i)).toBeVisible();
    await expect(page.getByLabel(/city/i)).toBeVisible();
    await expect(page.getByLabel(/listing type/i)).toBeVisible();
    await expect(page.getByLabel(/property type/i)).toBeVisible();
    await expect(page.getByLabel(/min price/i)).toBeVisible();
    await expect(page.getByLabel(/max price/i)).toBeVisible();
});
