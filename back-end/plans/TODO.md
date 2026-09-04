# Decision-Delta — Back-End (Laravel) Technical To-Do List

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

Organized **feature by feature** — each phase takes one feature all
the way from database to a testable endpoint, so you can verify it
manually (Postman/Insomnia/browser) before moving to the next one,
instead of building all models first and only testing at the end.

---

## Phase 0 — Project setup & Auth
*(one-time foundation, needed before any feature can be tested)*

- [ ] `composer create-project laravel/laravel backend`
- [ ] `composer require laravel/sanctum`
- [ ] `php artisan install:api`
- [ ] `php artisan migrate`
- [ ] CORS config allowing the Next.js frontend origin
- [ ] `composer require spatie/laravel-permission`
- [ ] `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] `php artisan migrate` (creates `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`)
- [ ] Seeder: create the 4 roles (`sales`, `operations`, `finance`, `admin`) + their permissions
- [ ] Login endpoint issuing a Sanctum token
- [ ] `auth:sanctum` applied to API routes
- [ ] Route-level role middleware (Spatie's `role:admin` etc.) available for use in later phases
- [ ] **Verify:** log in via Postman, get a token, assign a role to a test user, confirm `$user->hasRole()` / a `role:`-protected dummy route behaves correctly

---

## Phase 1 — Products
*(needed by almost every other feature, so it goes first)*

- [X] Migration + model: `products` (name, base_price, unit_cost, physical_inventory, reserved_inventory, safety_stock, install_minutes_per_unit)
- [ ] Seeder: the 3 products from the brief's seed table
- [ ] `GET /api/products` — list
- [ ] `GET /api/products/{id}` — single
- [ ] **Verify:** call `GET /api/products`, confirm the 3 seeded products come back correctly

---

## Phase 2 — Customers

- [ ] Migration + model: `customers` (name, contact info)
- [ ] Seeder: the 5 customers from the brief
- [ ] `GET /api/customers`
- [ ] **Verify:** call `GET /api/customers`, confirm 5 records

---

## Phase 3 — Sales Opportunities
*(the core feature — build it fully, one entity at a time, and test each piece)*

- [ ] Migration + model: `opportunities` (customer_id, product_id, qty, unit_price, discount_percent, due_date, stage, analytical_status)
- [ ] Seeder: the 5 opportunities from the brief
- [ ] `GET /api/opportunities` — list, plain (no calculated fields yet)
- [ ] **Verify:** confirm the 5 seeded opportunities return correctly
- [ ] `POST /api/opportunities` — create, with Form Request validation (required fields, no negative qty, valid due date)
- [ ] **Verify:** create one via Postman, confirm it's saved and rejected correctly when a required field is missing
- [ ] `PATCH /api/opportunities/{id}` — update, including stage changes (New → Quoted → Won → Lost)
- [ ] **Verify:** update stage on one record, confirm it persists
- [ ] `CalculationService` — revenue, cost of goods, shipping, install hours, operating profit, margin %
- [ ] Wire calculated fields into the `GET`/`POST` responses
- [ ] **Verify:** create/update an opportunity, confirm the returned numbers match the brief's formulas by hand
- [ ] `FeasibilityService` — `Feasible / Conditional / Not feasible`, thresholds read from `settings` (stub settings table if Phase 6 isn't built yet)
- [ ] **Verify:** change quantity past available stock, confirm status flips correctly
- [ ] Marking stage = `Won` → reserve inventory + install capacity on the product/capacity records
- [ ] **Verify:** mark an opportunity "Won", confirm product's reserved inventory increases

---

## Phase 4 — Operations (Inventory & Capacity)

- [ ] Migration + model: `inventory_adjustments` (product_id, new_quantity, reason)
- [ ] `POST /api/inventory-adjustments` — validate, apply, return list of affected opportunities
- [ ] **Verify:** adjust a product's inventory down, confirm affected opportunities in the response, and confirm the product record updated
- [ ] Migration + model: `install_capacity` + `capacity_adjustments` (date, available_hours, reason)
- [ ] `POST /api/capacity-adjustments` — validate, apply, return affected opportunities/orders
- [ ] **Verify:** adjust capacity for a date, confirm affected records and updated capacity value

---

## Phase 5 — Finance (Receipts, Payments, Expenses)

- [ ] Migrations + models: `receipts`, `payments`, `expenses`
- [ ] `POST /api/receipts` — validate + save
- [ ] **Verify:** create a receipt, confirm it's saved
- [ ] `POST /api/payments` — validate + save
- [ ] **Verify:** create a payment, confirm it's saved
- [ ] `POST /api/expenses` — validate + save (amount, date, description)
- [ ] **Verify:** create an expense, confirm it's saved
- [ ] `GET /api/cash-summary` — current balance, post-change balance, next-7-days receipts/payments, lowest forecasted balance, shortage warning
- [ ] **Verify:** with seeded + created records, confirm the 7-day forecast and shortage warning match manual math

---

## Phase 6 — Settings (Admin only)

- [ ] Migration + model: `settings` (target_margin, min_operating_cash, fixed_shipping, per_unit_shipping, capacity thresholds, alert toggles)
- [ ] Seeder: initial settings values from the brief
- [ ] `GET /api/settings`
- [ ] **Verify:** confirm seeded values return correctly
- [ ] `PATCH /api/settings` — Admin-role-gated update
- [ ] **Verify:** try updating as a non-admin (should be rejected), then as admin (should succeed)
- [ ] Wire `FeasibilityService`/`CalculationService` (Phase 3) to actually read from this table instead of any stubbed values
- [ ] **Verify:** change target margin, re-fetch an opportunity, confirm its status/margin reflects the new setting

---

## Phase 7 — Alerts & Recalculation Cascade

- [ ] Migration + model: `alerts` (title, severity, cause, effect, suggestion, entity reference, active/resolved)
- [ ] `AlertService` — generates/updates alerts from inventory, capacity, cash, and margin thresholds
- [ ] `RecalculationService` — re-runs calculation/feasibility/alerts across affected records after any save
- [ ] Hook `RecalculationService` into: opportunity save, inventory adjustment, capacity adjustment, finance entries, settings update
- [ ] `GET /api/alerts` — active alerts
- [ ] **Verify:** trigger a known shortage (e.g. push qty past inventory), confirm the matching critical alert appears in `GET /api/alerts`
- [ ] **Verify:** change a setting (Phase 6), confirm alerts across multiple opportunities update accordingly

---

## Phase 8 — Dashboard

- [ ] `GET /api/dashboard` — firm revenue, operating profit, cash, open opportunities, at-risk orders, critical alert count, product inventory, capacity utilization %
- [ ] **Verify:** cross-check each number against the underlying records by hand

---

## Phase 9 — Events Log

- [ ] Migration + model: `events` (timestamp, change_type, entity, value_before, value_after, reason, user_id, alert_generated) — insert-only
- [ ] Event write hooked into every save path built in Phases 3–6
- [ ] `GET /api/events` — paginated, most recent first
- [ ] **Verify:** perform a save in each feature area, confirm a matching event appears; confirm no update/delete route exists for events

---

## Phase 10 — Final polish pass

- [ ] `JsonResource` classes for consistent response shapes across all endpoints
- [ ] Consistent validation error format across all Form Requests
- [ ] Sanity check: no business number is hardcoded anywhere outside the `settings` table
- [ ] Sanity check: no endpoint depends on an external/paid/cloud service
- [ ] Full manual run-through: create a sale → mark it Won → adjust inventory → change a setting → confirm dashboard, alerts, and events all reflect it correctly end-to-end