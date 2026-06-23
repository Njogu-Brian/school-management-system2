import Link from "next/link";
import { RichPage, PageHero, SectionBlock, PhotoGrid, CtaBanner } from "@/components/layout/RichPage";
import { CAMPUS_LIFE, HIGHLIGHTS, CONTACT } from "@/content/schoolContent";
import { LEGACY_GALLERY, LEGACY_HEROES } from "@/content/legacyGallery";

export default function CampusLifePage() {
  return (
    <RichPage>
      <PageHero title="Campus & Gallery" subtitle={CAMPUS_LIFE.intro} image={LEGACY_HEROES.playground} />
      <SectionBlock title="Life at Royal Kings Premier School">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {CAMPUS_LIFE.features.map((feature, i) => {
            const thumb = LEGACY_GALLERY[i % LEGACY_GALLERY.length];
            return (
              <article key={feature.title} className="overflow-hidden rounded-2xl bg-[var(--rk-surface)] ring-1 ring-[var(--rk-border)]">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={thumb.src} alt={feature.title} className="aspect-[16/10] w-full object-cover" />
                <div className="p-5 sm:p-6">
                  <h3 className="font-serif text-lg font-semibold text-[var(--rk-purple)]">{feature.title}</h3>
                  <p className="mt-2 text-sm text-[var(--rk-muted)] sm:text-base">{feature.detail}</p>
                </div>
              </article>
            );
          })}
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
            <p className="prose-rk mt-4">
              <strong>Learning is Fun!</strong> — that is not just a slogan on our walls. It is how we teach, play, worship, and grow together
              every single day.
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
          <img src={LEGACY_HEROES.community} alt="Royal Kings campus community" className="w-full rounded-2xl object-cover shadow-xl" />
        </div>
      </SectionBlock>
      <SectionBlock title="A Sneak Peek — Learning is Fun!" intro="Real moments from our Wangige campus — classrooms, sports, arts, devotions, and celebrations." id="gallery">
        <PhotoGrid photos={LEGACY_GALLERY.map((p) => ({ src: p.src, title: p.title, caption: p.caption }))} />
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
              Riverside Wangige, along the Western Bypass — walk our grounds, meet our teachers, and see why families choose Royal Kings.
            </p>
            <div className="mt-6 grid grid-cols-2 gap-3">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={LEGACY_HEROES.sports} alt="Sports at Royal Kings" className="rounded-xl object-cover shadow" />
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={LEGACY_HEROES.arts} alt="Arts at Royal Kings" className="rounded-xl object-cover shadow" />
            </div>
          </div>
        </div>
      </SectionBlock>
      <SectionBlock alt>
        <CtaBanner title="2025 Admissions Open" body="Limited spaces available. Enroll your child at Royal Kings Premier School today." href="/admissions" label="Apply Now" />
      </SectionBlock>
    </RichPage>
  );
}
