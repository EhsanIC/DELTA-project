export type AuthUser = {
  id: number
  name: string
  email: string
  roles: string[]
  permissions: string[]
}

export type AuthResponse = {
  token: string
  token_type: string
  user: AuthUser
}

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api"
const TOKEN_KEY = "decision-delta-token"
const USER_KEY = "decision-delta-user"

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message)
    this.name = "ApiError"
    this.status = status
    this.errors = errors
  }
}

export function getToken(): string {
  if (typeof window === "undefined") return ""
  return window.localStorage.getItem(TOKEN_KEY) ?? ""
}

export function getStoredUser(): AuthUser | null {
  if (typeof window === "undefined") return null
  const value = window.localStorage.getItem(USER_KEY)
  if (!value) return null

  try {
    return JSON.parse(value) as AuthUser
  } catch {
    return null
  }
}

export function saveAuth(auth: AuthResponse): void {
  window.localStorage.setItem(TOKEN_KEY, auth.token)
  window.localStorage.setItem(USER_KEY, JSON.stringify(auth.user))
  window.dispatchEvent(new Event("decision-delta-auth-change"))
}

export function clearAuth(): void {
  window.localStorage.removeItem(TOKEN_KEY)
  window.localStorage.removeItem(USER_KEY)
  window.dispatchEvent(new Event("decision-delta-auth-change"))
}

export function authHeaders(): HeadersInit {
  const token = getToken()
  return token ? { Authorization: `Bearer ${token}` } : {}
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers)
  headers.set("Accept", "application/json")

  if (options.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json")
  }

  const token = getToken()
  if (token) headers.set("Authorization", `Bearer ${token}`)

  const response = await fetch(`${API_URL}${path}`, { ...options, headers })
  const data = await response.json().catch(() => ({})) as {
    message?: string
    errors?: Record<string, string[]>
  } & T

  if (!response.ok) {
    throw new ApiError(
      data.message ?? "The request could not be completed.",
      response.status,
      data.errors,
    )
  }

  return data
}

export { API_URL }
