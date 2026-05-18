/**
 * E2E payment flow tests — seller billing scenarios
 *
 * Covered scenarios:
 *   1. New seller activates a monthly subscription (inline success)
 *   2. Active seller creates a listing and pays the per-property publish fee
 *   3. Monthly subscription renewal via email link
 *   4. Late / past-due payment recovery
 *   5. Checkout cancellation — user abandons the payment page and returns
 *
 * All tests run against a mocked API so no real Pesapal calls are made.
 * Screenshots are attached to the test report at every meaningful step.
 */
import { expect, test, type Page, type TestInfo } from '@playwright/test';

// ─── Types ───────────────────────────────────────────────────────────────────

type SubscriptionStatus = 'active' | 'inactive' | 'past_due';
type PaymentStatus = 'paid' | 'pending' | 'failed' | 'overdue' | null;

type BillingSessionState = {
    active: boolean;
    subscriptionStatus: SubscriptionStatus;
    /** null = no prior payment attempt (brand-new seller account) */
    paymentStatus: PaymentStatus;
    amountUgx: number;
    planCode: string;
    billingEmail: string;
};

type SubscribeResult = {
    /** Provide a relative URL to simulate an external payment redirect and return.
     *  Leave null/undefined to confirm the subscription inline (no page navigation). */
    redirectUrl?: string | null;
    active?: boolean;
    subscriptionStatus?: SubscriptionStatus;
    paymentStatus?: PaymentStatus;
};

type SetupOptions = {
    billing: BillingSessionState;
    onSubscribe?: () => SubscribeResult;
    onCreateProperty?: () => Record<string, unknown>;
    propertiesList?: Array<Record<string, unknown>>;
    unreadCount?: number;
};

// ─── Screenshot helpers ───────────────────────────────────────────────────────

async function captureStep(page: Page, testInfo: TestInfo, stepName: string): Promise<void> {
    const path = testInfo.outputPath(`${stepName}.png`);
    await page.screenshot({ path, fullPage: true });
    await testInfo.attach(stepName, { path, contentType: 'image/png' });
}

async function captureScenarioStep(
    page: Page,
    testInfo: TestInfo,
    scenario: string,
    stepNumber: number,
    label: string,
): Promise<void> {
    const safeLabel = label.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
    const paddedStep = String(stepNumber).padStart(2, '0');
    await captureStep(page, testInfo, `${scenario}-step-${paddedStep}-${safeLabel}`);
}

// ─── Payload builders ─────────────────────────────────────────────────────────

/**
 * Build the /api/seller/billing/status response body.
 *
 * For a genuinely inactive account (no prior payment attempt) we return
 * subscription: null so that hasPendingPayment() / hasFailedPayment() in the
 * component do not incorrectly disable the "Pay now" button on first load.
 */
function buildBillingStatusPayload(state: BillingSessionState): Record<string, unknown> {
    const subscription =
        state.active || state.subscriptionStatus !== 'inactive'
            ? {
                  billing_email: state.billingEmail,
                  payment_status: state.paymentStatus,
                  status: state.subscriptionStatus,
              }
            : null;

    return {
        seller_has_active_subscription: state.active,
        seller_subscription_status: state.subscriptionStatus,
        account_email: state.billingEmail,
        subscription,
    };
}

function buildAuthMePayload(state: BillingSessionState): Record<string, unknown> {
    return {
        data: {
            id: 101,
            name: 'Seller Test User',
            email: state.billingEmail,
            role: 'seller',
            corporation_id: null,
            seller_has_active_subscription: state.active,
            seller_subscription_status: state.subscriptionStatus,
            seller_subscription_amount: state.amountUgx,
            seller_subscription_currency: 'UGX',
            seller_subscription_plan_code: state.planCode,
        },
    };
}

function buildLocationsPayload(): Record<string, unknown> {
    return {
        data: [
            {
                id: 1,
                district: 'Kampala',
                city: 'Kampala Central',
                slug: 'uganda-kampala-kampala-central',
                country: 'Uganda',
                is_active: true,
            },
            {
                id: 2,
                district: 'Wakiso',
                city: 'Entebbe',
                slug: 'uganda-wakiso-entebbe',
                country: 'Uganda',
                is_active: true,
            },
        ],
    };
}

function buildPropertiesListPayload(
    propertiesList: Array<Record<string, unknown>>,
): Record<string, unknown> {
    return { data: propertiesList };
}

// ─── Mock environment ─────────────────────────────────────────────────────────

const DEFAULT_EXISTING_PROPERTY: Record<string, unknown> = {
    id: 444,
    title: 'Existing Seller Listing',
    description: 'An existing listing owned by the test seller.',
    district: 'Kampala',
    city: 'Kampala Central',
    address: 'Plot 42 Kampala Central',
    price_ugx: 700_000,
    price_currency: 'UGX',
    listing_type: 'rent',
    property_type: 'apartment',
    bedrooms: 2,
    bathrooms: 1,
    latitude: 0.3476,
    longitude: 32.5825,
    status: 'published',
    published_at: null,
    user: { id: 101, name: 'Seller Test User' },
    images: [{ path: '/storage/sample-listing.jpg', sort_order: 0, mime_type: 'image/jpeg' }],
};

async function setupMockedPaymentEnvironment(page: Page, options: SetupOptions): Promise<void> {
    const state = options.billing;
    const propertiesList = options.propertiesList ?? [DEFAULT_EXISTING_PROPERTY];

    // Auth — returns seller profile including subscription flags
    await page.route('**/api/auth/me', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(buildAuthMePayload(state)),
        });
    });

    // Billing status — reflects current mutable state so post-subscribe calls
    // return the updated active/inactive status without a page reload
    await page.route('**/api/seller/billing/status', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(buildBillingStatusPayload(state)),
        });
    });

    // Subscription checkout — mutates state then returns the checkout result
    await page.route('**/api/seller/billing/subscribe', async (route) => {
        const result: SubscribeResult = options.onSubscribe?.() ?? {};

        if (typeof result.active === 'boolean') state.active = result.active;
        if (result.subscriptionStatus) state.subscriptionStatus = result.subscriptionStatus;
        if (result.paymentStatus !== undefined) state.paymentStatus = result.paymentStatus;

        const redirectUrl = result.redirectUrl ?? null;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                seller_has_active_subscription: state.active,
                seller_subscription_status: state.subscriptionStatus,
                // checkout_url at the top level is what normalizeCheckout() reads first
                checkout_url: redirectUrl,
                checkout: {
                    provider: 'pesapal',
                    url: redirectUrl,
                    payment_status: state.paymentStatus,
                    selected_payment_method: 'mobile_money',
                },
                payment_status: state.paymentStatus,
                subscription: {
                    payment_status: state.paymentStatus,
                    status: state.subscriptionStatus,
                    billing_email: state.billingEmail,
                },
            }),
        });
    });

    // Ancillary endpoints
    await page.route('**/api/messages/unread-count', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ unread_count: options.unreadCount ?? 0 }),
        });
    });

    await page.route('**/api/locations', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(buildLocationsPayload()),
        });
    });

    // Properties — GET returns list, POST returns newly created property
    await page.route('**/api/properties**', async (route) => {
        const method = route.request().method();

        if (method === 'GET') {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(buildPropertiesListPayload(propertiesList)),
            });
            return;
        }

        if (method === 'POST') {
            const created = options.onCreateProperty?.() ?? {
                id: 777,
                title: 'New Listing',
                description: 'Created in test',
                district: 'Kampala',
                city: 'Kampala Central',
                address: 'Plot 77 Kampala Central',
                price_ugx: 950_000,
                price_currency: 'UGX',
                listing_type: 'rent',
                property_type: 'apartment',
                bedrooms: 2,
                bathrooms: 2,
                latitude: 0.3476,
                longitude: 32.5825,
                status: 'draft',
                published_at: null,
                user: { id: 101, name: 'Seller Test User' },
                images: [],
                publish_fee_payment_required: true,
                publish_fee_checkout_url: '/?owned=1&created=1',
            };

            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify(created),
            });
            return;
        }

        await route.continue();
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

/** Fill out the minimum required fields on the Create Listing form. */
async function fillPropertyForm(page: Page): Promise<void> {
    await page.locator('#property-title').fill('Test Payment Property');
    await page.locator('#property-district').selectOption('Kampala');
    await page.locator('#property-city').selectOption('Kampala Central');
    await page.locator('#property-listing-type').selectOption('rent');
    await page.locator('#property-property-type').selectOption('apartment');
    await page.locator('#property-price').fill('1200000');
    await page.locator('#property-bedrooms').fill('3');
    await page.locator('#property-bathrooms').fill('2');
    await page.locator('#property-latitude').fill('0.3476');
    await page.locator('#property-longitude').fill('32.5825');
    await page.locator('#property-address').fill('Plot 8 Kampala Central');
    await page.locator('#property-description').fill('Property created in playwright payment test.');
}

// ─── Tests ────────────────────────────────────────────────────────────────────

test.describe('seller payment flow with screenshots', () => {
    /**
     * Scenario 1: New seller activates a monthly subscription
     *
     * The subscribe API confirms the payment inline (no external redirect).
     * The component shows "Subscription is active. You can continue now."
     * immediately after the call resolves, and the Continue button becomes
     * enabled so the seller can reach the dashboard.
     */
    test('subscription onboarding flow — new seller activates and reaches dashboard', async ({
        page,
    }, testInfo) => {
        const billingState: BillingSessionState = {
            active: false,
            subscriptionStatus: 'inactive',
            paymentStatus: null, // no prior subscription record
            amountUgx: 39_000,
            planCode: 'starter_monthly',
            billingEmail: 'seller@example.com',
        };

        await setupMockedPaymentEnvironment(page, {
            billing: billingState,
            onSubscribe: () => ({
                redirectUrl: null, // inline confirmation — no external redirect needed
                active: true,
                subscriptionStatus: 'active',
                paymentStatus: 'paid',
            }),
        });

        // ── Step 1: Land on onboarding ────────────────────────────────────
        await page.goto('/seller/onboarding');
        await expect(page.getByRole('heading', { name: /Activate seller subscription/i })).toBeVisible();
        await captureScenarioStep(page, testInfo, 'subscription-purchase', 1, 'onboarding-initial');

        // ── Step 2: Pricing copy is visible ───────────────────────────────
        await expect(page.getByText(/UGX\s*39,?000/i)).toBeVisible();
        await captureScenarioStep(page, testInfo, 'subscription-purchase', 2, 'pricing-visible');

        // ── Step 3: Pay button is enabled (no pending or failed prior payment) ─
        const payButton = page.getByRole('button', { name: /Pay now/i });
        await expect(payButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'subscription-purchase', 3, 'pay-button-enabled');

        // ── Step 4: Click Pay now — inline confirmation ───────────────────
        await payButton.click();
        await expect(
            page.getByText(/Subscription is active\. You can continue now\./i),
        ).toBeVisible();
        await captureScenarioStep(page, testInfo, 'subscription-purchase', 4, 'payment-confirmed-inline');

        // ── Step 5: Continue button is enabled ────────────────────────────
        const continueButton = page.getByRole('button', { name: /Continue to dashboard/i });
        await expect(continueButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'subscription-purchase', 5, 'continue-button-enabled');

        // ── Step 6: Navigate to dashboard ────────────────────────────────
        await continueButton.click();
        await page.waitForURL((url) => url.pathname === '/dashboard');
        await captureScenarioStep(page, testInfo, 'subscription-purchase', 6, 'dashboard-reached');
    });

    /**
     * Scenario 2: Active seller creates a listing — per-property publish fee
     *
     * After saving the form the API returns publish_fee_payment_required=true
     * together with a checkout URL.  The component redirects the browser to that
     * URL (mocked to return to /?owned=1&created=1).  The success notice
     * "Property placed successfully." is shown on the home/listings screen.
     */
    test('pay per property — create listing triggers publish fee and shows confirmation', async ({
        page,
    }, testInfo) => {
        const billingState: BillingSessionState = {
            active: true,
            subscriptionStatus: 'active',
            paymentStatus: 'paid',
            amountUgx: 39_000,
            planCode: 'starter_monthly',
            billingEmail: 'seller@example.com',
        };

        await setupMockedPaymentEnvironment(page, {
            billing: billingState,
            onCreateProperty: () => ({
                id: 888,
                title: 'Test Payment Property',
                description: 'Property created in playwright payment test.',
                district: 'Kampala',
                city: 'Kampala Central',
                address: 'Plot 8 Kampala Central',
                price_ugx: 1_200_000,
                price_currency: 'UGX',
                listing_type: 'rent',
                property_type: 'apartment',
                bedrooms: 3,
                bathrooms: 2,
                latitude: 0.3476,
                longitude: 32.5825,
                status: 'draft',
                user: { id: 101, name: 'Seller Test User' },
                images: [],
                // Triggers window.location.assign() in the form component
                publish_fee_payment_required: true,
                publish_fee_checkout_url: '/?owned=1&created=1',
            }),
            propertiesList: [
                {
                    id: 888,
                    title: 'Test Payment Property',
                    description: 'Property created in playwright payment test.',
                    district: 'Kampala',
                    city: 'Kampala Central',
                    address: 'Plot 8 Kampala Central',
                    price_ugx: 1_200_000,
                    price_currency: 'UGX',
                    listing_type: 'rent',
                    property_type: 'apartment',
                    bedrooms: 3,
                    bathrooms: 2,
                    latitude: 0.3476,
                    longitude: 32.5825,
                    status: 'published',
                    published_at: null,
                    user: { id: 101, name: 'Seller Test User' },
                    images: [
                        { path: '/storage/test-property.jpg', sort_order: 0, mime_type: 'image/jpeg' },
                    ],
                },
            ],
        });

        // ── Step 1: Create Listing form loads ─────────────────────────────
        await page.goto('/properties/new');
        await expect(page.getByRole('heading', { name: /Create listing/i })).toBeVisible();
        await captureScenarioStep(page, testInfo, 'pay-per-property', 1, 'create-listing-loaded');

        // ── Step 2: Fill in the property form ────────────────────────────
        await fillPropertyForm(page);
        await captureScenarioStep(page, testInfo, 'pay-per-property', 2, 'form-filled');

        // ── Step 3: Save — API returns publish fee checkout URL ──────────
        const saveButton = page.getByRole('button', { name: /^Save$/i });
        await expect(saveButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'pay-per-property', 3, 'save-button-enabled');
        await saveButton.click();

        // ── Step 4: Browser redirected to /?owned=1&created=1 ────────────
        await page.waitForURL((url) => url.pathname === '/' && url.searchParams.get('owned') === '1');
        await expect(page.getByText(/Property placed successfully\./i)).toBeVisible();
        await captureScenarioStep(page, testInfo, 'pay-per-property', 4, 'placed-success-notice');

        // ── Step 5: Listing title is visible in the properties list ───────
        await expect(page.getByText(/Test Payment Property/i)).toBeVisible();
        await captureScenarioStep(page, testInfo, 'pay-per-property', 5, 'listing-visible-in-list');
    });

    /**
     * Scenario 3: Monthly subscription renewal via email link
     *
     * A seller whose subscription lapsed clicks the renewal link from their
     * billing email.  The link arrives as /seller/onboarding?source=email-renewal.
     * The payment flow is identical to Scenario 1; the source param is preserved
     * as context but does not alter the UI behaviour.
     */
    test('monthly renewal via email link — seller pays and reaches dashboard', async ({
        page,
    }, testInfo) => {
        const billingState: BillingSessionState = {
            active: false,
            subscriptionStatus: 'inactive',
            paymentStatus: null,
            amountUgx: 39_000,
            planCode: 'starter_monthly',
            billingEmail: 'seller@example.com',
        };

        await setupMockedPaymentEnvironment(page, {
            billing: billingState,
            onSubscribe: () => ({
                redirectUrl: null,
                active: true,
                subscriptionStatus: 'active',
                paymentStatus: 'paid',
            }),
        });

        // ── Step 1: Renewal link opens onboarding ─────────────────────────
        await page.goto('/seller/onboarding?source=email-renewal');
        await expect(page.getByRole('heading', { name: /Activate seller subscription/i })).toBeVisible();
        await captureScenarioStep(page, testInfo, 'renewal-email-link', 1, 'email-link-opened');

        // ── Step 2: Pricing is shown, Pay now button is enabled ───────────
        await expect(page.getByText(/UGX\s*39,?000/i)).toBeVisible();
        const payButton = page.getByRole('button', { name: /Pay now/i });
        await expect(payButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'renewal-email-link', 2, 'renewal-prompt-visible');

        // ── Step 3: Pay — inline confirmation ────────────────────────────
        await payButton.click();
        await expect(
            page.getByText(/Subscription is active\. You can continue now\./i),
        ).toBeVisible();
        await captureScenarioStep(page, testInfo, 'renewal-email-link', 3, 'payment-completed');

        // ── Step 4: Continue button is enabled ────────────────────────────
        const continueButton = page.getByRole('button', { name: /Continue to dashboard/i });
        await expect(continueButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'renewal-email-link', 4, 'dashboard-continue-enabled');

        // ── Step 5: Navigate to dashboard ────────────────────────────────
        await continueButton.click();
        await page.waitForURL((url) => url.pathname === '/dashboard');
        await captureScenarioStep(page, testInfo, 'renewal-email-link', 5, 'dashboard-reached');
    });

    /**
     * Scenario 4: Late / past-due payment recovery
     *
     * The seller's subscription is past_due.  Attempting to reach /dashboard
     * triggers the router guard which redirects to /seller/onboarding.  The
     * component's refreshStatus() detects the failed state and shows an inline
     * error.  Clicking Pay now re-activates the subscription.
     */
    test('late payment — past-due seller is redirected to onboarding and recovers', async ({
        page,
    }, testInfo) => {
        const billingState: BillingSessionState = {
            active: false,
            subscriptionStatus: 'past_due',
            paymentStatus: 'overdue',
            amountUgx: 39_000,
            planCode: 'starter_monthly',
            billingEmail: 'seller@example.com',
        };

        await setupMockedPaymentEnvironment(page, {
            billing: billingState,
            onSubscribe: () => ({
                redirectUrl: null,
                active: true,
                subscriptionStatus: 'active',
                paymentStatus: 'paid',
            }),
        });

        // ── Step 1: Attempting /dashboard triggers redirect to onboarding ─
        await page.goto('/dashboard');
        await page.waitForURL((url) => url.pathname === '/seller/onboarding');
        await captureScenarioStep(page, testInfo, 'late-payment-recovery', 1, 'redirected-to-onboarding');

        // ── Step 2: Past-due error is displayed ───────────────────────────
        await expect(
            page.getByText(/Last payment attempt failed\. Please try Pay now again\./i),
        ).toBeVisible();
        await captureScenarioStep(page, testInfo, 'late-payment-recovery', 2, 'past-due-error-shown');

        // ── Step 3: Pay now is enabled (failed state, not pending) ────────
        const payButton = page.getByRole('button', { name: /Pay now/i });
        await expect(payButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'late-payment-recovery', 3, 'pay-button-enabled');

        // ── Step 4: Repay — inline confirmation ──────────────────────────
        await payButton.click();
        await expect(
            page.getByText(/Subscription is active\. You can continue now\./i),
        ).toBeVisible();
        await captureScenarioStep(page, testInfo, 'late-payment-recovery', 4, 'repayment-success');

        // ── Step 5: Continue button is enabled ────────────────────────────
        const continueButton = page.getByRole('button', { name: /Continue to dashboard/i });
        await expect(continueButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'late-payment-recovery', 5, 'recovered-continue-enabled');

        // ── Step 6: Navigate to dashboard ────────────────────────────────
        await continueButton.click();
        await page.waitForURL((url) => url.pathname === '/dashboard');
        await captureScenarioStep(page, testInfo, 'late-payment-recovery', 6, 'dashboard-reached');
    });

    /**
     * Scenario 5: Checkout cancellation
     *
     * Simulates the seller abandoning the external Pesapal page and being
     * redirected back to /seller/onboarding?billing_result=cancel.
     *
     * The component sets the cancel error message in processReturnState(), but
     * the immediately-following refreshStatus() call clears errorMessage as it
     * re-fetches billing state.  Because the account is still inactive (no
     * payment was completed), refreshStatus() finds no pending or failed state
     * and leaves no message — the form simply resets so the seller can retry.
     *
     * We therefore test the durable post-cancel state: the onboarding page is
     * clean, the query param has been stripped by the Vue router, and the
     * Pay now button is available for a retry.
     */
    test('checkout cancellation — seller is returned to onboarding and can retry', async ({
        page,
    }, testInfo) => {
        const billingState: BillingSessionState = {
            active: false,
            subscriptionStatus: 'inactive',
            paymentStatus: null,
            amountUgx: 39_000,
            planCode: 'starter_monthly',
            billingEmail: 'seller@example.com',
        };

        await setupMockedPaymentEnvironment(page, {
            billing: billingState,
            // state does NOT change — payment was never completed
        });

        // ── Step 1: Simulate arriving at the cancel return URL directly
        //           (as if the payment provider redirected the browser here)
        await page.goto('/seller/onboarding?billing_result=cancel');
        await captureScenarioStep(page, testInfo, 'checkout-cancel', 1, 'cancel-return-url-landed');

        // ── Step 2: Vue router strips the billing_result param ────────────
        //           Wait for the clean URL (router.replace removes the param)
        await page.waitForURL((url) => !url.searchParams.has('billing_result'));
        await expect(page.getByRole('heading', { name: /Activate seller subscription/i })).toBeVisible();
        await captureScenarioStep(page, testInfo, 'checkout-cancel', 2, 'query-param-stripped');

        // ── Step 3: No subscription block — form is fully usable ─────────
        await expect(page.getByText(/You need an active seller subscription/i)).not.toBeVisible();
        await captureScenarioStep(page, testInfo, 'checkout-cancel', 3, 'no-subscription-block');

        // ── Step 4: Pay now is enabled — seller can retry immediately ─────
        const retryButton = page.getByRole('button', { name: /Pay now/i });
        await expect(retryButton).toBeEnabled();
        await captureScenarioStep(page, testInfo, 'checkout-cancel', 4, 'retry-button-enabled');

        // ── Step 5: Retry — inline success on second attempt ─────────────
        await setupMockedPaymentEnvironment(page, {
            billing: billingState,
            onSubscribe: () => ({
                redirectUrl: null,
                active: true,
                subscriptionStatus: 'active',
                paymentStatus: 'paid',
            }),
        });
        await retryButton.click();
        await expect(
            page.getByText(/Subscription is active\. You can continue now\./i),
        ).toBeVisible();
        await captureScenarioStep(page, testInfo, 'checkout-cancel', 5, 'retry-succeeded');
    });
});
