# Decision-Delta — Back-End (Laravel) Technical To-Do List — MVP

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

One dashboard per role — Sales, Operations, Finance, Admin. Each
phase is broken into small, single-action steps so every checkbox is
a quick win you can tick off and move on.

---

## Phase 1 — Sales Dashboard

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
- [X] Build `PATCH /api/opportunities/{id}``
- [X] Add validation: required fields
- [X] Add validation: no negative quantity
- [X] Add validation: valid due date
- [X] Add revenue calculation to response
- [X] Add profit/margin calculation to response
- [X] Add "Won" stage → reduce product's free inventory

**Access**
- [X] Protect opportunity routes with `role:sales`

**Verify**
- [ ] Log in as sales user and confirm list/create/update all work

---

## Phase 2 — Operations Dashboard

- [ ] Create `inventory_adjustments` migration + model
- [ ] Build `POST /api/inventory-adjustments`
- [ ] Add validation (required fields, valid quantity)
- [ ] Create `capacity_adjustments` migration + model
- [ ] Build `POST /api/capacity-adjustments`
- [ ] Add validation
- [ ] Protect routes with `role:operations`
- [ ] **Verify:** log in as operations user, adjust inventory, confirm it updates the product record

---

## Phase 3 — Finance Dashboard

- [ ] Create `receipts` migration + model
- [ ] Build `POST /api/receipts`
- [ ] Create `payments` migration + model
- [ ] Build `POST /api/payments`
- [ ] Create `expenses` migration + model
- [ ] Build `POST /api/expenses`
- [ ] Add validation to all three (required fields, valid amount/date)
- [ ] Build `GET /api/cash-summary` (current balance = receipts − payments − expenses)
- [ ] Protect routes with `role:finance`
- [ ] **Verify:** log in as finance user, create one of each entry, confirm cash summary total is correct

---

## Phase 4 — Admin Dashboard

- [ ] Create `settings` migration + model
- [ ] Seed initial settings (target margin, min cash, shipping costs, thresholds)
- [ ] Build `GET /api/settings`
- [ ] Build `PATCH /api/settings`
- [ ] Protect settings routes with `role:admin`
- [ ] Build `GET /api/dashboard` (pull totals from opportunities, cash, inventory)
- [ ] **Verify:** log in as admin, view dashboard numbers, update a setting, confirm it saves

---

## Later (only if time remains)
- [ ] Alerts (info/risk/critical) generated automatically
- [ ] Full recalculation cascade on every save
- [ ] Move calculation thresholds from hardcoded to reading `settings`
- [ ] Activity/events log
- [ ] Consistent API response formatting