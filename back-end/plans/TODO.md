# Decision-Delta — Back-End (Laravel) Technical To-Do List

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

---

## Phase 1 — Skeleton & Auth

- [X] `composer create-project laravel/laravel backend`
- [X] `composer require laravel/sanctum`
- [X] `php artisan install:api`
- [X] `php artisan migrate`
- [ ] CORS config allowing the Next.js frontend origin
- [ ] `role` column added to `users` table (`sales / operations / finance / admin`)
- [ ] Login endpoint issuing a Sanctum token
- [ ] `auth:sanctum` middleware applied to protected API routes
- [ ] Role-check middleware or policy base (gate per role)

---

## Phase 2 — Data models & migrations

**Core entities**

- [ ] `products` (name, base_price, unit_cost, physical_inventory, reserved_inventory, safety_stock, install_minutes_per_unit)
- [ ] `customers` (name, contact info)
- [ ] `opportunities` (customer_id, product_id, qty, unit_price, discount_percent, due_date, stage, analytical_status)
- [ ] `orders` (firm/won opportunities — or a `stage = won` flag on opportunities, whichever is simpler)
- [ ] `inventory_adjustments` (product_id, new_quantity, reason, user_id, created_at)
- [ ] `capacity_adjustments` (date, available_hours, reason, user_id, created_at)
- [ ] `install_capacity` (date, total_hours, reserved_hours)
- [ ] `receipts` (amount, date)
- [ ] `payments` (amount, date)
- [ ] `expenses` (amount, date, description)
- [ ] `cash_balance` (current running total, or derive from receipts − payments − expenses)
- [ ] `settings` (single-row or key/value table: target_margin, min_operating_cash, fixed_shipping, per_unit_shipping, capacity thresholds, alert toggles)
- [ ] `alerts` (title, severity, cause, effect, suggestion, entity reference, active/resolved)
- [ ] `events` (timestamp, change_type, entity, value_before, value_after, reason, user_id, alert_generated) — insert-only, no update/delete

- [ ] Seed data migration/seeder: 3 products, 5 customers, 5 opportunities, 3 firm orders, initial inventory/capacity/cash/settings (matching the brief's seed table)

---

## Phase 3 — Calculation engine (shared logic)

- [ ] `CalculationService` (or similar) — single source of truth for all formulas, used by every endpoint that needs them
    - [ ] Revenue = qty × unit price
    - [ ] Cost of goods = qty × unit cost
    - [ ] Shipping cost = fixed shipping + (per-unit shipping × qty)
    - [ ] Install hours = qty × install-minutes-per-unit ÷ 60
    - [ ] Operating profit = revenue − COGS − shipping
    - [ ] Margin % = operating profit ÷ revenue × 100
    - [ ] Free inventory = physical − reserved
- [ ] `FeasibilityService` — determines `Feasible / Conditional / Not feasible` per opportunity, reading thresholds from Settings (never hardcoded)
- [ ] `AlertService` — generates/updates alerts (info/risk/critical) based on inventory, capacity, cash, and margin thresholds from Settings
- [ ] All services read **all** thresholds/rules from the `settings` table, nothing hardcoded in service classes

---

## Phase 4 — Sales endpoints

- [ ] `GET /api/opportunities` — list with computed status + alert severity
- [ ] `POST /api/opportunities` — create (Form Request validation: required fields, no negative qty, valid due date)
- [ ] `PATCH /api/opportunities/{id}` — update, including stage transitions
- [ ] Marking stage = `Won` → reserve inventory + capacity, convert to firm commitment, trigger recalculation (Phase 6)
- [ ] `POST /api/opportunities/preview` (optional) — server-side echo of the same formula for consistency checks; not required for live UI since frontend computes it client-side, but useful if you want one source of truth

---

## Phase 5 — Operations & Finance endpoints

**Operations**

- [ ] `POST /api/inventory-adjustments` — validate + apply + return affected opportunities/orders
- [ ] `POST /api/capacity-adjustments` — validate + apply + return affected opportunities/orders

**Finance**

- [ ] `POST /api/receipts`
- [ ] `POST /api/payments`
- [ ] `POST /api/expenses`
- [ ] `GET /api/cash-summary` — current balance, post-change balance, next-7-days receipts/payments, lowest forecasted balance, shortage warning

- [ ] Form Request validation on all of the above: required fields, disallowed negatives, valid dates — this is the **only** thing allowed to block a save

---

## Phase 6 — Recalculation & cascading effects

- [ ] `RecalculationService` — re-runs Calculation/Feasibility/Alert services across all affected records after any save
- [ ] Triggered on: opportunity save, inventory adjustment, capacity adjustment, receipt/payment/expense, settings change
- [ ] Save cycle implemented as: save → update real state → recalculate → update alerts/indicators → write event (Phase 8)
- [ ] Confirm a settings change (e.g. target margin) cascades to every open opportunity's status and alerts

---

## Phase 7 — Dashboard, Alert Center & Settings endpoints

- [ ] `GET /api/dashboard` — firm revenue, operating profit, cash, open opportunities, at-risk orders, critical alert count, product inventory, capacity utilization %
- [ ] `GET /api/alerts` — active alerts with title, severity, cause, effect, suggestion
- [ ] `GET /api/settings` — current settings values
- [ ] `PATCH /api/settings` (Admin-only, role-gated) — update settings, triggers Phase 6 recalculation

---

## Phase 8 — Events log

- [ ] `GET /api/events` — paginated, most recent first
- [ ] Event write hooked into every save path (opportunity, inventory, capacity, receipt, payment, expense, settings)
- [ ] No update or delete route exposed for events — insert-only by design

---

## Phase 9 — Polish & validation pass

- [ ] Form Request classes cover every documented validation rule (required fields, negative-value rules, date validity)
- [ ] Consistent JSON error responses for validation failures (so frontend `sonner` toasts have something clean to show)
- [ ] `JsonResource` classes for consistent API response shapes across all endpoints
- [ ] Sanity check: no endpoint depends on an external service, paid API, or cloud dependency
- [ ] Sanity check: no business number (margin target, min cash, shipping cost, thresholds) is hardcoded anywhere outside the `settings` table
- [ ] Seed data verified against the brief's exact seed table (products, customers, opportunities, orders)
