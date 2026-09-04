# Decision-Delta — Back-End (Laravel) Technical To-Do List — MVP

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

One dashboard per role — Sales, Operations, Finance, Admin. Each
phase is broken into small, single-action steps so every checkbox is
a quick win you can tick off and move on.

---

## Phase 1 — Sales Dashboard

**Setup**
- [X] Seed 5 users: user@example.com (no role), plus one each for sales, operations, finance, and admin
- [X] Build signup endpoint with optional non-admin role assignment
- [X] Build login endpoint returning a Sanctum token

**Products**
- [X] Create `products` migration
- [X] Create `Product` model
- [X] Seed 3 products

**Customers**
- [X] Create `customers` migration
- [X] Create `Customer` model
- [X] Seed 5 customers

**Opportunities**
- [X] Create `opportunities` migration (product_id, qty, unit_price, due_date, stage)
- [X] Create `Opportunity` model
- [X] Seed 5 opportunities (product only, no customer, no discount)
- [X] Build `GET /api/opportunities`
- [X] Build `POST /api/opportunities`
- [X] Build `PATCH /api/opportunities/{id}`
- [X] Add validation: required fields
- [X] Add validation: no negative quantity
- [X] Add validation: valid due date
- [X] Add revenue calculation to response
- [X] Add profit/margin calculation to response
- [X] Add "Won" stage → reduce product's free inventory

**Access**
- [X] Protect opportunity routes with `role:sales`

**Verify**
- [X] Log in as sales user and confirm list/create/update all work

---

## Phase 2 — Operations Dashboard

- [X] Create `inventory_adjustments` migration + model
- [X] Build `POST /api/inventory-adjustments`
- [X] Add validation (required fields, valid quantity)
- [X] Create `capacity_adjustments` migration + model
- [X] Build `POST /api/capacity-adjustments`
- [X] Add validation
- [X] Protect routes with `role:operations`
- [X] **Verify:** log in as operations user, adjust inventory, confirm it updates the product record

---

## Phase 3 — Finance Dashboard

**Implementation plan**
1. Add append-only receipt, payment, and expense tables with a shared
   `amount`, `date`, and `user_id` audit shape; expenses also require a
   description.
2. Add typed Eloquent models with decimal amount/date casts and the
   existing user relationship convention.
3. Add dedicated Form Requests so all finance writes enforce required
   fields, non-negative numeric amounts, ISO dates, and the expense
   description length.
4. Add a focused Finance API controller for the three create actions and
   one cash summary aggregate. The summary returns receipts, payments,
   expenses, and `current_balance = receipts - payments - expenses`.
5. Register the endpoints behind the existing Sanctum and
   `role:finance` middleware group.
6. Add feature coverage for role protection, validation, persistence,
   and the cash calculation across all three entry types.

**Build status**
- [X] Create `receipts` migration + model
- [X] Build `POST /api/receipts`
- [X] Create `payments` migration + model
- [X] Build `POST /api/payments`
- [X] Create `expenses` migration + model
- [X] Build `POST /api/expenses`
- [X] Add validation to all three (required fields, valid amount/date)
- [X] Build `GET /api/cash-summary` (current balance = receipts − payments − expenses)
- [X] Protect routes with `role:finance`
- [~] **Verify:** feature coverage is implemented and PHP syntax checks pass; full Laravel tests require the missing `back-end/vendor` dependencies

---

## Phase 4 — Admin Dashboard

**Implementation plan**
1. Create a typed key/value `settings` table so financial, capacity, and
   alert rules can be changed without code edits.
2. Add a `Setting` model with one canonical definition for supported keys,
   types, defaults, and validation ranges.
3. Seed the initial target margin, minimum cash, shipping costs, capacity
   thresholds, and alert toggles through an idempotent settings seeder.
4. Add admin-only `GET /api/settings` and `PATCH /api/settings` endpoints.
   Updates accept a `settings` object, reject unknown keys, and validate
   each value according to its setting definition.
5. Add admin-only `GET /api/dashboard`, aggregating won revenue/profit,
   cash, open opportunities, product inventory, at-risk opportunities,
   and critical alert counts using the current settings.
6. Add feature coverage for role protection, settings validation and
   persistence, seeded defaults, and dashboard calculations.

**API contract**
- `GET /api/settings` returns `{ settings: { key: typed_value, ... } }`.
- `PATCH /api/settings` accepts `{ settings: { key: new_typed_value, ... } }`.
- `GET /api/dashboard` returns `dashboard` KPIs plus inventory,
  at-risk opportunities, and alert counts.

**Build status**
- [X] Create `settings` migration + model
- [X] Seed initial settings (target margin, min cash, shipping costs, thresholds)
- [X] Build `GET /api/settings`
- [X] Build `PATCH /api/settings`
- [X] Protect settings routes with `role:admin`
- [X] Build `GET /api/dashboard` (pull totals from opportunities, cash, and inventory)
- [X] **Verify:** admin feature coverage and full backend suite pass; Laravel emits existing `.env` file warnings

---

## Later (only if time remains)
- [ ] Alerts (info/risk/critical) generated automatically
- [ ] Full recalculation cascade on every save
- [ ] Move calculation thresholds from hardcoded to reading `settings`
- [ ] Activity/events log
- [ ] Consistent API response formatting