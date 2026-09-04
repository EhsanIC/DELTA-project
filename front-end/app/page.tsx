"use client";

import { useEffect, useMemo, useState, useSyncExternalStore } from "react";
import type { FormEvent, ReactNode } from "react";
import {
  ArrowDownLeft,
  ArrowUpRight,
  CalendarDays,
  CircleDollarSign,
  LoaderCircle,
  Plus,
  ReceiptText,
  WalletCards,
} from "lucide-react";

type EntryType = "receipt" | "payment" | "expense";

type CashSummary = {
  receipts: string;
  payments: string;
  expenses: string;
  current_balance: string;
};

type FormState = {
  amount: string;
  date: string;
  description: string;
};

const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

function getStoredToken(): string {
  return window.localStorage.getItem("decision-delta-token") ?? "";
}

function subscribeToToken(): () => void {
  return () => undefined;
}

const entryLabels: Record<EntryType, string> = {
  receipt: "Receipt",
  payment: "Payment",
  expense: "Expense",
};

const initialForm: FormState = {
  amount: "",
  date: new Date().toISOString().slice(0, 10),
  description: "",
};

function currency(value: string | number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(Number(value));
}

export default function Home() {
  const [summary, setSummary] = useState<CashSummary | null>(null);
  const [entryType, setEntryType] = useState<EntryType>("receipt");
  const [form, setForm] = useState<FormState>(initialForm);
  const token = useSyncExternalStore(subscribeToToken, getStoredToken, () => "");
  const [loading, setLoading] = useState(() => Boolean(token));
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(() => (
    token ? "" : "Sign in as a finance user and store the Sanctum token as decision-delta-token to load live data."
  ));

  const projectedBalance = useMemo(() => {
    if (!summary || !form.amount) {
      return null;
    }

    const direction = entryType === "receipt" ? 1 : -1;
    return Number(summary.current_balance) + direction * Number(form.amount);
  }, [entryType, form.amount, summary]);

  async function loadSummary(authToken: string): Promise<void> {
    setLoading(true);

    try {
      const response = await fetch(`${apiUrl}/cash-summary`, {
        headers: { Accept: "application/json", Authorization: `Bearer ${authToken}` },
      });

      if (!response.ok) {
        throw new Error("Unable to load the cash summary.");
      }

      const data = (await response.json()) as { cash_summary: CashSummary };
      setSummary(data.cash_summary);
      setMessage("");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Unable to load the cash summary.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (token) {
      void loadSummary(token);
    }
  }, [token]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();

    if (!token) {
      setMessage("A finance Sanctum token is required before saving entries.");
      return;
    }

    setSaving(true);
    setMessage("");

    const payload = entryType === "expense"
      ? { amount: Number(form.amount), date: form.date, description: form.description }
      : { amount: Number(form.amount), date: form.date };

    try {
      const response = await fetch(`${apiUrl}/${entryType}s`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        const data = (await response.json()) as { message?: string };
        throw new Error(data.message ?? "Unable to save this finance entry.");
      }

      setForm(initialForm);
      setMessage(`${entryLabels[entryType]} saved successfully.`);
      await loadSummary(token);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Unable to save this finance entry.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <main className="min-h-screen bg-[#f7f8fa] text-slate-950">
      <div className="mx-auto flex min-h-screen max-w-7xl flex-col px-5 py-6 sm:px-8 lg:px-10">
        <header className="flex flex-col gap-5 border-b border-slate-200 pb-6 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <div className="flex size-11 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-sm">
              <WalletCards className="size-5" />
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Decision-Delta</p>
              <h1 className="text-2xl font-semibold tracking-tight">Finance dashboard</h1>
            </div>
          </div>
          <div className="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
            <span className="size-2 rounded-full bg-emerald-500" />
            Live cash position
          </div>
        </header>

        <section className="grid gap-4 py-8 sm:grid-cols-2 lg:grid-cols-4">
          <SummaryCard label="Current balance" value={summary?.current_balance} icon={<CircleDollarSign />} tone="dark" loading={loading} />
          <SummaryCard label="Receipts" value={summary?.receipts} icon={<ArrowDownLeft />} tone="green" loading={loading} />
          <SummaryCard label="Payments" value={summary?.payments} icon={<ArrowUpRight />} tone="blue" loading={loading} />
          <SummaryCard label="Expenses" value={summary?.expenses} icon={<ReceiptText />} tone="amber" loading={loading} />
        </section>

        <section className="grid flex-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
          <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div className="mb-7 flex items-start justify-between gap-4">
              <div>
                <p className="mb-1 text-sm font-medium text-slate-500">Finance operations</p>
                <h2 className="text-xl font-semibold tracking-tight">Record a cash movement</h2>
              </div>
              <div className="hidden rounded-xl bg-slate-100 p-3 text-slate-600 sm:block">
                <Plus className="size-5" />
              </div>
            </div>

            <div className="mb-7 grid grid-cols-3 rounded-2xl bg-slate-100 p-1">
              {(["receipt", "payment", "expense"] as EntryType[]).map((type) => (
                <button
                  key={type}
                  type="button"
                  onClick={() => setEntryType(type)}
                  className={`rounded-xl px-3 py-2.5 text-sm font-semibold transition ${entryType === type ? "bg-white text-slate-950 shadow-sm" : "text-slate-500 hover:text-slate-800"}`}
                >
                  {entryLabels[type]}
                </button>
              ))}
            </div>

            <form className="space-y-5" onSubmit={handleSubmit}>
              <div className="grid gap-5 sm:grid-cols-2">
                <label className="space-y-2 text-sm font-medium text-slate-700">
                  Amount
                  <div className="relative">
                    <CircleDollarSign className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input required min="0" step="0.01" type="number" value={form.amount} onChange={(event) => setForm({ ...form, amount: event.target.value })} placeholder="0.00" className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 outline-none transition focus:border-slate-500 focus:bg-white focus:ring-4 focus:ring-slate-100" />
                  </div>
                </label>
                <label className="space-y-2 text-sm font-medium text-slate-700">
                  Date
                  <div className="relative">
                    <CalendarDays className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input required type="date" value={form.date} onChange={(event) => setForm({ ...form, date: event.target.value })} className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 outline-none transition focus:border-slate-500 focus:bg-white focus:ring-4 focus:ring-slate-100" />
                  </div>
                </label>
              </div>

              {entryType === "expense" && (
                <label className="block space-y-2 text-sm font-medium text-slate-700">
                  Description
                  <input required maxLength={255} type="text" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} placeholder="e.g. Office supplies" className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 outline-none transition focus:border-slate-500 focus:bg-white focus:ring-4 focus:ring-slate-100" />
                </label>
              )}

              <div className="flex flex-col gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-slate-500">All saved entries are attributed to the authenticated finance user.</p>
                <button disabled={saving} type="submit" className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                  {saving && <LoaderCircle className="size-4 animate-spin" />}
                  Save {entryLabels[entryType]}
                </button>
              </div>
            </form>
          </div>

          <aside className="rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
            <p className="mb-2 text-sm font-medium text-slate-400">What-if preview</p>
            <h2 className="text-xl font-semibold tracking-tight">Post-save balance</h2>
            <p className="mt-3 text-sm leading-6 text-slate-400">Preview how this entry changes the current cash position before you save it.</p>
            <div className="mt-10 border-t border-white/10 pt-6">
              <p className="text-sm text-slate-400">Projected balance</p>
              <p className={`mt-2 text-4xl font-semibold tracking-tight ${projectedBalance !== null && projectedBalance < 0 ? "text-rose-400" : "text-white"}`}>
                {projectedBalance === null ? "—" : currency(projectedBalance)}
              </p>
              {projectedBalance !== null && projectedBalance < 0 && <p className="mt-3 text-sm font-medium text-rose-300">This movement would take cash below zero.</p>}
            </div>
            <div className="mt-10 rounded-2xl bg-white/10 p-4 text-sm leading-6 text-slate-300">
              <strong className="text-white">Formula</strong>
              <br />Receipts − payments − expenses
            </div>
          </aside>
        </section>

        {message && <p className="mt-6 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">{message}</p>}
      </div>
    </main>
  );
}

function SummaryCard({ label, value, icon, tone, loading }: { label: string; value?: string; icon: ReactNode; tone: "dark" | "green" | "blue" | "amber"; loading: boolean }) {
  const tones = {
    dark: "bg-slate-950 text-white",
    green: "bg-emerald-50 text-emerald-700",
    blue: "bg-blue-50 text-blue-700",
    amber: "bg-amber-50 text-amber-700",
  };

  return (
    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-slate-500">{label}</p>
        <div className={`flex size-9 items-center justify-center rounded-xl ${tones[tone]}`}>{icon}</div>
      </div>
      <p className="mt-5 text-2xl font-semibold tracking-tight">{loading ? "..." : currency(value ?? 0)}</p>
    </div>
  );
}
