# Project Context

- **Project:** fuganda
- **Created:** 2026-05-05
- **Requested By:** rvrbk
- **Stack:** Vue.js SPA consuming Laravel API

## Core Context

Frontend context seeded for marketplace discovery and listing flows.

## Recent Updates

📌 Team roster initialized on 2026-05-05.

## Learnings

- Mobile-first and low-bandwidth UX are core success factors.
- 2026-05-07: PropertiesListView restructured to a map-first layout. App shell wraps routed views in `<div class="mx-auto max-w-6xl p-4">` (App.vue:36) — to escape the shell for full-bleed UIs, use `fixed inset-0` rather than the negative-margin hack (`-mx-[50vw] w-screen`). Cleaner and avoids body horizontal-scroll edge cases.
- Floating panels over Leaflet: use `z-[1000]` (Leaflet's overlayPane caps near 700). White/95 opacity + `backdrop-blur` reads well over the map.
- Filter pattern across views: Home pushes the route, the listing view reads `route.query` and watches it. On the listing view itself, prefer `router.replace` (not `push`) so back-button behavior stays sane while iterating filters.
- Listings strip along the bottom (horizontal `overflow-x-auto`) plays well with mobile and keeps the map dominant; cards shrunk to `w-64` with `h-24` images for the strip.
- 2026-05-12: Seller onboarding now uses a redirect-first Stripe UX in `resources/js/views/SellerOnboardingView.vue`, with return-state parsing (`billing_result`/`payment_status`) and no manual payment reference field. Keep onboarding query sanitization via `router.replace` so return tokens do not persist in history.
