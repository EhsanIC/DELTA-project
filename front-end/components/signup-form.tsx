"use client"

import { zodResolver } from "@hookform/resolvers/zod"
import { useRouter } from "next/navigation"
import { useState } from "react"
import { useForm } from "react-hook-form"
import { toast } from "sonner"
import { z } from "zod"

import { apiFetch, ApiError, saveAuth, type AuthResponse } from "@/lib/api"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

const signupSchema = z.object({
  name: z.string().min(1, "Enter your name."),
  email: z.string().email("Enter a valid email address."),
  password: z.string().min(8, "Password must be at least 8 characters."),
  password_confirmation: z.string().min(1, "Confirm your password."),
  role: z.enum(["sales", "operations", "finance", "none"]),
}).refine((values) => values.password === values.password_confirmation, {
  message: "Passwords do not match.",
  path: ["password_confirmation"],
})

type SignupValues = z.infer<typeof signupSchema>

export function SignupForm() {
  const router = useRouter()
  const [submitting, setSubmitting] = useState(false)
  const form = useForm<SignupValues>({
    resolver: zodResolver(signupSchema),
    defaultValues: { name: "", email: "", password: "", password_confirmation: "", role: "sales" },
  })

  async function onSubmit(values: SignupValues): Promise<void> {
    setSubmitting(true)

    try {
      const { role, ...account } = values
      const auth = await apiFetch<AuthResponse>("/auth/register", {
        method: "POST",
        body: JSON.stringify(role === "none" ? account : { ...account, role }),
      })
      saveAuth(auth)
      toast.success("Account created.")
      router.replace(auth.user.roles.includes("sales") ? "/sales" : "/")
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Unable to create your account.")
    } finally {
      setSubmitting(false)
    }
  }

  const errorMessage = (name: keyof SignupValues) => form.formState.errors[name]?.message

  return (
    <Card>
      <CardHeader className="text-center">
        <CardTitle className="text-xl">Create your account</CardTitle>
        <CardDescription>Choose a workspace role to get started.</CardDescription>
      </CardHeader>
      <CardContent>
        <form className="space-y-5" onSubmit={form.handleSubmit(onSubmit)}>
          <div className="space-y-2"><Label htmlFor="name">Full name</Label><Input id="name" placeholder="John Doe" {...form.register("name")} />{errorMessage("name") && <p className="text-sm text-destructive">{errorMessage("name")}</p>}</div>
          <div className="space-y-2"><Label htmlFor="email">Email</Label><Input id="email" type="email" placeholder="m@example.com" {...form.register("email")} />{errorMessage("email") && <p className="text-sm text-destructive">{errorMessage("email")}</p>}</div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2"><Label htmlFor="password">Password</Label><Input id="password" type="password" {...form.register("password")} />{errorMessage("password") && <p className="text-sm text-destructive">{errorMessage("password")}</p>}</div>
            <div className="space-y-2"><Label htmlFor="password_confirmation">Confirm password</Label><Input id="password_confirmation" type="password" {...form.register("password_confirmation")} />{errorMessage("password_confirmation") && <p className="text-sm text-destructive">{errorMessage("password_confirmation")}</p>}</div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="role">Workspace role</Label>
            <Select value={form.watch("role")} onValueChange={(value) => form.setValue("role", value as SignupValues["role"], { shouldValidate: true })}>
              <SelectTrigger id="role" className="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="sales">Sales</SelectItem>
                <SelectItem value="operations">Operations</SelectItem>
                <SelectItem value="finance">Finance</SelectItem>
                <SelectItem value="none">No role yet</SelectItem>
              </SelectContent>
            </Select>
            {errorMessage("role") && <p className="text-sm text-destructive">{errorMessage("role")}</p>}
          </div>
          <Button className="w-full" disabled={submitting} type="submit">{submitting ? "Creating account…" : "Create account"}</Button>
          <p className="text-center text-sm text-muted-foreground">Already have an account? <a className="font-medium text-foreground underline" href="/login">Sign in</a></p>
        </form>
      </CardContent>
    </Card>
  )
}
