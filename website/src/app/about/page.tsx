import { RichPage, PageHero, SectionBlock, CardGrid, StatsRow, CtaBanner, InfoCardGrid } from "@/components/layout/RichPage";
import { INTRO, MISSION, VISION, PILLARS, HIGHLIGHTS, STATS, FACILITIES } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";
import { SocialBarLight } from "@/components/layout/SocialBar";

export default function AboutPage() {
  return (
    <RichPage>
      <PageHero
        title="Welcome to Royal Kings"
        subtitle="Where we build a sure foundation for the little ones' future — CBC-aligned, Christian-centred, and rooted in Wangige since 2006."
        image={LEGACY_HEROES.aboutWelcome}
      />
      <SectionBlock>
        <StatsRow stats={STATS} />
      </SectionBlock>
      <SectionBlock title="Our Story">
        <div className="grid items-start gap-8 lg:grid-cols-2">
          <div className="prose-rk space-y-4">
            <p>{INTRO.legacy}</p>
            <p>{INTRO.aboutFull}</p>
            <p>
              Our school offers the Competency-Based Curriculum (CBC) to provide your child with a modern, effective approach to learning —
              a holistic journey that empowers learners to thrive in an ever-evolving world. Holistic learning goes beyond the classroom,
              encompassing character-building, creativity, and critical thinking.
            </p>
            <p>
              We wholeheartedly embrace the <strong>“Learning is Fun”</strong> philosophy, guided by our commitment to nurturing young minds
              and fostering future leaders. We do not simply educate — we inspire, empower, and mould the leaders of tomorrow.
            </p>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={LEGACY_HEROES.mission}
            alt="Royal Kings students reading together"
            className="w-full rounded-2xl object-cover shadow-xl ring-2 ring-[var(--rk-border)]"
          />
        </div>
      </SectionBlock>
      <SectionBlock title={MISSION.title} alt>
        <div className="grid items-center gap-8 lg:grid-cols-2">
          <p className="text-base leading-relaxed text-[var(--rk-muted)] sm:text-lg">{MISSION.body}</p>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={LEGACY_HEROES.empowering} alt="Learners at Royal Kings" className="rounded-2xl object-cover shadow-lg" />
        </div>
      </SectionBlock>
      <SectionBlock title={VISION.title}>
        <p className="mx-auto max-w-3xl text-center text-base leading-relaxed text-[var(--rk-muted)] sm:text-lg">{VISION.body}</p>
      </SectionBlock>
      <SectionBlock title="Facilities & Campus" alt>
        <p className="mx-auto mb-8 max-w-3xl text-center text-[var(--rk-muted)]">{FACILITIES.location}</p>
        <div className="grid gap-8 lg:grid-cols-2">
          <InfoCardGrid
            items={FACILITIES.items.map((item) => ({
              title: "Campus Feature",
              description: item,
              icon: "🏫",
            }))}
          />
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={LEGACY_HEROES.aboutFacilities}
            alt="Royal Kings campus and facilities"
            className="h-full min-h-[240px] w-full rounded-2xl object-cover shadow-lg"
          />
        </div>
      </SectionBlock>
      <SectionBlock title="What Makes Us Royal Kings">
        <CardGrid items={PILLARS} />
      </SectionBlock>
      <SectionBlock title="Highlights" alt>
        <div className="grid gap-6 sm:grid-cols-3">
          {HIGHLIGHTS.map((h) => (
            <article key={h.title} className="overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-[var(--rk-border)]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={h.image} alt={h.title} className="aspect-video w-full object-cover" />
              <div className="p-5 text-center sm:p-6">
                <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{h.title}</h3>
                <p className="mt-2 text-sm text-[var(--rk-muted)]">{h.subtitle}</p>
              </div>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock>
        <div className="flex flex-col items-center gap-6 text-center">
          <p className="text-sm font-semibold uppercase tracking-widest text-[var(--rk-purple)]">Stay Connected</p>
          <SocialBarLight />
          <CtaBanner
            title="Unleash Your Potential Today"
            body="Embrace education that goes beyond textbooks and unlocks your child's true capabilities at Royal Kings Premier School."
            href="/admissions"
            label="Start Your Adventure"
          />
        </div>
      </SectionBlock>
    </RichPage>
  );
}
