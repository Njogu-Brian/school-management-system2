"use client";

import Link from "next/link";
import type { PageSection } from "@/types/website";
import {
  PageHero,
  SectionBlock,
  CardGrid,
  InfoCardGrid,
  PhotoGrid,
  CtaBanner,
  StatsRow,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { SocialBarLight } from "@/components/layout/SocialBar";
import {
  sectionCta,
  sectionImage,
  sectionItems,
  sectionPhotos,
  sectionSettings,
  sectionVariant,
  splitParagraphs,
  useGalleryCatalog,
} from "@/lib/cmsSections";
import { LEGACY_GALLERY } from "@/content/legacyGallery";
import { STATS } from "@/content/schoolContent";

function RichTextBlock({ section, alt }: { section: PageSection; alt?: boolean }) {
  const image = sectionImage(section);
  const paragraphs = splitParagraphs(section.content);
  const imageRight = sectionSettings(section).image_position !== "left";

  return (
    <SectionBlock title={section.title} intro={section.subtitle} alt={alt}>
      <div className={`grid items-start gap-8 ${image ? "lg:grid-cols-2" : ""}`}>
        <div className={image && !imageRight ? "order-2 lg:order-1" : ""}>
          {paragraphs.map((p) => (
            <p key={p.slice(0, 40)} className="prose-rk mb-4 last:mb-0">
              {p}
            </p>
          ))}
        </div>
        {image && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={image}
            alt={section.title || ""}
            className={`w-full rounded-2xl object-cover shadow-lg ring-1 ring-[var(--rk-border)] ${image && !imageRight ? "order-1 lg:order-2" : ""}`}
          />
        )}
      </div>
    </SectionBlock>
  );
}

function SchoolStoryBlock({ section }: { section: PageSection }) {
  const variant = sectionVariant(section) || "empowering";
  const image = sectionImage(section);
  const cta = sectionCta(section);
  const pillars = sectionItems(section);
  const paragraphs = splitParagraphs(section.content);

  if (variant === "mission") {
    return (
      <section className="bg-white py-rk-16 lg:py-rk-20">
        <div className="rk-container grid items-center gap-rk-10 lg:grid-cols-2 lg:gap-rk-16">
          {image && (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={image} alt="" className="order-2 w-full rounded-rk-xl object-cover shadow-rk-lg lg:order-1" />
          )}
          <div className="order-1 lg:order-2">
            <p className="rk-overline text-[var(--rk-purple)]">{section.subtitle || section.title}</p>
            {paragraphs.map((p) => (
              <p key={p.slice(0, 40)} className="rk-body mt-rk-4 text-[var(--rk-muted)]">
                {p}
              </p>
            ))}
            {pillars.length > 0 && (
              <div className="mt-rk-8 grid gap-rk-4 sm:grid-cols-2">
                {pillars.map((pillar) => (
                  <article key={pillar.title} className="rounded-rk-lg bg-[var(--rk-cream)] p-rk-4 ring-1 ring-[var(--rk-border)]">
                    {pillar.icon && <span className="text-xl">{pillar.icon}</span>}
                    <h3 className="mt-rk-2 font-serif text-base font-semibold text-[var(--rk-purple)]">{pillar.title}</h3>
                    {pillar.description && (
                      <p className="mt-rk-1 line-clamp-3 text-sm text-[var(--rk-muted)]">{pillar.description}</p>
                    )}
                  </article>
                ))}
              </div>
            )}
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="bg-[var(--rk-cream)] py-rk-16 lg:py-rk-20">
      <div className="rk-container grid items-center gap-rk-10 lg:grid-cols-2 lg:gap-rk-16">
        <div>
          <p className="rk-overline text-[var(--rk-purple)]">{section.subtitle || "Since 2006"}</p>
          <h2 className="rk-h2 mt-rk-3 text-[var(--rk-text)]">{section.title}</h2>
          <div className="rk-body mt-rk-5 space-y-rk-4 text-[var(--rk-muted)]">
            {paragraphs.map((p) => (
              <p key={p.slice(0, 40)}>{p}</p>
            ))}
          </div>
          <Link href={cta.href} className="rk-btn-tertiary mt-rk-6 inline-flex">
            {cta.label}
          </Link>
        </div>
        {image && (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={image} alt="" className="w-full rounded-rk-xl object-cover shadow-rk-lg ring-1 ring-[var(--rk-border)]" />
        )}
      </div>
    </section>
  );
}

function PaymentMethodsBlock({ section }: { section: PageSection }) {
  const s = sectionSettings(section);
  const bank = (s.bank as Record<string, string>) || {};
  const mpesa = (s.mpesa as Record<string, string | string[]>) || {};
  const equity = (s.equity_paybill as Record<string, string>) || {};
  const steps = Array.isArray(mpesa.steps) ? (mpesa.steps as string[]) : [];

  return (
    <SectionBlock title={section.title || "Accepted Payment Methods"}>
      <div className="mx-auto grid max-w-4xl gap-6 lg:grid-cols-2">
        <article className="rounded-2xl bg-white p-6 shadow-md ring-1 ring-[var(--rk-border)]">
          <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">Bank Transfer — {bank.name || "Equity Bank"}</h3>
          <dl className="mt-4 space-y-2 text-sm text-[var(--rk-muted)]">
            {bank.branch && <div><dt className="font-semibold text-[var(--rk-text)]">Branch</dt><dd>{bank.branch}</dd></div>}
            {bank.account_name && <div><dt className="font-semibold text-[var(--rk-text)]">Account Name</dt><dd>{bank.account_name}</dd></div>}
            {bank.account_number && <div><dt className="font-semibold text-[var(--rk-text)]">Account Number</dt><dd className="font-mono">{bank.account_number}</dd></div>}
            {bank.swift && <div><dt className="font-semibold text-[var(--rk-text)]">SWIFT</dt><dd>{bank.swift}</dd></div>}
          </dl>
        </article>
        <article className="rounded-2xl bg-white p-6 shadow-md ring-1 ring-[var(--rk-border)]">
          <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">M-Pesa Paybill</h3>
          {mpesa.paybill && (
            <p className="mt-2 text-sm text-[var(--rk-muted)]">
              Paybill <strong>{String(mpesa.paybill)}</strong>
              {mpesa.account_hint ? ` — Account: ${String(mpesa.account_hint)}` : ""}
            </p>
          )}
          {steps.length > 0 && (
            <ol className="mt-4 list-decimal space-y-1 pl-5 text-sm text-[var(--rk-muted)]">
              {steps.map((step) => (
                <li key={step}>{step}</li>
              ))}
            </ol>
          )}
          {equity.paybill && (
            <p className="mt-4 text-sm text-[var(--rk-muted)]">
              Equity Paybill <strong>{equity.paybill}</strong>
              {equity.account_hint ? ` — ${equity.account_hint}` : ""}
            </p>
          )}
          {typeof s.notice === "string" && (
            <p className="mt-4 rounded-lg bg-[var(--rk-cream)] p-3 text-xs font-medium text-[var(--rk-purple-dark)]">{s.notice}</p>
          )}
        </article>
      </div>
    </SectionBlock>
  );
}

function ListColumnsBlock({ section, alt }: { section: PageSection; alt?: boolean }) {
  const columns = sectionItems(section);
  const s = sectionSettings(section);
  const promoImage = typeof s.promo_image === "string" ? s.promo_image : undefined;

  return (
    <SectionBlock title={section.title} intro={section.subtitle} alt={alt}>
      <div className="grid gap-8 md:grid-cols-2">
        {columns.map((col) => (
          <article key={col.title} className="rounded-2xl bg-white p-6 ring-1 ring-[var(--rk-border)]">
            <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{col.title}</h3>
            {col.description && (
              <ul className="mt-4 list-disc space-y-2 pl-5 text-sm text-[var(--rk-muted)]">
                {col.description.split("\n").filter(Boolean).map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            )}
          </article>
        ))}
      </div>
      {promoImage && (
        <div className="mt-8 flex justify-center">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={promoImage} alt="" className="max-w-xs rounded-2xl shadow-lg" />
        </div>
      )}
    </SectionBlock>
  );
}

export function PageSectionRenderer({
  section,
  index = 0,
}: {
  section: PageSection;
  index?: number;
}) {
  const alt = index % 2 === 1;
  const type = section.type;

  switch (type) {
    case "page_hero":
      return (
        <PageHero
          title={section.title || ""}
          subtitle={section.subtitle}
          image={sectionImage(section)}
        />
      );

    case "rich_text":
      return <RichTextBlock section={section} alt={alt} />;

    case "stats":
      return (
        <SectionBlock>
          <StatsRow
            stats={
              sectionItems(section).length
                ? sectionItems(section).map((i) => ({ value: i.title, label: i.description || "" }))
                : STATS
            }
          />
        </SectionBlock>
      );

    case "card_grid":
      return (
        <SectionBlock title={section.title} intro={section.subtitle} alt={alt}>
          <CardGrid items={sectionItems(section).map((i) => ({ title: i.title, description: i.description || "", icon: i.icon || "✦" }))} />
        </SectionBlock>
      );

    case "info_grid":
      return (
        <SectionBlock title={section.title} intro={section.subtitle} alt={alt}>
          <InfoCardGrid items={sectionItems(section).map((i) => ({ title: i.title, description: i.description || "", icon: i.icon }))} />
        </SectionBlock>
      );

    case "photo_grid": {
      const photos = useGalleryCatalog(section)
        ? LEGACY_GALLERY.map((p) => ({ src: p.src, title: p.title, caption: p.caption }))
        : sectionPhotos(section);
      const grid = photos.length ? photos : LEGACY_GALLERY.map((p) => ({ src: p.src, title: p.title, caption: p.caption }));
      return (
        <SectionBlock title={section.title} intro={section.subtitle} alt={alt} id={section.key}>
          <PhotoGrid photos={grid} />
        </SectionBlock>
      );
    }

    case "cta_banner": {
      const cta = sectionCta(section);
      return (
        <SectionBlock alt={alt}>
          <CtaBanner title={section.title || ""} body={section.content || section.subtitle || ""} href={cta.href} label={cta.label} />
        </SectionBlock>
      );
    }

    case "school_story":
      return <SchoolStoryBlock section={section} />;

    case "payment_methods":
      return <PaymentMethodsBlock section={section} />;

    case "list_columns":
      return <ListColumnsBlock section={section} alt={alt} />;

    case "editorial_intro":
      return (
        <SectionBlock>
          <EditorialIntro>{section.content || section.title}</EditorialIntro>
          {section.subtitle && <p className="mx-auto mt-4 max-w-3xl text-center text-sm text-[var(--rk-muted)]">{section.subtitle}</p>}
        </SectionBlock>
      );

    case "social_cta":
      return (
        <SectionBlock alt={alt}>
          <div className="flex flex-col items-center gap-6 text-center">
            <p className="text-sm font-semibold uppercase tracking-widest text-[var(--rk-purple)]">{section.title || "Stay Connected"}</p>
            <SocialBarLight />
            <CtaBanner
              title={(sectionSettings(section).cta_title as string) || section.subtitle || ""}
              body={section.content || ""}
              href={sectionCta(section).href}
              label={sectionCta(section).label}
            />
          </div>
        </SectionBlock>
      );

    default:
      if (section.title || section.content) {
        return (
          <SectionBlock title={section.title} intro={section.subtitle} alt={alt}>
            {section.content &&
              splitParagraphs(section.content).map((p) => (
                <p key={p.slice(0, 30)} className="prose-rk mx-auto mb-4 max-w-3xl">
                  {p}
                </p>
              ))}
          </SectionBlock>
        );
      }
      return null;
  }
}
