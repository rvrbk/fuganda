# Sprint 1 Backlog

| ID | Item | Owner Role | Status | Notes |
|---|---|---|---|---|
| S1-01 | Environment and baseline hardening | Lead / Backend | todo | Validate install, build, and test bootstrap for repo baseline. |
| S1-02 | Fortify authentication flow completion | Backend | todo | Signup/login/logout/reset flows with feature tests. |
| S1-03 | Sanctum SPA session auth wiring | Backend + Frontend | todo | SPA authenticated calls and 401 handling contracts. |
| S1-04 | Role-based auth foundation (buyer/seller/agent/admin) | Backend | done | Added user role migration/model/factory updates and auth role tests. |
| S1-05 | Core user and role seed data | Backend | done | Added repeatable seeded buyer/seller/agent/admin users and feature test coverage. |
| S1-06 | Uganda district/city reference dataset seed | Backend | done | Expanded MVP Uganda district/city seed list and added API/seed feature tests. |
| S1-07 | Property domain schema baseline | Backend | in-progress | Minimum tables/relations for listing + images under a user-first ownership model (seller owns multiple properties, no tenant context required). |
| S1-08 | Read-only listing API with filters | Backend | todo | List endpoint with location/type/price filters and pagination. |
| S1-09 | Vue listing shell and auth state store wiring | Frontend | todo | Properties list view integration with API and session state. |
| S1-10 | Test and QA contract baseline | QA / Tester | todo | Auth + listing contracts in `qa/contracts` with automated checks. |
