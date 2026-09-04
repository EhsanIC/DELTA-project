"use client"

import { useEffect } from "react"
import { useRouter } from "next/navigation"

import { useAuth } from "@/hooks/use-auth"

export function AuthGuard({ role, children }: { role: string; children: React.ReactNode }) {
  const router = useRouter()
  const { ready, token, user } = useAuth()

  useEffect(() => {
    if (!ready) return

    if (!token) {
      router.replace(`/login?next=/${role}`)
      return
    }

    if (!user?.roles.includes(role)) {
      router.replace("/")
    }
  }, [ready, token, user, role, router])

  if (!ready || !token || !user?.roles.includes(role)) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-muted p-6">
        <p className="text-sm text-muted-foreground">Checking access…</p>
      </main>
    )
  }

  return children
}
