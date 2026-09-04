# Decision-Delta — Front-End Technical To-Do List

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

---

## Phase 1 — Skeleton

- [ ] Next.js App Router project set up (`app/` structure)
- [ ] Install core deps: `react-hook-form`, `zod`, `@hookform/resolvers`, `sonner`, `swr`
- [ ] `apiFetch` wrapper (fetch + base URL + auth header + error handling)
- [ ] Root layout with nav: Daily Entry, Dashboard, Settings, Activity Log
- [ ] Route structure:
  - [ ] `app/entry/page.tsx`
  - [ ] `app/dashboard/page.tsx`
  - [ ] `app/settings/page.tsx`
  - [ ] `app/events/page.tsx`
- [ ] Auth/session handling (store token, read current user + role)
- [ ] `middleware.ts` — role-based route protection
- [ ] `useRole()` hook — expose current user's role to components
- [ ] `<Toaster />` (sonner) mounted in root layout

---

## Phase 2 — Core data entry (basic save, no live calc yet)

**Sales**
- [ ] `OpportunityList` component — table of opportunities from SWR
- [ ] `OpportunityForm` — react-hook-form + zod schema (customer, product, qty, unit price, discount %, due date, stage)
- [ ] Stage enum: `New / Quoted / Won / Lost`
- [ ] POST/PATCH call on submit → `mutate()` list on success
- [ ] Price/discount interdependency logic (recompute one when other changes)

**Operations**
- [ ] `InventoryAdjustmentForm` (product, new quantity, reason)
- [ ] `CapacityAdjustmentForm` (date, available hours, reason)
- [ ] Submit handlers wired to API + SWR `mutate()`

**Finance**
- [ ] `ReceiptForm` (amount, date)
- [ ] `PaymentForm` (amount, date)
- [ ] `ExpenseForm` (amount, date, description)
- [ ] Submit handlers wired to API + SWR `mutate()`

- [ ] Zod validation rules for required fields, disallowed negatives, valid dates
- [ ] Save blocked only on structural validation errors (not on warnings)

---

## Phase 3 — Live preview (pre-save calculations)

- [ ] `useLiveEffect()` hook — pure client-side calc, no network call
- [ ] Watch form fields via `watch()` (react-hook-form) to trigger recompute on every keystroke
- [ ] Sales live panel:
  - [ ] Revenue = qty × unit price
  - [ ] Cost of goods = qty × unit cost
  - [ ] Shipping cost = fixed + (per-unit × qty)
  - [ ] Operating profit = revenue − COGS − shipping
  - [ ] Margin % = profit ÷ revenue × 100
  - [ ] Install hours = qty × install-min-per-unit ÷ 60
  - [ ] Remaining inventory = free inventory − qty
  - [ ] Remaining capacity calculation
  - [ ] Feasibility status: `Feasible / Conditional / Not feasible`
  - [ ] New warnings preview (info/risk/critical)
- [ ] Finance live panel:
  - [ ] Current cash balance
  - [ ] Balance after this change
  - [ ] Receipts/payments due in next 7 days
  - [ ] Lowest forecasted balance over 7 days
  - [ ] Cash-shortage warning
- [ ] Operations pre-save impact view (which opportunities/orders are affected by this change)
- [ ] All settings-derived values (target margin, min cash, shipping cost, thresholds) pulled from Settings data, not hardcoded

---

## Phase 4 — Cross-screen live updates

- [ ] SWR `refreshInterval` set on all shared data (opportunities, inventory, capacity, cash, alerts, dashboard KPIs)
- [ ] Confirm dashboard reflects a save made in Entry screen without manual refresh
- [ ] Confirm marking a sale "Won" cascades: inventory reserved, capacity reserved, dependent alerts recalculated
- [ ] Confirm Operations/Finance changes cascade into Sales feasibility status
- [ ] Sanity check: no page requires a manual browser refresh to show current data

---

## Phase 5 — Dashboard & Alert Center

- [ ] `DashboardSummary` component — revenue, profit, cash, open opportunities, at-risk orders, critical alert count, stock levels, capacity utilization %
- [ ] `AlertCenter` component — list of alerts (title, severity, cause, effect, suggested fix)
- [ ] Severity badge styling: info / risk / critical (distinct colors)
- [ ] Data fetched via SWR with polling, matching Phase 4 setup

---

## Phase 6 — Settings screen (Admin only)

- [ ] Route-gated to Admin role only (middleware + `useRole()`)
- [ ] `ProductSettingsForm` (name, base price, unit cost, safety stock, install time)
- [ ] `FinancialRulesForm` (target margin, min operating cash, fixed shipping, per-unit shipping)
- [ ] `CapacitySettingsForm` (available capacity, info/risk/critical thresholds)
- [ ] Alert enable/disable toggles
- [ ] Save triggers global SWR revalidation so every screen recalculates immediately
- [ ] Confirm changing a setting (e.g. target margin) updates opportunity statuses/alerts app-wide without refresh

---

## Phase 7 — Activity Log

- [ ] `EventsList` component — timestamp, change type, entity, before, after, reason, alert generated
- [ ] Read-only list, no edit/delete actions exposed in UI
- [ ] Fetched via SWR with polling (new events appear without refresh)

---

## Phase 8 — Polish

- [ ] Loading states for all forms/lists
- [ ] Empty states (no opportunities yet, no alerts, etc.)
- [ ] Error states (failed save, failed fetch) surfaced via `sonner` toasts
- [ ] Consistent severity color coding across Alert Center, dashboard, and inline warnings
- [ ] Form UX pass — tab order, sensible defaults, fast to fill under time pressure
- [ ] Final check: every "must be true across the whole front-end" behavior from the feature doc still holds