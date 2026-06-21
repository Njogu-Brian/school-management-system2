"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { RichPage, PageHero, SectionBlock, PhotoGrid, CtaBanner } from "@/components/layout/RichPage";
import { CO_CURRICULAR, HIGHLIGHTS, LEGACY_IMAGES, LEGACY_TESTIMONIALS, MISSION } from "@/content/schoolContent";
import { SocialBarLight } from "@/components/layout/SocialBar";
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
      <PageHero
        title="Our Community"
        subtitle="Families, alumni, faith, and fellowship — growing together at Royal Kings Premier School since 2006."
        image={LEGACY_IMAGES.students}
      />
      <SectionBlock>
        <p className="mx-auto max-w-3xl text-center text-base leading-relaxed text-[var(--rk-muted)]">{MISSION.body}</p>
        <div className="mt-8 flex justify-center">
          <SocialBarLight />
        </div>
      </SectionBlock>
      <SectionBlock title="Co-Curricular Excellence" id="programs" alt>
        <p className="mx-auto mb-8 max-w-2xl text-center text-[var(--rk-muted)]">{CO_CURRICULAR.intro}</p>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {CO_CURRICULAR.programs.map((p) => (
            <article key={p.name} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-[var(--rk-border)] sm:p-6">
              <span className="text-3xl">{p.icon}</span>
              <h3 className="mt-2 font-serif text-lg font-semibold text-[var(--rk-purple)]">{p.name}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{p.detail}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="Spotlights" id="spotlights">
        <div className="grid gap-6 md:grid-cols-3">
          {HIGHLIGHTS.map((h) => (
            <article key={h.title} className="overflow-hidden rounded-2xl shadow-lg ring-1 ring-[var(--rk-border)]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={h.image} alt={h.title} className="aspect-video w-full object-cover" />
              <div className="bg-white p-5">
                <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{h.title}</h3>
                <p className="mt-2 text-sm text-[var(--rk-muted)]">{h.subtitle}</p>
              </div>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="What Parents Say" alt>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {LEGACY_TESTIMONIALS.map((t) => (
            <blockquote key={t.quote} className="rounded-2xl bg-white p-5 text-sm italic text-[var(--rk-muted)] shadow-sm ring-1 ring-[var(--rk-border)] sm:p-6">
              &ldquo;{t.quote}&rdquo;
              <footer className="mt-3 text-xs font-semibold not-italic text-[var(--rk-purple)]">— {t.role}, Royal Kings Premier School</footer>
            </blockquote>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="Refer a Family">
        <form onSubmit={submitReferral} className="mx-auto grid max-w-xl gap-3">
          <input name="referrer_name" required placeholder="Your name" className="rounded-xl border border-[var(--rk-border)] px-4 py-3 text-sm outline-none focus:border-[var(--rk-purple)]" />
          <input name="referrer_phone" required placeholder="Your phone" className="rounded-xl border border-[var(--rk-border)] px-4 py-3 text-sm outline-none focus:border-[var(--rk-purple)]" />
          <input name="referred_name" required placeholder="Family you are referring" className="rounded-xl border border-[var(--rk-border)] px-4 py-3 text-sm outline-none focus:border-[var(--rk-purple)]" />
          <button type="submit" className="rounded-full bg-[var(--rk-purple)] px-6 py-3 text-sm font-semibold text-white hover:bg-[var(--rk-purple-dark)]">
            Submit Referral
          </button>
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
      <SectionBlock alt>
        <PhotoGrid
          photos={[
            { src: LEGACY_IMAGES.students, title: "Student Life", caption: "Joyful learning every day" },
            { src: LEGACY_IMAGES.classroom, title: "In the Classroom", caption: "Dedicated teachers, engaged learners" },
            { src: LEGACY_IMAGES.campus, title: "Our Campus", caption: "Wangige, Kiambu County" },
          ]}
        />
      </SectionBlock>
      <SectionBlock>
        <CtaBanner
          title="Join the Royal Kings Family"
          body="Share the gift of quality Christian education — refer a family or follow us on social media."
          href="/admissions"
          label="Enroll Today"
        />
      </SectionBlock>
    </RichPage>
  );
}
