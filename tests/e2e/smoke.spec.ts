import { test, expect } from '@playwright/test';

test('spa shell mounts app root', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('#app')).toBeVisible();
    await expect(page).toHaveTitle(/Verbeek.ug Real Estates/i);
});
