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
  id,
}: {
  title?: string;
  children: React.ReactNode;
  alt?: boolean;
  id?: string;
}) {
  return (
    <section id={id} className={alt ? "bg-[var(--rk-surface)] py-12 sm:py-16 lg:py-20" : "py-12 sm:py-16 lg:py-20"}>
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

export function PhotoGrid({
  photos,
}: {
  photos: { src: string; title: string; caption?: string }[];
}) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {photos.map((photo) => (
        <figure key={photo.title + photo.src} className="rk-photo-card group">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={photo.src} alt={photo.title} className="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105" />
          <figcaption className="bg-white p-4">
            <h3 className="font-serif font-semibold text-[var(--rk-purple-dark)]">{photo.title}</h3>
            {photo.caption && <p className="mt-1 text-sm text-[var(--rk-muted)]">{photo.caption}</p>}
          </figcaption>
        </figure>
      ))}
    </div>
  );
}

export function StatsRow({ stats }: { stats: { value: string; label: string }[] }) {
  return (
    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
      {stats.map((s) => (
        <div key={s.label} className="rk-stat-pill">
          <p className="font-serif text-3xl font-bold sm:text-4xl">{s.value}</p>
          <p className="mt-1 text-sm text-white/85">{s.label}</p>
        </div>
      ))}
    </div>
  );
}

export function CtaBanner({ title, body, href, label }: { title: string; body: string; href: string; label: string }) {
  return (
    <div className="rounded-3xl bg-gradient-to-r from-[var(--rk-purple-bright)] via-[var(--rk-purple)] to-[var(--rk-purple-dark)] p-8 text-center text-white sm:p-12">
      <h2 className="font-serif text-2xl font-bold sm:text-3xl">{title}</h2>
      <p className="mx-auto mt-3 max-w-xl text-white/90">{body}</p>
      <a href={href} className="mt-6 inline-block rounded-full bg-[var(--rk-gold)] px-8 py-3 font-bold text-[var(--rk-purple-deep)] transition hover:brightness-110">
        {label}
      </a>
    </div>
  );
}

export function RichPage({ children }: { children: React.ReactNode }) {
  return <SiteShell>{children}</SiteShell>;
}
