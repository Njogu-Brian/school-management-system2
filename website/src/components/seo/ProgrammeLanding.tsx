import Link from "next/link";
import { SiteShell } from "@/components/layout/SiteShell";
import { PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { SeoBreadcrumbs } from "@/components/seo/SeoBreadcrumbs";
import { JsonLd } from "@/components/seo/JsonLd";
import { breadcrumbJsonLd, faqJsonLd } from "@/lib/seo";

type Block = { title: string; body: string };
type Faq = { question: string; answer: string };

export function ProgrammeLanding({
  path,
  h1,
  subtitle,
  heroImage,
  heroAlt,
  intro,
  sections,
  faqs,
  related,
}: {
  path: string;
  h1: string;
  subtitle: string;
  heroImage: string;
  heroAlt: string;
  intro: string;
  sections: Array<{ title: string; items: Block[]; alt?: boolean }>;
  faqs: Faq[];
  related: Array<{ href: string; label: string }>;
}) {
  const crumbs = [
    { name: "Home", path: "/" },
    { name: h1.split("–")[0].trim().slice(0, 48), path },
  ];

  return (
    <SiteShell>
      <JsonLd data={breadcrumbJsonLd(crumbs)} />
      <JsonLd data={faqJsonLd(faqs)} />

      <PageHero title={h1} subtitle={subtitle} image={heroImage} />
      {/* PageHero hides image from AT; expose descriptive alt for SEO via visually associated copy */}
      <span className="sr-only">{heroAlt}</span>

      <SeoBreadcrumbs
        items={[
          { name: "Home", href: "/" },
          { name: crumbs[1].name },
        ]}
      />

      <SectionBlock title="About this programme" intro={intro}>
        <div className="mx-auto max-w-3xl space-y-4 text-center">
          <p className="rk-body">
            Serving families in Wangige, Lower Kabete, Gathiga, Kibichiku and nearby areas of Kiambu County.
          </p>
          <div className="flex flex-wrap justify-center gap-3">
            <Link href="/admissions/apply" className="rk-btn rk-btn-primary cursor-pointer">
              Start application
            </Link>
            <Link href="/contact" className="rk-btn rk-btn-secondary cursor-pointer">
              Book a school visit
            </Link>
          </div>
        </div>
      </SectionBlock>

      {sections.map((section) => (
        <SectionBlock key={section.title} title={section.title} alt={section.alt}>
          <div className="grid gap-4 md:grid-cols-2">
            {section.items.map((item) => (
              <article
                key={item.title}
                className="rounded-rk-xl bg-rk-white p-5 shadow-rk-soft ring-1 ring-rk-border"
              >
                <h3 className="font-serif text-xl font-bold text-rk-deep-purple">{item.title}</h3>
                <p className="rk-body-sm mt-2">{item.body}</p>
              </article>
            ))}
          </div>
        </SectionBlock>
      ))}

      <SectionBlock title="Frequently asked questions" alt>
        <div className="mx-auto max-w-3xl space-y-4">
          {faqs.map((faq) => (
            <details
              key={faq.question}
              className="rounded-rk-xl bg-rk-white p-4 ring-1 ring-rk-border open:shadow-rk-soft"
            >
              <summary className="cursor-pointer font-serif text-lg font-semibold text-rk-deep-purple">
                {faq.question}
              </summary>
              <p className="rk-body-sm mt-3">{faq.answer}</p>
            </details>
          ))}
        </div>
      </SectionBlock>

      <SectionBlock title="Explore related pages">
        <ul className="flex flex-wrap justify-center gap-3">
          {related.map((link) => (
            <li key={link.href}>
              <Link href={link.href} className="rk-btn rk-btn-tertiary cursor-pointer">
                {link.label}
              </Link>
            </li>
          ))}
        </ul>
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Ready to join Royal Kings?"
          body="Talk to admissions about preschool, primary or junior school places in Wangige — or book a campus tour."
          href="/admissions"
          label="Admissions"
        />
      </SectionBlock>
    </SiteShell>
  );
}
