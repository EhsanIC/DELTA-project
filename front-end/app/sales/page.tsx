import { AuthGuard } from "@/components/auth-guard"
import { SalesDashboard } from "@/components/sales-dashboard"

export default function SalesPage() {
  return (
    <AuthGuard role="sales">
      <SalesDashboard />
    </AuthGuard>
  )
}
