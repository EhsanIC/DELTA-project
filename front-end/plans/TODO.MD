# Decision-Delta — Front-End (Next.js) Technical To-Do List — MVP

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

One dashboard per role — Sales, Operations, Finance, Admin — mirroring
the back-end phases. Same shadcn/ui components reused across all four,
just different data/forms per role.

---

## Phase 1 — Sales Dashboard

**Setup**
- [X] Install deps: `react-hook-form`, `zod`, `@hookform/resolvers`, `sonner`, `swr`
- [X] `npx shadcn@latest init`
- [~] Add shadcn components: `button`, `input`, `label`, `form`, `select`, `table`, `sonner` (base components exist; form/select/table/sonner are still missing)
- [ ] `apiFetch` wrapper (base URL + Sanctum token header)
- [~] Signup page — calls `POST /api/signup` (signup page/form shell exists, but it does not call the backend)
- [~] Login page — calls login endpoint, stores token (login page/form shell exists, but it does not call the backend)
- [ ] `useRole()` hook — reads role from logged-in user
- [ ] Route protection so `/sales` only loads for `role:sales`

**Products (read-only for Sales)**
- [ ] Fetch product list via SWR (needed for the opportunity form's product dropdown)

**Customers (read-only for Sales)**
- [ ] Fetch customer list via SWR (needed for the opportunity form's customer dropdown)

**Opportunities**
- [ ] `OpportunityList` — table via SWR: product, customer, qty, unit price, due date, stage
- [ ] `OpportunityForm` — react-hook-form + zod (product, customer, qty, unit price, due date, stage)
- [ ] Wire submit → `POST`/`PATCH /api/opportunities` → `mutate()` list → `sonner` toast
- [ ] Show revenue/profit/margin returned by the API on each row (no client-side calc needed — backend already returns it)
- [ ] Stage dropdown includes "Won" — confirm list refreshes after marking Won
- [ ] **Verify:** log in as sales user, create/edit an opportunity, confirm list and calculated fields match the backend

---

## Phase 2 — Operations Dashboard

- [ ] `InventoryAdjustmentForm` — react-hook-form + zod (product, new quantity, reason)
- [ ] Wire submit → `POST /api/inventory-adjustments` → toast
- [ ] `CapacityAdjustmentForm` — react-hook-form + zod (date, available hours, reason)
- [ ] Wire submit → `POST /api/capacity-adjustments` → toast
- [ ] Route protection for `role:operations`
- [ ] **Verify:** log in as operations user, submit both forms, confirm they save without error

---

## Phase 3 — Finance Dashboard

- [~] `ReceiptForm`, `PaymentForm`, `ExpenseForm` — finance page has one native-state form switching between all three entry types, but does not use react-hook-form + zod
- [~] Wire each submit → its `POST` endpoint → toast (all three POST endpoints are wired and refresh the summary; uses inline messages instead of sonner)
- [~] `CashSummary` — fetch `GET /api/cash-summary` via SWR, show current balance (summary is fetched with native fetch/useEffect, not SWR)
- [ ] Route protection for `role:finance`
- [ ] **Verify:** log in as finance user, submit all three forms, confirm cash summary total updates

---

## Phase 4 — Admin Dashboard

- [ ] `SettingsForm` — react-hook-form + zod (target margin, min cash, shipping costs, thresholds)
- [ ] Wire submit → `GET`/`PATCH /api/settings` → toast
- [ ] `DashboardSummary` — fetch `GET /api/dashboard` via SWR, show the returned totals
- [ ] Route protection for `role:admin`
- [ ] **Verify:** log in as admin, view dashboard numbers, update a setting, confirm it saves

---

## Later (only if time remains)
- [~] Live client-side preview panel (calc before save, not just after) (finance cash post-save preview exists; sales/opportunity previews do not)
- [ ] Alert Center UI (info/risk/critical badges)
- [ ] Activity/events log page
- [~] Loading/empty/error state polish (finance page has basic loading and error/message handling; the rest of the app is not covered)
- [ ] SWR polling interval for cross-screen live updates