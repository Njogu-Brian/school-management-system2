"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { BRAND } from "@/content/schoolContent";

export function PageHero({
  title,
  subtitle,
  image,
}: {
  title: string;
  subtitle?: string;
  image?: string;
}) {
  return (
    <section className="relative overflow-hidden bg-gradient-to-br from-[var(--rk-purple)] via-[var(--rk-purple-dark)] to-[var(--rk-purple-deep)] py-16 text-white sm:py-20 lg:py-24">
      {image && (
        <div
          className="absolute inset-0 bg-cover bg-center opacity-20"
          style={{ backgroundImage: `url(${image})` }}
          aria-hidden
        />
      )}
      <div className="relative mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-[var(--rk-gold)] sm:text-sm">
          {BRAND.shortName}
        </p>
        <h1 className="mt-3 font-serif text-3xl font-bold leading-tight sm:text-4xl md:text-5xl">{title}</h1>
        {subtitle && <p className="mx-auto mt-4 max-w-2xl text-base text-white/85 sm:text-lg">{subtitle}</p>}
      </div>
    </section>
  );
}

export function SectionBlock({
  title,
  children,
  alt = false,
}: {
  title?: string;
  children: React.ReactNode;
  alt?: boolean;
}) {
  return (
    <section className={alt ? "bg-[var(--rk-surface)] py-12 sm:py-16 lg:py-20" : "py-12 sm:py-16 lg:py-20"}>
      <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        {title && (
          <h2 className="mb-8 text-center font-serif text-2xl font-bold text-[var(--rk-purple-dark)] sm:text-3xl">
            {title}
          </h2>
        )}
        {children}
      </div>
    </section>
  );
}

export function CardGrid({
  items,
}: {
  items: { title: string; description: string; icon?: string }[];
}) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
      {items.map((item) => (
        <article
          key={item.title}
          className="rounded-2xl border border-[var(--rk-border)] bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-6"
        >
          {item.icon && <span className="text-2xl sm:text-3xl">{item.icon}</span>}
          <h3 className="mt-2 font-serif text-lg font-semibold text-[var(--rk-purple)] sm:text-xl">{item.title}</h3>
          <p className="mt-2 text-sm leading-relaxed text-[var(--rk-muted)] sm:text-base">{item.description}</p>
        </article>
      ))}
    </div>
  );
}

export function RichPage({ children }: { children: React.ReactNode }) {
  return <SiteShell>{children}</SiteShell>;
}
