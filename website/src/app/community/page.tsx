"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { LEGACY_TESTIMONIALS, MISSION } from "@/content/schoolContent";
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
    <RichPage>
      <PageHero title="Our Community" subtitle="Families, alumni, faith, and fellowship — growing together at Royal Kings since 2006." />
      <SectionBlock>
        <p className="mx-auto max-w-3xl text-center text-[var(--rk-muted)]">{MISSION.body}</p>
      </SectionBlock>
      <SectionBlock title="Parent Inspiration" alt>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {LEGACY_TESTIMONIALS.map((quote) => (
            <blockquote key={quote} className="rounded-2xl bg-white p-5 text-sm italic text-[var(--rk-muted)] shadow-sm ring-1 ring-[var(--rk-border)] sm:p-6">
              &ldquo;{quote}&rdquo;
              <footer className="mt-3 text-xs font-semibold not-italic text-[var(--rk-purple)]">— Parent, Royal Kings</footer>
            </blockquote>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="Refer a Family">
        <form onSubmit={submitReferral} className="mx-auto grid max-w-xl gap-3">
          <input name="referrer_name" required placeholder="Your name" className="rounded-xl border px-4 py-3 text-sm" />
          <input name="referrer_phone" required placeholder="Your phone" className="rounded-xl border px-4 py-3 text-sm" />
          <input name="referred_name" required placeholder="Family you are referring" className="rounded-xl border px-4 py-3 text-sm" />
          <button type="submit" className="rounded-full bg-[var(--rk-purple)] px-6 py-3 text-sm font-semibold text-white">Submit referral</button>
          {referralSent && <p className="text-sm text-green-700">Thank you — our admissions team will follow up.</p>}
        </form>
      </SectionBlock>
      {(community?.alumni_stories?.length ?? 0) > 0 && (
        <SectionBlock title="Alumni Stories" alt>
          <div className="grid gap-6 md:grid-cols-2">
            {community.alumni_stories.map((a: { id: number; name: string; headline: string; story: string }) => (
              <article key={a.id} className="rounded-2xl border border-[var(--rk-border)] p-6">
                <h3 className="font-serif text-xl text-[var(--rk-purple)]">{a.headline}</h3>
                <p className="text-sm text-[var(--rk-gold)]">{a.name}</p>
                <p className="mt-2 text-sm text-[var(--rk-muted)]">{a.story}</p>
              </article>
            ))}
          </div>
        </SectionBlock>
      )}
    </RichPage>
  );
}
