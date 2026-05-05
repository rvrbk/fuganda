# Squad Decisions

## Active Decisions

### 2026-05-05: MVP scope lock (authoritative)

- Launch target: internal pilot first.
- Market focus: landlords-first acquisition.
- Coverage: nationwide Uganda.
- Primary contact channel: email.
- MVP includes rentals.
- MVP excludes payments.
- Architecture: headless Laravel API + Vue SPA with Sanctum and Fortify.
- Mapping stack: Leaflet.
- Hosting setup deferred for now.

### 2026-05-05: MVP trust and verification defaults

- Use lightweight landlord verification in MVP (email verification, phone OTP, listing moderation, optional national ID for manual review).
- Defer stricter KYC to post-MVP.
- Apply secure-by-default anti-fraud controls for listings and messaging from MVP.

### 2026-05-05: MVP north-star metric

- Track Weekly Contact Intent Rate (WCIR): percentage of active listings that receive at least one qualified renter inquiry via platform email flow each week.

### 2026-05-05: Delivery phasing baseline

- Adopt an 8-sprint (2 weeks each) phased plan from foundation/auth/listings through search/messaging and scale/ops.
- Preserve payment readiness architecture during MVP while deferring checkout launch.
- Frontend baseline: 12-week mobile-first rollout with staged modules, Pinia + TanStack Query, and progressive PWA readiness.
- Backend baseline: 12-week API-first execution with contracts, auth hardening, SQL-first search with Scout/Meilisearch migration path, media pipeline, messaging/eventing, and observability/test maturity.

### 2026-05-05: Sprint 1 planning lock (authoritative)

- Final planning bundle confirmed: 1A, 2A, 3A, 4B, 5A.
- Verification and publish gate: email verification, phone OTP, and moderation before publish.
- Inquiry routing uses masked relay email.
- Moderation SLA target is under 24 hours.
- Nationwide search UX in Sprint 1 includes district filter plus map radius from day one.
- Sprint 1 runs for 2 weeks with backend foundations first, frontend integration second, and continuous QA with end-loaded regression.
- Sprint 1 API surface is limited to auth bootstrap, verification, listing submission/moderation, search (district + radius), and masked inquiry relay endpoints.
- Sprint 1 Definition of Done requires WCIR instrumentation for listing view and inquiry submit events, plus moderation SLA tracking.

## Governance

- All meaningful changes require team consensus
- Document architectural decisions here
- Keep history focused on work, decisions focused on direction
