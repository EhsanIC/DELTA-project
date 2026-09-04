# Decision-Delta — Front-End Build Phases

*A general, non-technical breakdown of what to build and in what
order, sized for a 90-minute hackathon.*

---

## Phase 1 — Skeleton
Get the basic shape of the app up before anything fancy.
- The 4 screens exist and you can move between them (Daily Entry,
  Dashboard, Settings, Activity Log)
- Basic layout: navigation, page titles, empty sections ready to be
  filled in
- Login/role identification working, so the app knows if someone is
  Sales, Operations, Finance, or Admin

**Goal:** app opens, screens are reachable, nothing is functional yet.

---

## Phase 2 — Core data entry (no live preview yet)
Get the basic forms working, just plain save-and-see-it-in-the-list.
- Sales tab: list of opportunities + a working create/edit form
- Operations tab: inventory adjustment + capacity adjustment forms
- Finance tab: receipt, payment, and expense forms
- Saving something adds it to its list and nothing breaks

**Goal:** every type of entry in the brief can be created and saved.

---

## Phase 3 — Live preview (the app's signature feature)
This is the most important phase — where the app goes from "a normal
form" to "the thing the challenge is actually testing."
- Sales form: live preview panel that updates on every keystroke,
  before saving — revenue, profit, margin, remaining stock,
  install hours, feasibility, warnings
- Finance section: live cash balance, 7-day forecast, shortage
  warning, all updating as you type
- Operations section: shows which sales/orders are affected before
  you save a stock or capacity change

**Goal:** nothing requires hitting save to see its impact.

---

## Phase 4 — Everything updates itself
Make sure a change made in one place shows up everywhere else,
automatically.
- Saving a sale, payment, or stock change updates the dashboard
  without a refresh
- Marking a sale "Won" updates stock, capacity, and any related
  warnings across the whole app
- No page anywhere needs a manual refresh to show the latest numbers

**Goal:** the whole app feels like one connected system, not separate pages.

---

## Phase 5 — Dashboard & Alert Center
Build the manager-facing overview.
- Dashboard summary numbers: revenue, profit, cash, open
  opportunities, at-risk orders, critical alerts, stock, capacity use
- Alert Center: list of active warnings with severity, cause, effect,
  and suggested fix
- Warnings are color-coded by severity so critical ones stand out

**Goal:** a manager can understand the whole business state at a glance.

---

## Phase 6 — Settings screen
Let an admin change the rules that drive everything else.
- Editable product details, money rules, capacity rules, and
  alert on/off toggles
- Changing any setting instantly recalculates the rest of the app —
  reuse the same "everything updates itself" behavior from Phase 4

**Goal:** nothing in the app depends on values that are fixed in the
code — an admin can change the rules from the screen.

---

## Phase 7 — Activity Log
Add the permanent history trail.
- Every real save shows up here: what changed, when, before/after
  values, why, and what warning it triggered
- Nothing in this log can be edited or deleted

**Goal:** every action taken anywhere in the app leaves a visible,
permanent trace.

---

## Phase 8 — Polish (only if time allows)
- Clean up spacing, labels, and empty/error states
- Make sure warnings and colors are consistent everywhere
- Double-check nothing needs a manual refresh anywhere
- Quick pass to make forms fast and easy to fill under time pressure

**Goal:** the app is pleasant to use, not just functional.

---

## Priority if time runs out
If the clock forces a cut, keep things in this order — each phase
depends on the ones before it, and the grading weights the *live,
connected* behavior far more than looks:

1. Phase 1–2 (basic structure + working forms) — required baseline
2. Phase 3 (live preview) — worth the most, do not skip
3. Phase 4 (everything updates itself) — the second most important
4. Phase 5 (dashboard/alerts) — needed for a complete product
5. Phase 6 (settings) — required by the brief, but can be minimal
6. Phase 7 (activity log) — smallest, do last if squeezed
7. Phase 8 (polish) — only with leftover time