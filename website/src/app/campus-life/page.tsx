import Link from "next/link";
import { RichPage, PageHero, SectionBlock, PhotoGrid, CtaBanner } from "@/components/layout/RichPage";
import { CAMPUS_LIFE, GALLERY_PHOTOS, HIGHLIGHTS, LEGACY_IMAGES, CONTACT } from "@/content/schoolContent";

export default function CampusLifePage() {
  return (
    <RichPage>
      <PageHero title="Campus & Gallery" subtitle={CAMPUS_LIFE.intro} image={LEGACY_IMAGES.campus} />
      <SectionBlock title="Life at Royal Kings Premier School">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {CAMPUS_LIFE.features.map((feature) => (
            <article key={feature.title} className="rounded-2xl bg-[var(--rk-surface)] p-5 ring-1 ring-[var(--rk-border)] sm:p-6">
              <h3 className="font-serif text-lg font-semibold text-[var(--rk-purple)]">{feature.title}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)] sm:text-base">{feature.detail}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock alt>
        <div className="grid items-center gap-8 lg:grid-cols-2">
          <div>
            <h2 className="font-serif text-2xl font-bold text-[var(--rk-purple-dark)] sm:text-3xl">A Home Away From Home</h2>
            <p className="prose-rk mt-4">
              From morning assembly to afternoon sports, every moment at Royal Kings Premier School is designed to help children feel safe,
              loved, and inspired. Our Wangige campus blends modern learning spaces with warm, family-centered care — the same experience
              families have trusted since 2006.
            </p>
            <div className="mt-6 flex flex-wrap gap-3">
              <a href={CONTACT.mapsUrl} target="_blank" rel="noreferrer" className="rounded-full bg-[var(--rk-purple)] px-5 py-2 text-sm font-semibold text-white">
                Visit Us on Maps
              </a>
              <Link href="/admissions" className="rounded-full border-2 border-[var(--rk-purple)] px-5 py-2 text-sm font-semibold text-[var(--rk-purple)]">
                Book a Tour
              </Link>
            </div>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={LEGACY_IMAGES.campus} alt="Royal Kings campus" className="w-full rounded-2xl object-cover shadow-xl" />
        </div>
      </SectionBlock>
      <SectionBlock title="Photo Gallery" id="gallery">
        <PhotoGrid photos={GALLERY_PHOTOS} />
      </SectionBlock>
      <SectionBlock title="Upcoming Highlights" alt id="events">
        <div className="grid gap-6 md:grid-cols-3">
          {HIGHLIGHTS.map((h) => (
            <article key={h.title} className="overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-[var(--rk-border)]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={h.image} alt={h.title} className="aspect-[16/10] w-full object-cover" />
              <div className="p-5">
                <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{h.title}</h3>
                <p className="mt-2 text-sm text-[var(--rk-muted)]">{h.subtitle}</p>
              </div>
            </article>
          ))}
        </div>
        <p className="mt-8 text-center text-sm text-[var(--rk-muted)]">
          Follow us on social media for the latest events, open days, and talent camp updates.
        </p>
      </SectionBlock>
      <SectionBlock title="Virtual Campus Tour" id="tour">
        <div className="grid items-center gap-8 lg:grid-cols-2">
          <div className="overflow-hidden rounded-2xl shadow-xl ring-1 ring-[var(--rk-border)]">
            <iframe
              title="Royal Kings Premier School location"
              src={CONTACT.mapsEmbed}
              className="h-[280px] w-full sm:h-[360px]"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              allowFullScreen
            />
          </div>
          <div>
            <h3 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)]">Explore Wangige Campus</h3>
            <p className="prose-rk mt-4">
              Walk our grounds, meet our teachers, and see why families from Wangige, Lower Kabete, Kikuyu, Gitaru, and Uthiru choose
              Royal Kings Premier School. Schedule an in-person tour or explore our location on Google Maps.
            </p>
            <div className="mt-6 grid grid-cols-2 gap-3">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={LEGACY_IMAGES.classroom} alt="Classroom" className="rounded-xl object-cover shadow" />
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={LEGACY_IMAGES.students} alt="Students" className="rounded-xl object-cover shadow" />
            </div>
          </div>
        </div>
      </SectionBlock>
      <SectionBlock alt>
        <CtaBanner
          title="2025 Admissions Open"
          body="Limited spaces available. Enroll your child at Royal Kings Premier School today."
          href="/admissions"
          label="Apply Now"
        />
      </SectionBlock>
    </RichPage>
  );
}
