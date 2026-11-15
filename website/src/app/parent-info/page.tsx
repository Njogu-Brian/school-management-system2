import type { Metadata } from "next";
import Link from "next/link";
import { SiteShell } from "@/components/layout/SiteShell";
import { PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { JsonLd } from "@/components/seo/JsonLd";
import { SeoBreadcrumbs } from "@/components/seo/SeoBreadcrumbs";
import { PARENT_INFO_FAQS } from "@/content/seoLandingContent";
import { LEGACY_IMAGES } from "@/content/schoolContent";
import { breadcrumbJsonLd, faqJsonLd, pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/parent-info");

export default function ParentInfoPage() {
  return (
    <SiteShell>
      <JsonLd
        data={breadcrumbJsonLd([
          { name: "Home", path: "/" },
          { name: "Parent information", path: "/parent-info" },
        ])}
      />
      <JsonLd data={faqJsonLd(PARENT_INFO_FAQS)} />

      <PageHero
        title="Parent information about Royal Kings Premier School"
        subtitle="Clear answers for families considering preschool, primary or junior school in Wangige"
        image={LEGACY_IMAGES.about}
      />
      <SeoBreadcrumbs items={[{ name: "Home", href: "/" }, { name: "Parent information" }]} />

      <SectionBlock
        title="What parents ask most"
        intro="These answers match our public school information and help families — and search tools — understand Royal Kings clearly."
      >
        <div className="mx-auto max-w-3xl space-y-4">
          {PARENT_INFO_FAQS.map((faq) => (
            <article key={faq.question} className="rounded-rk-xl bg-rk-white p-5 ring-1 ring-rk-border">
              <h2 className="font-serif text-xl font-bold text-rk-deep-purple">{faq.question}</h2>
              <p className="rk-body-sm mt-2">{faq.answer}</p>
            </article>
          ))}
        </div>
      </SectionBlock>

      <SectionBlock title="Programme pages" alt>
        <ul className="flex flex-wrap justify-center gap-3">
          <li>
            <Link href="/preschool-wangige" className="rk-btn rk-btn-tertiary cursor-pointer">
              Preschool Wangige
            </Link>
          </li>
          <li>
            <Link href="/primary-school-wangige" className="rk-btn rk-btn-tertiary cursor-pointer">
              Primary School
            </Link>
          </li>
          <li>
            <Link href="/junior-school-wangige" className="rk-btn rk-btn-tertiary cursor-pointer">
              Junior School
            </Link>
          </li>
          <li>
            <Link href="/admissions" className="rk-btn rk-btn-tertiary cursor-pointer">
              Admissions
            </Link>
          </li>
        </ul>
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Talk to admissions"
          body="Call, WhatsApp, or book a visit to Riverside, Wangige."
          href="/contact"
          label="Contact us"
        />
      </SectionBlock>
    </SiteShell>
  );
}
