import type { Metadata } from "next";
import Link from "next/link";
import { SiteShell } from "@/components/layout/SiteShell";
import { PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { JsonLd } from "@/components/seo/JsonLd";
import { SeoBreadcrumbs } from "@/components/seo/SeoBreadcrumbs";
import { CO_CURRICULAR, LEGACY_IMAGES } from "@/content/schoolContent";
import { COCURRICULAR_MEDIA } from "@/content/campusMedia";
import { breadcrumbJsonLd, pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/co-curricular");

const PROGRAMMES = [
  { name: "Skating", detail: COCURRICULAR_MEDIA.skating.caption, image: COCURRICULAR_MEDIA.skating.src, alt: COCURRICULAR_MEDIA.skating.alt },
  { name: "Ballet", detail: COCURRICULAR_MEDIA.ballet.caption, image: COCURRICULAR_MEDIA.ballet.src, alt: COCURRICULAR_MEDIA.ballet.alt },
  { name: "Coding & STEM", detail: "Digital literacy and problem-solving from primary upwards.", image: COCURRICULAR_MEDIA.coding.src, alt: COCURRICULAR_MEDIA.coding.alt },
  { name: "Music & Worship", detail: "Choir, instruments, and celebrations that put talent on stage.", image: COCURRICULAR_MEDIA.music.src, alt: COCURRICULAR_MEDIA.music.alt },
  { name: "Sports", detail: COCURRICULAR_MEDIA.sports.caption, image: COCURRICULAR_MEDIA.sports.src, alt: COCURRICULAR_MEDIA.sports.alt },
  { name: "Field trips", detail: COCURRICULAR_MEDIA.fieldTrips.caption, image: COCURRICULAR_MEDIA.fieldTrips.src, alt: COCURRICULAR_MEDIA.fieldTrips.alt },
];

export default function CoCurricularPage() {
  return (
    <SiteShell>
      <JsonLd
        data={breadcrumbJsonLd([
          { name: "Home", path: "/" },
          { name: "Co-curricular activities", path: "/co-curricular" },
        ])}
      />
      <PageHero
        title="Co-curricular Activities at Royal Kings"
        subtitle={CO_CURRICULAR.intro}
        image={LEGACY_IMAGES.students}
      />
      <SeoBreadcrumbs items={[{ name: "Home", href: "/" }, { name: "Co-curricular" }]} />

      <SectionBlock
        title="Programmes beyond the classroom"
        intro="Authentic activities that unlock gifts for learners in Wangige — from early years through junior school."
      >
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {PROGRAMMES.map((item) => (
            <article key={item.name} className="overflow-hidden rounded-rk-xl bg-rk-white shadow-rk-soft ring-1 ring-rk-border">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={item.image} alt={item.alt} className="aspect-[4/3] w-full object-cover" width={640} height={480} loading="lazy" />
              <div className="p-4">
                <h2 className="font-serif text-xl font-bold text-rk-deep-purple">{item.name}</h2>
                <p className="rk-body-sm mt-2">{item.detail}</p>
              </div>
            </article>
          ))}
        </div>
      </SectionBlock>

      <SectionBlock alt>
        <div className="mx-auto flex max-w-2xl flex-wrap justify-center gap-3">
          <Link href="/preschool-wangige" className="rk-btn rk-btn-tertiary cursor-pointer">
            Preschool
          </Link>
          <Link href="/primary-school-wangige" className="rk-btn rk-btn-tertiary cursor-pointer">
            Primary
          </Link>
          <Link href="/junior-school-wangige" className="rk-btn rk-btn-tertiary cursor-pointer">
            Junior School
          </Link>
          <Link href="/gallery" className="rk-btn rk-btn-tertiary cursor-pointer">
            Gallery
          </Link>
        </div>
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="See campus life in person"
          body="Book a school visit to experience co-curriculars and classrooms at Riverside, Wangige."
          href="/contact"
          label="Book a visit"
        />
      </SectionBlock>
    </SiteShell>
  );
}
