"use client"

import { useSyncExternalStore } from "react"

import { getStoredUser, getToken, type AuthUser } from "@/lib/api"

const serverSnapshot = JSON.stringify({ token: "", user: null })

function snapshot(): string {
  return JSON.stringify({ token: getToken(), user: getStoredUser() })
}

function subscribe(callback: () => void): () => void {
  window.addEventListener("decision-delta-auth-change", callback)
  window.addEventListener("storage", callback)

  return () => {
    window.removeEventListener("decision-delta-auth-change", callback)
    window.removeEventListener("storage", callback)
  }
}

export function useAuth(): { token: string; user: AuthUser | null; ready: boolean } {
  const value = useSyncExternalStore(subscribe, snapshot, () => serverSnapshot)
  const session = JSON.parse(value) as { token: string; user: AuthUser | null }

  return { ...session, ready: value !== serverSnapshot }
}

export function useRole(): string | null {
  return useAuth().user?.roles[0] ?? null
}
