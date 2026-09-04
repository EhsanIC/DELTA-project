"use client"

import { zodResolver } from "@hookform/resolvers/zod"
import useSWR from "swr"
import { useEffect, useState } from "react"
import { useForm, useWatch } from "react-hook-form"
import { toast } from "sonner"
import { z } from "zod"
import { Pencil, Plus, RefreshCw, TrendingUp } from "lucide-react"

import { apiFetch, ApiError } from "@/lib/api"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"

const stages = ["New", "Quoted", "Won", "Lost"] as const

const opportunitySchema = z.object({
  customer_id: z.string().optional(),
  product_id: z.string().min(1, "Select a product."),
  qty: z.coerce.number().int().min(0, "Quantity cannot be negative."),
  unit_price: z.coerce.number().min(0, "Unit price cannot be negative."),
  due_date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, "Enter a valid date."),
  stage: z.enum(stages),
})

type OpportunityFormValues = z.infer<typeof opportunitySchema>
type OpportunityFormInput = z.input<typeof opportunitySchema>
type Product = { id: number; name: string; base_price: string; unit_cost: string; physical_inventory: number; reserved_inventory: number; free_inventory?: number }
type Customer = { id: number; name: string; email?: string | null; phone?: string | null }
type Opportunity = {
  id: number
  customer_id: number | null
  product_id: number
  qty: number
  unit_price: string
  due_date: string
  stage: (typeof stages)[number]
  customer: Customer | null
  product: Product
  revenue: string
  operating_profit: string
  margin_percent: string
}

const fetcher = <T,>(path: string) => apiFetch<T>(path)

function money(value: string | number): string {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value))
}

function stageVariant(stage: Opportunity["stage"]): "default" | "secondary" | "outline" | "destructive" {
  if (stage === "Won") return "default"
  if (stage === "Lost") return "destructive"
  if (stage === "Quoted") return "secondary"
  return "outline"
}

export function SalesDashboard() {
  const { data: productsData, error: productsError } = useSWR<{ products: Product[] }>("/products", fetcher)
  const { data: customersData, error: customersError } = useSWR<{ customers: Customer[] }>("/customers", fetcher)
  const { data: opportunitiesData, error: opportunitiesError, isLoading, mutate } = useSWR<{ opportunities: Opportunity[] }>("/opportunities", fetcher)
  const [editing, setEditing] = useState<Opportunity | null>(null)
  const [saving, setSaving] = useState(false)

  const form = useForm<OpportunityFormInput, unknown, OpportunityFormValues>({
    resolver: zodResolver(opportunitySchema),
    defaultValues: {
      customer_id: "",
      product_id: "",
      qty: 1,
      unit_price: 0,
      due_date: new Date().toISOString().slice(0, 10),
      stage: "New",
    },
  })

  const productId = useWatch({ control: form.control, name: "product_id" })
  const customerId = useWatch({ control: form.control, name: "customer_id" })
  const qty = useWatch({ control: form.control, name: "qty" })
  const unitPrice = useWatch({ control: form.control, name: "unit_price" })
  const stage = useWatch({ control: form.control, name: "stage" })
  const selectedProduct = productsData?.products.find((product) => String(product.id) === productId)

  useEffect(() => {
    if (selectedProduct && !editing) {
      form.setValue("unit_price", Number(selectedProduct.base_price))
    }
  }, [selectedProduct, editing, form])

  function startCreate(): void {
    setEditing(null)
    form.reset({
      customer_id: "",
      product_id: "",
      qty: 1,
      unit_price: 0,
      due_date: new Date().toISOString().slice(0, 10),
      stage: "New",
    })
  }

  function startEdit(opportunity: Opportunity): void {
    setEditing(opportunity)
    form.reset({
      customer_id: opportunity.customer_id ? String(opportunity.customer_id) : "",
      product_id: String(opportunity.product_id),
      qty: opportunity.qty,
      unit_price: Number(opportunity.unit_price),
      due_date: opportunity.due_date,
      stage: opportunity.stage,
    })
    window.scrollTo({ top: 0, behavior: "smooth" })
  }

  async function onSubmit(values: OpportunityFormValues): Promise<void> {
    setSaving(true)
    try {
      const payload = {
        ...values,
        product_id: Number(values.product_id),
        customer_id: values.customer_id ? Number(values.customer_id) : null,
      }
      const path = editing ? `/opportunities/${editing.id}` : "/opportunities"
      await apiFetch(path, { method: editing ? "PATCH" : "POST", body: JSON.stringify(payload) })
      await mutate()
      toast.success(editing ? "Opportunity updated." : "Opportunity created.")
      startCreate()
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Unable to save the opportunity.")
    } finally {
      setSaving(false)
    }
  }

  const opportunities = opportunitiesData?.opportunities ?? []
  const errors = [productsError, customersError, opportunitiesError].filter(Boolean)
  const formError = (name: keyof OpportunityFormValues) => form.formState.errors[name]?.message

  return (
    <main className="min-h-screen bg-muted/30 px-4 py-8 sm:px-8">
      <div className="mx-auto max-w-7xl space-y-6">
        <header className="flex flex-col gap-4 rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:flex-row sm:items-end sm:justify-between sm:p-8">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Decision-Delta / Sales</p>
            <h1 className="mt-2 text-3xl font-semibold tracking-tight">Opportunity workspace</h1>
            <p className="mt-2 text-sm text-slate-400">Create, qualify, and close opportunities with live backend calculations.</p>
          </div>
          <Button variant="secondary" onClick={startCreate}><Plus /> New opportunity</Button>
        </header>

        {errors.length > 0 && <p className="rounded-2xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">Unable to load one or more sales resources. Check the API connection and your sales access.</p>}

        <Card>
          <CardHeader>
            <CardTitle>{editing ? "Edit opportunity" : "Create opportunity"}</CardTitle>
            <CardDescription>Customer is optional; the product, quantity, price, date, and stage are sent to the backend.</CardDescription>
          </CardHeader>
          <CardContent>
            <form className="grid gap-5 md:grid-cols-2 lg:grid-cols-3" onSubmit={form.handleSubmit(onSubmit)}>
              <div className="space-y-2">
                <Label htmlFor="customer">Customer</Label>
                <Select value={customerId ?? ""} onValueChange={(value) => form.setValue("customer_id", value ?? "", { shouldValidate: true })}>
                  <SelectTrigger id="customer" className="w-full"><SelectValue placeholder="No customer selected" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">No customer</SelectItem>
                    {(customersData?.customers ?? []).map((customer) => <SelectItem key={customer.id} value={String(customer.id)}>{customer.name}</SelectItem>)}
                  </SelectContent>
                </Select>
                {formError("customer_id") && <p className="text-xs text-destructive">{formError("customer_id")}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="product">Product</Label>
                <Select value={productId ?? ""} onValueChange={(value) => form.setValue("product_id", value ?? "", { shouldValidate: true })}>
                  <SelectTrigger id="product" className="w-full"><SelectValue placeholder="Select a product" /></SelectTrigger>
                  <SelectContent>{(productsData?.products ?? []).map((product) => <SelectItem key={product.id} value={String(product.id)}>{product.name} · {money(product.base_price)}</SelectItem>)}</SelectContent>
                </Select>
                {formError("product_id") && <p className="text-xs text-destructive">{formError("product_id")}</p>}
              </div>
              <div className="space-y-2"><Label htmlFor="qty">Quantity</Label><Input id="qty" type="number" min="0" {...form.register("qty")} />{formError("qty") && <p className="text-xs text-destructive">{formError("qty")}</p>}</div>
              <div className="space-y-2"><Label htmlFor="unit_price">Unit price</Label><Input id="unit_price" type="number" min="0" step="0.01" {...form.register("unit_price")} />{formError("unit_price") && <p className="text-xs text-destructive">{formError("unit_price")}</p>}</div>
              <div className="space-y-2"><Label htmlFor="due_date">Due date</Label><Input id="due_date" type="date" {...form.register("due_date")} />{formError("due_date") && <p className="text-xs text-destructive">{formError("due_date")}</p>}</div>
              <div className="space-y-2"><Label htmlFor="stage">Stage</Label><Select value={stage} onValueChange={(value) => value && form.setValue("stage", value as OpportunityFormValues["stage"], { shouldValidate: true })}><SelectTrigger id="stage" className="w-full"><SelectValue /></SelectTrigger><SelectContent>{stages.map((stage) => <SelectItem key={stage} value={stage}>{stage}</SelectItem>)}</SelectContent></Select>{formError("stage") && <p className="text-xs text-destructive">{formError("stage")}</p>}</div>
              <div className="flex gap-2 md:col-span-2 lg:col-span-3"><Button type="submit" disabled={saving}>{saving ? "Saving…" : editing ? "Update opportunity" : "Create opportunity"}</Button>{editing && <Button type="button" variant="outline" onClick={startCreate}>Cancel</Button>}</div>
            </form>
          </CardContent>
        </Card>

        {selectedProduct && <div className="grid gap-4 sm:grid-cols-3"><MetricCard label="Inventory available" value={String(selectedProduct.free_inventory ?? selectedProduct.physical_inventory - selectedProduct.reserved_inventory)} /><MetricCard label="Unit cost" value={money(selectedProduct.unit_cost)} /><MetricCard label="Estimated revenue" value={money(Number(qty || 0) * Number(unitPrice || 0))} /></div>}

        <Card>
          <CardHeader className="flex-row items-center justify-between"><div><CardTitle>Opportunities</CardTitle><CardDescription>{opportunities.length} total records · backend-calculated values</CardDescription></div><Button variant="outline" size="sm" onClick={() => void mutate()}><RefreshCw /> Refresh</Button></CardHeader>
          <CardContent>
            {isLoading ? <p className="py-8 text-center text-sm text-muted-foreground">Loading opportunities…</p> : opportunities.length === 0 ? <div className="py-8 text-center"><TrendingUp className="mx-auto size-8 text-muted-foreground" /><p className="mt-2 text-sm text-muted-foreground">No opportunities yet. Create the first one above.</p></div> : <Table><TableHeader><TableRow><TableHead>Customer</TableHead><TableHead>Product</TableHead><TableHead>Qty</TableHead><TableHead>Price</TableHead><TableHead>Due</TableHead><TableHead>Stage</TableHead><TableHead>Revenue</TableHead><TableHead>Profit / Margin</TableHead><TableHead /></TableRow></TableHeader><TableBody>{opportunities.map((opportunity) => <TableRow key={opportunity.id}><TableCell className="font-medium">{opportunity.customer?.name ?? "—"}</TableCell><TableCell>{opportunity.product.name}</TableCell><TableCell>{opportunity.qty}</TableCell><TableCell>{money(opportunity.unit_price)}</TableCell><TableCell>{opportunity.due_date}</TableCell><TableCell><Badge variant={stageVariant(opportunity.stage)}>{opportunity.stage}</Badge></TableCell><TableCell>{money(opportunity.revenue)}</TableCell><TableCell>{money(opportunity.operating_profit)} <span className="text-muted-foreground">({opportunity.margin_percent}%)</span></TableCell><TableCell><Button variant="ghost" size="sm" onClick={() => startEdit(opportunity)}><Pencil /> Edit</Button></TableCell></TableRow>)}</TableBody></Table>}
          </CardContent>
        </Card>
      </div>
    </main>
  )
}

function MetricCard({ label, value }: { label: string; value: string }) {
  return <Card size="sm"><CardContent className="pt-4"><p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</p><p className="mt-2 text-xl font-semibold">{value}</p></CardContent></Card>
}
