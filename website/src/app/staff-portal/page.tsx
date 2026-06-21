"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { SiteShell } from "@/components/layout/SiteShell";
import { staffService, getStaffToken } from "@/services/staffService";

export default function StaffPortalPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loggedIn, setLoggedIn] = useState(!!getStaffToken());

  const dashboard = useQuery({
    queryKey: ["staff-dashboard"],
    queryFn: staffService.dashboard,
    enabled: loggedIn,
  });

  const login = async (e: React.FormEvent) => {
    e.preventDefault();
    await staffService.login(email, password);
    setLoggedIn(true);
  };

  return (
    <SiteShell>
      <div className="mx-auto max-w-3xl px-4 py-16">
        <h1 className="font-serif text-4xl text-[#2a1145]">Staff Portal</h1>
        <p className="mt-2 text-[#4a3a5c]">Schedules, lesson plans & announcements — connected to ERP</p>

        {!loggedIn ? (
          <form onSubmit={login} className="mt-8 max-w-md space-y-4">
            <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="Staff email" className="w-full rounded-lg border px-3 py-2" required />
            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="Password" className="w-full rounded-lg border px-3 py-2" required />
            <button type="submit" className="rounded-full bg-[#5B2C8E] px-6 py-2 text-white">Sign in</button>
          </form>
        ) : (
          <div className="mt-8 space-y-4">
            <p className="text-sm text-green-700">Signed in — data from ERP.</p>
            <pre className="overflow-auto rounded-xl bg-[#faf6ef] p-4 text-xs">{JSON.stringify(dashboard.data, null, 2)}</pre>
            <div className="flex gap-3">
              <a href="/" className="text-sm text-[#5B2C8E] underline">Back to website</a>
            </div>
          </div>
        )}
      </div>
    </SiteShell>
  );
}
