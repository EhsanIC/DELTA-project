# Decision-Delta — Front-End Features Overview

*A plain, non-technical description of everything the app's user
interface needs to have.*

---

## 1. What the app does (in simple terms)

An internal tool for a company's Sales, Operations, Finance, and
Management staff. People enter their daily work (a sale, a payment,
an inventory change...) and the screen instantly shows them — before
they even save — what effect that entry will have on the company's
money, stock, and workload. After saving, every screen in the app
updates itself automatically, with no need to refresh the page.

---

## 2. The 4 main screens

### Screen 1 — Daily Entry & Live Preview
The main working screen, split into three tabs/sections:

**Sales tab**
- A list of all sales opportunities (customer, product, quantity,
  amount, discount, delivery date, stage, status, alert level)
- A form to create or edit an opportunity: customer, product,
  quantity, price, discount, delivery date, and stage
  (New → Quoted → Won → Lost)
- Changing price and discount affect each other automatically
- A **live preview panel** next to the form that updates on every
  keystroke — before anything is saved — showing: revenue, profit,
  profit margin, remaining stock, hours of installation needed,
  remaining capacity, whether the sale is doable, and any new warnings
- Marking a sale as "Won" turns it into a real commitment: stock and
  capacity are reserved, and everything else in the app updates to
  reflect that instantly

**Operations tab**
- A way to correct stock quantity (product, new amount, reason)
- A way to adjust available installation capacity for a date (hours
  available, reason)
- Before saving either one, the screen shows which sales/orders will
  be affected
- After saving, everything dependent on that number updates itself

**Finance tab**
- A way to record money coming in (a receipt)
- A way to record money going out (a payment)
- A way to record a cost/expense (with a description)
- A live summary showing: current cash, cash after this change, what
  money is expected in/out over the next 7 days, the lowest the cash
  is expected to drop to, and a warning if a cash shortage is coming

### Screen 2 — Management Dashboard
A single overview screen for managers, showing the company's current
state at a glance:
- Total confirmed revenue
- Operating profit
- Current cash
- Number of open opportunities
- Orders at risk
- Number of critical alerts
- Current stock per product
- How much of the installation capacity is being used (%)

Also includes an **Alert Center** — a feed of every active warning,
each showing: what the problem is, how serious it is, why it
happened, what it affects, and a suggested fix.

Everything on this screen refreshes itself the moment anything
changes anywhere in the app — no refresh button needed.

### Screen 3 — Settings (Admin only)
A screen where a manager can change how the whole app behaves,
without needing anyone to touch the code:
- Product details: name, price, cost, minimum stock to keep, install
  time per unit
- Money rules: target profit margin, minimum cash the company should
  keep on hand, shipping costs
- Capacity rules: total capacity available, and the thresholds at
  which warnings turn into "info," "risk," or "critical"
- Turning individual warning types on or off

The instant a manager changes any of these, the entire app
recalculates itself and reflects the new numbers everywhere.

### Screen 4 — Activity Log
A running history of everything that's happened in the app: what
changed, when, what it was before and after, why it was changed, and
what warnings it triggered. Nothing in this history can be deleted or
edited — it's a permanent record.

---

## 3. Things that must be true across the *whole* front-end

- **Nothing needs a page refresh.** Any change made by anyone,
  anywhere, shows up on every relevant screen automatically.
- **Preview before you commit.** Wherever someone is filling out a
  form, they should see the consequences of their entry before they
  hit save — not after.
- **Warnings never block you.** The app can tell you something is
  risky or critical, but it should still let you save — except when
  the entry itself is broken (a required field left empty, a negative
  number where that makes no sense, or an invalid date).
- **Everything feels connected.** A change made in one tab (say,
  Operations lowering stock) should be visible moments later in
  another tab (Sales showing that opportunity is now "not feasible"),
  without the user doing anything extra.
- **Simple and fast to use.** Forms should be quick to fill, the
  live-preview numbers easy to read at a glance, and warnings clearly
  color-coded by severity (info / risk / critical) so the important
  ones stand out immediately.