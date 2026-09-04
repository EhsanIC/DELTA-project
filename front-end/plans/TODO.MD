# Decision-Delta — Front-End Technical To-Do List

`[ ]` not started &nbsp;&nbsp; `[X]` done &nbsp;&nbsp; `[~]` in progress / partial

Organized **feature by feature** — each phase takes one feature all
the way from UI to a connected, working screen, so you can verify it
manually in the browser before moving to the next one, instead of
building all forms first and only testing at the end.

---

## Phase 0 — Project setup

_(one-time foundation, needed before any feature can be tested)_

- [ ] Next.js App Router project set up (`app/` structure)
- [ ] Install core deps: `react-hook-form`, `zod`, `@hookform/resolvers`, `sonner`, `swr`
- [ ] `npx shadcn@latest init` — set up shadcn/ui
- [ ] Add base shadcn components needed early: `button`, `input`, `label`, `form`, `select`, `sonner` (toast)
- [ ] `apiFetch` wrapper (fetch + base URL + auth token header + error handling)
- [ ] Root layout with nav: Daily Entry, Dashboard, Settings, Activity Log
- [ ] Route structure:
    - [ ] `app/entry/page.tsx`
    - [ ] `app/dashboard/page.tsx`
    - [ ] `app/settings/page.tsx`
    - [ ] `app/events/page.tsx`
- [ ] Login page + token storage
- [ ] `useRole()` hook — reads current user's role (from Spatie roles via the backend's `/me` response)
- [ ] `middleware.ts` — role-based route protection
- [ ] `<Toaster />` (sonner) mounted in root layout
- [ ] **Verify:** log in, land on a protected page, confirm a non-admin is blocked from `/settings`

---

## Phase 1 — Sales (Opportunities)

_(the core feature — build the full slice, then add live preview on top, testing each step)_

- [ ] Add shadcn components: `table`, `badge`, `dialog` (or `sheet`), `textarea`
- [ ] `OpportunityList` — table fed by SWR, columns: customer, product, qty, amount, discount, due date, stage, status, alert severity
- [ ] **Verify:** list renders the 5 seeded opportunities from the backend
- [ ] `OpportunityForm` — react-hook-form + zod schema (customer, product, qty, unit price, discount %, due date, stage)
- [ ] Wire submit → API `POST`/`PATCH` → `mutate()` list on success → `sonner` success/error toast
- [ ] **Verify:** create one opportunity via the form, confirm it appears in the list and matches what was saved on the backend
- [ ] Price/discount interdependency (changing one updates the other in the form)
- [ ] **Verify:** confirm the interdependency behaves correctly in the UI
- [ ] `useLiveEffect()` hook — pure client-side calc, watches form fields via `watch()`
- [ ] Live preview panel: revenue, cost, profit, margin, remaining inventory, install hours, remaining capacity, feasibility, new warnings
- [ ] **Verify:** type into the form, confirm the preview updates on every keystroke with no network request, and matches the backend's calculated response after save
- [ ] Stage change to "Won" wired to backend + UI reflects reserved inventory/capacity
- [ ] **Verify:** mark an opportunity "Won", confirm the list/status updates without a manual refresh

---

## Phase 2 — Operations (Inventory & Capacity)

- [ ] `InventoryAdjustmentForm` (product, new quantity, reason) — react-hook-form + zod
- [ ] Pre-save "affected opportunities" preview panel (from backend response or local computation)
- [ ] Wire submit → API → `mutate()` affected data → toast
- [ ] **Verify:** adjust a product's inventory, confirm the affected-opportunities preview shows before save, and confirm Sales list/status updates after save without refresh
- [ ] `CapacityAdjustmentForm` (date, available hours, reason)
- [ ] Wire submit → API → `mutate()` → toast
- [ ] **Verify:** adjust capacity, confirm affected records shown and everything updates after save

---

## Phase 3 — Finance (Receipts, Payments, Expenses)

- [ ] `ReceiptForm`, `PaymentForm`, `ExpenseForm` — react-hook-form + zod, each wired to its API endpoint + toast
- [ ] **Verify:** submit each form, confirm it saves and appears wherever it should
- [ ] Live cash summary panel: current balance, balance after this change, next-7-days in/out, lowest forecasted balance, shortage warning
- [ ] **Verify:** type an amount into a form, confirm the live summary updates instantly and matches manual math

---

## Phase 4 — Dashboard & Alert Center

- [ ] Add shadcn components: `card`, `badge` (severity variants)
- [ ] `DashboardSummary` — revenue, profit, cash, open opportunities, at-risk orders, critical alert count, stock levels, capacity utilization %, fetched via SWR
- [ ] **Verify:** numbers match the underlying data by hand
- [ ] `AlertCenter` — list of alerts (title, severity, cause, effect, suggested fix), color-coded by severity
- [ ] SWR `refreshInterval` set so the dashboard updates without a manual refresh
- [ ] **Verify:** make a change in Sales/Operations/Finance, confirm the dashboard and alerts update on their own within the polling interval

---

## Phase 5 — Settings (Admin only)

- [ ] Route-gated to Admin role (middleware + `useRole()`)
- [ ] `ProductSettingsForm`, `FinancialRulesForm`, `CapacitySettingsForm` — react-hook-form + zod, each wired to the settings API
- [ ] Alert enable/disable toggles (shadcn `switch`)
- [ ] Save triggers global SWR revalidation
- [ ] **Verify:** log in as non-admin, confirm the page/route is blocked; as admin, change a setting (e.g. target margin) and confirm Sales statuses/alerts update app-wide without refresh

---

## Phase 6 — Activity Log

- [ ] `EventsList` — timestamp, change type, entity, before, after, reason, alert generated
- [ ] Fetched via SWR with polling, read-only (no edit/delete UI)
- [ ] **Verify:** perform a save in another feature, confirm a matching event appears here without refresh

---

## Phase 7 — Polish

- [ ] Loading states for all forms/lists (shadcn `skeleton`)
- [ ] Empty states (no opportunities yet, no alerts, etc.)
- [ ] Error states surfaced via `sonner` toasts, consistent across all forms
- [ ] Consistent severity color coding across Alert Center, dashboard, and inline warnings
- [ ] Form UX pass — tab order, sensible defaults, fast to fill under time pressure
- [ ] Final check: every "must be true across the whole front-end" behavior still holds (no refresh needed anywhere, warnings never block, everything stays connected)
