"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { SiteShell } from "@/components/layout/SiteShell";
import { enterpriseService } from "@/services/enterpriseService";

export default function CommunityPage() {
  const { data } = useQuery({ queryKey: ["community"], queryFn: enterpriseService.community });
  const community = data?.data;
  const [referralSent, setReferralSent] = useState(false);

  const submitReferral = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    await enterpriseService.submitReferral(Object.fromEntries(fd) as Record<string, string>);
    setReferralSent(true);
    e.currentTarget.reset();
  };

  return (
    <SiteShell>
      <div className="mx-auto max-w-6xl px-4 py-16">
        <h1 className="font-serif text-4xl text-[#2a1145]">Our Community</h1>
        <p className="mt-2 text-[#4a3a5c]">Families, alumni & faith — growing together at Royal Kings</p>

        <section className="mt-12">
          <h2 className="font-serif text-2xl text-[#5B2C8E]">Refer a Family</h2>
          <form onSubmit={submitReferral} className="mt-4 grid max-w-xl gap-3">
            <input name="referrer_name" required placeholder="Your name" className="rounded-lg border px-3 py-2" />
            <input name="referrer_phone" required placeholder="Your phone" className="rounded-lg border px-3 py-2" />
            <input name="referred_name" required placeholder="Family you are referring" className="rounded-lg border px-3 py-2" />
            <button type="submit" className="rounded-full bg-[#5B2C8E] px-6 py-2 text-white">Submit referral</button>
            {referralSent && <p className="text-sm text-green-700">Thank you — our admissions team will follow up.</p>}
          </form>
        </section>

        {(community?.alumni_stories?.length ?? 0) > 0 && (
          <section className="mt-16">
            <h2 className="font-serif text-2xl text-[#5B2C8E]">Alumni Stories</h2>
            <div className="mt-6 grid gap-6 md:grid-cols-2">
              {community.alumni_stories.map((a: { id: number; name: string; headline: string; story: string }) => (
                <article key={a.id} className="rounded-2xl border p-6">
                  <h3 className="font-serif text-xl">{a.headline}</h3>
                  <p className="text-sm text-[#5B2C8E]">{a.name}</p>
                  <p className="mt-2 text-sm text-[#4a3a5c]">{a.story}</p>
                </article>
              ))}
            </div>
          </section>
        )}
      </div>
    </SiteShell>
  );
}
