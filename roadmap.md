# Verbeek.ug Real Estates Roadmap (6-9 Months)

## Project Overview and Goals
Verbeek.ug Real Estates is a Uganda-focused real estate platform for buying, renting, and selling property. The product goal is to make property discovery and transactions easier and safer for buyers, sellers, agents, and administrators.

**Core product scope**
- Listings with filters (price, location, type, status).
- Role-based workflows for buyer, seller, agent, and admin.
- Property search, image uploads, and in-platform messaging.
- Localization: English/Luganda (EN/LG), UGX pricing, and Ugandan district/city location data.

## Target Architecture
- Headless Laravel API + Vue SPA (single-page app) for fast web/mobile-first UX.
- Product model: single-platform, user-first architecture with no required agency/tenant layer.
- Auth and account lifecycle:
	- **Fortify** for registration, login, password reset, profile/account security flows.
	- **Sanctum** for SPA/API session authentication and protected API access.
- Authorization model: buyer/seller/agent/admin roles with policy-based access controls.
- Ownership model: seller users can own and manage multiple properties.
- Database: MySQL or PostgreSQL (production-ready, indexed for listing/search workloads).

**Package and library choices**
- Backend: Spatial, Scout, Intervention Image.
- Frontend: Vue Router, Pinia, Axios, VeeValidate, VueUse, Leaflet.

## Uganda-Specific Adaptations
- Localization: EN/LG language toggles, UGX currency formatting, district/city datasets for accurate search filters.
- Payments: MTN MoMo and Airtel Money first; bank transfer path next; escrow feasibility to be validated with legal/partner input.
- Data constraints: offline/PWA support for key flows, low-bandwidth optimizations, image compression + lazy loading.
- Legal/compliance: review land tenure implications, transaction tax obligations, and Uganda data protection requirements.

## Phase Plan (6-9 Months)

### Phase 1: Setup & Authentication (Months 1-2)
**Objective**
Establish a stable foundation for secure user onboarding and role-aware access.

**Deliverables**
- Laravel API + Vue SPA baseline wired end-to-end.
- Fortify auth flows and Sanctum-protected API sessions.
- Role model (buyer/seller/agent/admin) with policy guards.
- Core data schema and seed data for Ugandan districts/cities.

**Success Metrics**
- 95%+ pass rate on auth/role regression checks.
- Login/signup/reset flows complete end-to-end with no blocker defects.
- First admin can create and manage role-bound accounts reliably.

### Phase 2: Core Features (Months 3-4)
**Objective**
Deliver MVP value for listing, discovery, and contact between users.

**Deliverables**
- Listing CRUD with validated forms and image uploads.
- Search and filtering by location, property type, and price bands.
- Property detail pages and in-app messaging between interested parties.
- Admin moderation queue for listing review and basic abuse reports.

**Success Metrics**
- Listing publish -> discover -> message flow works without manual intervention.
- Search response times stay within acceptable UX threshold for seeded volume.
- Week-over-week growth in active listings and message initiation rate.

### Phase 3: Uganda-Specific Features (Months 5-6)
**Objective**
Localize trust, payments, and reliability for Ugandan market realities.

**Deliverables**
- EN/LG localization coverage across high-traffic user journeys.
- UGX-first pricing + location intelligence improvements (district/city quality checks).
- MTN MoMo/Airtel Money integration MVP, bank transfer workflow definition.
- PWA/offline enhancements, aggressive image optimization, lazy loading rollout.

**Success Metrics**
- Localized journeys cover key funnels (auth, listing, search, contact).
- Payment intent and completion tracking operational for supported methods.
- Measurable drop in bounce rates for low-bandwidth sessions.

### Phase 4: Deployment & Scale (Months 7-9)
**Objective**
Operate reliably in production and scale safely with stronger trust controls.

**Deliverables**
- Production deployment hardening, monitoring, alerting, backup/restore drills.
- Search and API performance tuning, queue/background job reliability.
- Trust/safety upgrades: stronger verification, repeat offender controls, audit trails.
- Ops playbooks for support, incident handling, and release cadence.

**Success Metrics**
- Service availability and latency targets met at higher traffic.
- Faster incident detection and recovery (defined response SLAs achieved).
- Lower fraud/report resolution times and improved user trust signals.

## Challenges and Mitigations
- Unstable internet: prioritize offline/PWA, retry-safe actions, and graceful degraded UX.
- Mobile-first UX pressure: design for low-end devices first, optimize payloads and interactions.
- Local payment method complexity: phased rollout with reconciliation and fallback paths.
- Data accuracy: enforce listing validation, moderation workflows, and periodic data audits.
- Trust/safety risk: verification tiers, report handling SLAs, and transparent policy enforcement.

## Next Steps
1. Validate roadmap assumptions with local agents and target users.
2. Run a competitor scan (local and regional property platforms).
3. Lock MVP scope for Phases 1-2 before expanding feature breadth.
4. Start legal/compliance diligence (land, tax, data protection, payments).
