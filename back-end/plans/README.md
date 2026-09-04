# تصمیم‌دلتا (Decision-Delta) — Project Documentation

## 1. Overview

A local, real-time-feeling business analysis system for a hackathon
challenge (90-minute build). Sales, Operations, and Finance staff log
daily business events, and the system recalculates revenue, profit,
inventory, install capacity, cash, firm orders, and warnings — both
**before save** (live "what-if" on every field change) and
**immediately after save** — with no manual page refresh.

**Core principle:** analysis only warns, it never blocks a save.
Only structurally invalid data (empty required field, disallowed
negative number, invalid date) can prevent a record from being saved.

**Hard constraint:** must run fully offline at runtime — no internet,
external services, external AI, API keys, paid services, or cloud
infrastructure. AI-assisted development is allowed.

---

## 2. Tech Stack

| Layer | Choice | Why |
|---|---|---|
| Frontend | Next.js (App Router) + TypeScript | existing standard stack |
| Backend | Laravel (PHP), multi-role API | existing standard stack |
| Data fetching | SWR + `apiFetch` wrapper | no Axios, polling covers "real-time" needs |
| Forms | `react-hook-form` + `zod` + `@hookform/resolvers` | per-field `onChange` needed for live pre-save calc |
| Toasts | `sonner` | save confirmations, alert-severity notifications |
| Auth/roles | Next.js middleware + `useRole()` hook reading JWT/session | avoids a full RBAC library at this scope |

### Frontend dependencies (final)
```bash
bun add react-hook-form zod @hookform/resolvers sonner swr
```
No state management library, no UI kit, no Axios, no clsx, no
websocket client — kept intentionally minimal for a time-boxed build.

### Real-time strategy
Two different needs, two different mechanisms:

- **Pre-save "live effect" (single user, single form):** pure
  client-side computation. Referenced data (price, cost, inventory,
  capacity) is already loaded via SWR; every keystroke recomputes
  in-memory with `useMemo` — zero network requests, zero latency.
- **Cross-user real-time (dashboard, events log, other users' saves
  reflected without refresh):** SWR polling (`refreshInterval`, e.g.
  2s) against the API. No websockets, no Laravel Reverb/Echo/Pusher —
  satisfies "no manual refresh" without the added infrastructure.

---

## 3. Roles

- **Sales** — creates/edits opportunities
- **Operations** — inventory adjustments, install capacity adjustments
- **Finance** — receipts, payments, expenses
- **Admin/Manager** — settings panel, dashboard, alert center

Role gating is route-level (Next.js middleware) plus UI-level
(`useRole()`), not a separate RBAC package.

---

## 4. Pages

### Page 1 — Registration & Analysis
Three sections in one page: Sales, Operations, Finance.

**Sales**
- List: customer, product, qty, amount, discount, due date, stage,
  analytical status, alert severity
- Form: customer, product, qty, unit price, discount %, delivery due
  date, sales stage — stages: `New / Quoted / Won / Lost`
- Price and discount are interdependent
- **Live effect panel** (pre-save, no reload): revenue, cost, profit,
  margin, remaining inventory, install hours needed, remaining
  capacity, feasibility status, new alerts
- Marking a sale "Won" converts it into a real commitment — inventory
  and capacity are consumed in subsequent calculations, and all
  dependent analyses recompute immediately

**Operations** — minimum two operations:
- Inventory adjustment: product, new quantity, reason
- Install capacity adjustment: date, available hours, reason
- Before save: show affected opportunities/orders
- After save: update all dependent statuses

**Finance** — minimum three operations:
- Receipt: amount, date
- Payment: amount, date
- Expense: amount, date, description
- Live: current cash balance, post-change balance, receipts/payments
  due in the next 7 days, lowest forecasted balance, cash-shortage
  warning

### Page 2 — Management Dashboard
Real-time KPIs: firm revenue, operating profit, cash balance, open
opportunities, at-risk orders, critical alerts, product inventory,
install-capacity utilization %.

**Alert Center** — per alert: title, severity, cause, effect,
suggested fix.

Example:
> **Critical — Product 100 inventory shortage**
> Need: 40 | Inventory: 34 | Shortage: 6
> Suggestion: reduce quantity or change delivery due date.

All indicators/alerts update after any save or settings change,
without manual refresh.

### Page 3 — Admin Settings (mandatory)
Manager edits, without touching code:
- Product specs: name, base price, unit cost, safety stock, install time
- Financial rules: target margin, minimum operating cash, fixed
  shipping cost, per-unit shipping cost
- Capacity rules: available capacity, info/risk/critical thresholds
- Enable/disable individual alerts

Every settings change triggers an instant, system-wide recalculation
(e.g. changing target margin from 20% to 25% immediately changes the
status of every open opportunity and its alerts).

### Page 4 — Events Log
Every real save creates an organizational event recording: timestamp,
change type, entity, value before, value after, reason, and any
alert generated. Records are immutable — never deleted by later
changes.

Example:
> **10:37 — Product 100 inventory adjustment**
> 34 → 27
> 2 opportunities affected
> 1 critical alert created

---

## 5. Calculation Rules

```
Revenue         = qty × unit price
Cost of goods   = qty × unit cost
Shipping cost   = fixed shipping + (per-unit shipping × qty)
Install hours   = qty × install-minutes-per-unit ÷ 60
Operating profit = revenue − cost of goods − shipping cost
Margin %        = operating profit ÷ revenue × 100

Free inventory  = physical inventory − reserved inventory
```

- Target margin, minimum cash, shipping costs, and capacity
  thresholds are always read from **Admin Settings** — never
  hardcoded.
- Negative inventory is allowed; it only ever produces a critical
  alert, it never blocks a save.

### Statuses
- **Opportunity analytical status:** `Feasible` / `Conditional` /
  `Not feasible currently`
- **Alert severity:** `Info` / `Risk` / `Critical`
- Neither status ever blocks saving a sale.

### Post-save cycle
```
Save data → update real state → recalculate whole system
          → update alerts & indicators → log event
```
No step should require a manual page reload.

---

## 6. Seed Data (provided to all teams)

3 products, 5 customers, 5 sales opportunities, 3 firm orders,
product inventory, install capacity, cash balance, receipts,
payments, expenses, and initial settings.

| Product | Base Price | Unit Cost | Free Inventory | Safety Stock | Install/Unit |
|---|---|---|---|---|---|
| Product 100 | 1,250 | 760 | 34 | 10 | 25 min |
| Product 200 | 2,100 | 1,390 | 27 | 8 | 50 min |
| Product 300 | 3,700 | 2,680 | 11 | 5 | 90 min |

---

## 7. Judging

| Criterion | Weight |
|---|---|
| Live analysis & instant recalculation | 30% |
| Dynamism & admin-configurability | 20% |
| Calculation accuracy | 20% |
| Cascading effects & alerts | 15% |
| Product cohesion | 10% |
| User experience | 5% |

**Acceptance test:** if an admin changes price, cost, inventory,
capacity, or an alert threshold — or if staff edits a sale, receipt,
payment, expense, or inventory — the system must recompute and
display all related effects across the whole product instantly, with
no code change and no refresh.

---

## 8. Explicitly Out of Scope (kept simple on purpose)

- Websockets / Laravel Reverb / Echo / Pusher — SWR polling instead
- Redux / Zustand / any state management library — React state + SWR
  cache is enough
- A UI component kit — hand-rolled or shadcn/ui copy-paste only if
  time allows
- A full RBAC package — middleware + one hook
- Axios — project's existing `apiFetch` wrapper