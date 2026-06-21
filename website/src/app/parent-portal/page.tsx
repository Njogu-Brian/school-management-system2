"use client";

import { useEffect, useState } from "react";
import { SiteShell } from "@/components/layout/SiteShell";
import { parentService, getParentToken } from "@/services/parentService";
import Link from "next/link";

export default function ParentPortalPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [authed, setAuthed] = useState(false);
  const [dashboard, setDashboard] = useState<Record<string, unknown> | null>(null);
  const [children, setChildren] = useState<Array<{ id: number; full_name?: string; name?: string }>>([]);
  const [error, setError] = useState("");

  useEffect(() => {
    if (getParentToken()) loadPortal();
  }, []);

  async function loadPortal() {
    try {
      const [dash, kids] = await Promise.all([parentService.dashboard(), parentService.children()]);
      setDashboard(dash);
      setChildren(kids.data || kids.students || []);
      setAuthed(true);
    } catch {
      setAuthed(false);
    }
  }

  async function login(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    try {
      await parentService.login(email, password);
      await loadPortal();
    } catch {
      setError("Invalid credentials or not a parent account.");
    }
  }

  if (!authed) {
    return (
      <SiteShell>
        <section className="mx-auto max-w-md px-4 py-16">
          <h1 className="font-serif text-3xl font-bold text-[#2a1145] text-center">Parent Portal</h1>
          <p className="mt-2 text-center text-[#4a3a5c]">Sign in with your ERP parent account</p>
          <form onSubmit={login} className="mt-8 space-y-4 rounded-2xl border p-6 shadow">
            <input type="email" placeholder="Email" className="w-full rounded-xl border px-4 py-3" value={email} onChange={(e) => setEmail(e.target.value)} required />
            <input type="password" placeholder="Password" className="w-full rounded-xl border px-4 py-3" value={password} onChange={(e) => setPassword(e.target.value)} required />
            {error && <p className="text-sm text-red-600">{error}</p>}
            <button type="submit" className="w-full rounded-full bg-[#5B2C8E] py-3 font-semibold text-white">Sign In</button>
          </form>
        </section>
      </SiteShell>
    );
  }

  return (
    <SiteShell>
      <section className="bg-gradient-to-br from-[#5B2C8E] to-[#2a1145] py-12 text-white">
        <div className="mx-auto max-w-6xl px-4 flex justify-between items-center">
          <div><h1 className="font-serif text-3xl font-bold">Welcome back</h1><p className="text-white/80">Your family dashboard</p></div>
          <button type="button" onClick={() => { parentService.logout(); setAuthed(false); }} className="rounded-full border border-white/30 px-4 py-2 text-sm">Sign out</button>
        </div>
      </section>
      <section className="mx-auto max-w-6xl px-4 py-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {children.map((child) => (
          <Link key={child.id} href={`/parent-portal/child/${child.id}`} className="rounded-2xl border border-[#e8dff5] bg-white p-6 shadow hover:shadow-lg transition">
            <h2 className="font-serif text-xl font-bold text-[#5B2C8E]">{child.full_name || child.name}</h2>
            <p className="mt-2 text-sm text-[#4a3a5c]">View fees · attendance · results</p>
          </Link>
        ))}
      </section>
      {dashboard && (
        <section className="mx-auto max-w-6xl px-4 pb-12">
          <div className="rounded-2xl bg-[#faf7ff] p-6">
            <h3 className="font-semibold text-[#2a1145]">Latest announcements</h3>
            <p className="mt-2 text-sm text-[#4a3a5c]">Pulled live from ERP communication module.</p>
          </div>
        </section>
      )}
    </SiteShell>
  );
}
