# Sprint 1 Plan (2 Weeks)

## 1) Sprint Goal
Deliver a stable Phase 1 foundation for setup and authentication (Fortify + Sanctum + role-based access), plus the minimum Phase 2 enablers needed to start property flows in Sprint 2: property schema baseline, read-only listing API, SPA listing shell, and contract tests.

## 2) Scope In
Backlog items for this sprint (implementation-ready):

- S1-01: Environment and baseline hardening
  - Repo areas: Laravel API (`config/*`, `bootstrap/*`), Vue SPA (`resources/js/*`), CI/test config (`phpunit.xml`, `playwright.config.ts`)
  - Done when: local install/build/test commands are documented and pass on a clean machine.

- S1-02: Fortify authentication flow completion
  - Repo areas: Laravel API (`app/Actions/Fortify/*`, `app/Providers/FortifyServiceProvider.php`, `config/fortify.php`, `routes/web.php`)
  - Done when: signup, login, logout, reset-password paths pass feature tests.

- Platform context note: Sprint 1 assumes a single-platform, user-first model with no mandatory tenant/agency layer in auth or listing flows.

- S1-03: Sanctum SPA session auth wiring
  - Repo areas: Laravel API (`config/sanctum.php`, `routes/api.php`, auth middleware), Vue SPA (`resources/js` auth client)
  - Done when: authenticated SPA requests succeed and unauthenticated requests return 401.

- S1-04: Role-based auth foundation (buyer/seller/agent/admin)
  - Repo areas: Laravel API (`app/Models/User.php`, policies/gates in `app/Providers/AppServiceProvider.php`, role middleware)
  - Done when: role checks guard protected endpoints and tests cover allow/deny cases.

- S1-05: Core user and role seed data
  - Repo areas: migrations/seeders (`database/migrations/*`, `database/seeders/*`)
  - Done when: seeded users exist for all four roles and are usable in tests.

- S1-06: Uganda district/city reference dataset seed
  - Repo areas: schema + seeders (`database/migrations/*`, `database/seeders/*`, `app/Models/Location.php`)
  - Done when: district/city records are queryable via API and used by validation.

- S1-07: Minimum Phase 2 enabler - property domain schema baseline
  - Repo areas: `app/Models/Property.php`, `database/migrations/*` for properties/images, `app/Models/PropertyImage.php`
  - Done when: property tables support title, type, status, price, location, owner, and image metadata.

- S1-08: Minimum Phase 2 enabler - read-only listing API and filters
  - Repo areas: Laravel API (`app/Http/Controllers/*`, `app/Services/PropertySearchService.php`, `routes/api.php`, `app/Http/Requests/*`)
  - Done when: list endpoint supports location/type/price filters and pagination with validation.

- S1-09: Minimum Phase 2 enabler - Vue listing shell and auth state store
  - Repo areas: Vue SPA (`resources/js/views/PropertiesListView.vue`, router/store modules in `resources/js/*`)
  - Done when: SPA renders listing data from API and handles auth/session state transitions.

- S1-10: Test and QA contract baseline
  - Repo areas: API tests (`tests/Feature/*`, `tests/Unit/*`), E2E (`tests/e2e/*`), QA contracts (`qa/contracts/*`, `qa/checks/*`)
  - Done when: auth and listing contracts are defined and passing in local run.

## 3) Scope Out
- Messaging workflows and inbox UX.
- Listing create/update/delete UI and moderation queue.
- Payment integrations (MTN MoMo/Airtel Money/bank flow).
- Full localization rollout (EN/LG copy coverage).
- PWA/offline and advanced performance tuning.

## 4) Definition of Done
- Code merged with passing unit/feature/E2E tests for touched areas.
- API contracts updated for new/changed endpoints and validated by QA checks.
- Role and auth behavior verified for both happy-path and deny-path scenarios.
- Seed data reproducible and usable for local/dev test environments.
- No blocker/high defects open for sprint scope items.

## 5) Risks and Mitigations
- Risk: Fortify/Sanctum integration regressions.
  - Mitigation: lock contract tests early (S1-10) and run auth regression daily.
- Risk: role matrix ambiguity causing inconsistent authorization.
  - Mitigation: define endpoint-to-role matrix in tests before implementation completion.
- Risk: dataset quality for districts/cities.
  - Mitigation: seed validation checks and strict input constraints.
- Risk: API/SPA contract drift.
  - Mitigation: maintain QA contract files and wire them into CI checks.

## 6) Metrics to track this sprint
- Auth flow pass rate (target: >=95% for signup/login/reset/guard tests).
- Role-guard test coverage (target: all protected endpoints have allow/deny coverage).
- API contract pass rate for auth + listing endpoints (target: 100%).
- Defect trend by severity in sprint scope (target: 0 blocker, <=2 high by close).
- Sprint throughput: completed backlog items / committed items.

## 7) Day-by-day execution plan (10 working days)
- Day 1: Sprint kickoff, finalize endpoint-role matrix, confirm acceptance criteria for S1-01..S1-10.
- Day 2: Complete S1-01 baseline hardening; start S1-02 Fortify fixes and tests.
- Day 3: Finish S1-02; implement S1-03 Sanctum SPA session wiring.
- Day 4: Continue S1-03 and push S1-04 role-based auth foundation (in-progress).
- Day 5: Close S1-04 with allow/deny tests; start S1-05 seed users/roles.
- Day 6: Complete S1-05 and S1-06 district/city seed + validation checks.
- Day 7: Implement S1-07 property schema baseline and migration verification.
- Day 8: Implement S1-08 read-only listing API with filters + request validation.
- Day 9: Implement S1-09 SPA listing shell + auth state handling; begin contract verification.
- Day 10: Complete S1-10 QA contracts and full regression run, defect cleanup, sprint review.
