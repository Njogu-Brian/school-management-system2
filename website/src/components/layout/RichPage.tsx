"use client";

import Link from "next/link";
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
    <section className="relative flex min-h-[42vh] items-end overflow-hidden sm:min-h-[48vh] lg:min-h-[52vh]">
      {image ? (
        <>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={image}
            alt=""
            className="absolute inset-0 h-full w-full object-cover"
            aria-hidden
          />
          <div className="absolute inset-0 bg-rk-deep-purple/50" aria-hidden />
          <div className="absolute inset-0 bg-gradient-to-t from-rk-deep-purple/80 via-rk-deep-purple/30 to-transparent" aria-hidden />
        </>
      ) : (
        <div className="absolute inset-0 bg-rk-deep-purple" aria-hidden />
      )}

      <div className="rk-container relative z-10 pb-rk-12 pt-rk-20 text-white sm:pb-rk-16">
        <p className="rk-overline text-rk-gold">{BRAND.shortName}</p>
        <h1 className="mt-rk-3 max-w-3xl font-serif text-[clamp(2rem,4.5vw,3.25rem)] font-bold leading-tight tracking-[-0.02em]">
          {title}
        </h1>
        {subtitle && (
          <p className="rk-lead mt-rk-4 max-w-2xl text-white/90">{subtitle}</p>
        )}
      </div>
    </section>
  );
}

export function SectionBlock({
  title,
  intro,
  children,
  alt = false,
  id,
}: {
  title?: string;
  intro?: string;
  children: React.ReactNode;
  alt?: boolean;
  id?: string;
}) {
  return (
    <section
      id={id}
      className={
        alt
          ? "rk-section rk-section-cream"
          : "rk-section bg-rk-white"
      }
    >
      <div className="rk-container">
        {title && <h2 className="rk-h2 text-center">{title}</h2>}
        {intro && (
          <p className="rk-lead mx-auto mt-rk-4 max-w-3xl text-center">{intro}</p>
        )}
        <div className={title || intro ? "mt-rk-10" : ""}>{children}</div>
      </div>
    </section>
  );
}

export function InfoCard({
  title,
  description,
  icon,
}: {
  title: string;
  description: string;
  icon?: string;
}) {
  return (
    <article className="rounded-rk-xl border border-rk-border bg-rk-white p-rk-6 shadow-rk-soft transition hover:-translate-y-0.5 hover:shadow-rk-md">
      {icon && (
        <span className="flex h-10 w-10 items-center justify-center rounded-rk-lg bg-rk-soft-lavender text-lg text-rk-purple">
          {icon}
        </span>
      )}
      <h3 className="mt-rk-3 font-serif text-lg font-semibold text-rk-purple">{title}</h3>
      <p className="rk-body-sm mt-rk-2">{description}</p>
    </article>
  );
}

export function InfoCardGrid({
  items,
}: {
  items: { title: string; description: string; icon?: string }[];
}) {
  return (
    <div className="grid gap-rk-5 sm:grid-cols-2 lg:grid-cols-3">
      {items.map((item) => (
        <InfoCard key={item.title} {...item} />
      ))}
    </div>
  );
}

export function EditorialIntro({ children }: { children: React.ReactNode }) {
  return (
    <p className="rk-prose mx-auto max-w-3xl text-center text-lg">{children}</p>
  );
}

export function LeaderCard({
  name,
  role,
  bio,
  image,
}: {
  name: string;
  role?: string;
  bio?: string;
  image?: string;
}) {
  return (
    <article className="text-center">
      {image && (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={image}
          alt={name}
          className="mx-auto h-36 w-36 rounded-full object-cover ring-4 ring-rk-gold shadow-rk-md sm:h-40 sm:w-40"
        />
      )}
      <h3 className="mt-rk-5 font-serif text-xl font-bold text-rk-purple">{name}</h3>
      {role && <p className="mt-rk-1 text-sm font-semibold text-rk-gold">{role}</p>}
      {bio && <p className="rk-body-sm mx-auto mt-rk-4 max-w-xs">{bio}</p>}
    </article>
  );
}

export function CardGrid({
  items,
}: {
  items: { title: string; description: string; icon?: string }[];
}) {
  return <InfoCardGrid items={items} />;
}

export function PhotoGrid({
  photos,
}: {
  photos: { src: string; title: string; caption?: string }[];
}) {
  return (
    <div className="grid gap-rk-5 sm:grid-cols-2 lg:grid-cols-3">
      {photos.map((photo) => (
        <figure key={photo.title + photo.src} className="rk-photo-card group overflow-hidden rounded-rk-xl">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={photo.src}
            alt={photo.title}
            className="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105"
          />
          <figcaption className="bg-rk-white p-rk-4">
            <h3 className="font-serif font-semibold text-rk-deep-purple">{photo.title}</h3>
            {photo.caption && <p className="rk-caption mt-rk-1">{photo.caption}</p>}
          </figcaption>
        </figure>
      ))}
    </div>
  );
}

export function StatsRow({ stats }: { stats: { value: string; label: string }[] }) {
  return (
    <div className="grid grid-cols-2 gap-rk-4 lg:grid-cols-4">
      {stats.map((s) => (
        <div key={s.label} className="rk-stat-pill">
          <p className="font-serif text-3xl font-bold sm:text-4xl">{s.value}</p>
          <p className="mt-rk-1 text-sm text-white/85">{s.label}</p>
        </div>
      ))}
    </div>
  );
}

export function CtaBanner({
  title,
  body,
  href,
  label,
}: {
  title: string;
  body: string;
  href: string;
  label: string;
}) {
  const external = href.startsWith("http");

  return (
    <div className="rounded-rk-2xl border border-rk-border bg-rk-soft-lavender p-rk-8 text-center sm:p-rk-12">
      <h2 className="rk-h3 text-rk-deep-purple">{title}</h2>
      <p className="rk-lead mx-auto mt-rk-3 max-w-xl">{body}</p>
      {external ? (
        <a
          href={href}
          target="_blank"
          rel="noreferrer"
          className="rk-btn rk-btn-primary rk-btn-lg mt-rk-6 inline-flex cursor-pointer"
        >
          {label}
        </a>
      ) : (
        <Link href={href} className="rk-btn rk-btn-primary rk-btn-lg mt-rk-6 inline-flex cursor-pointer">
          {label}
        </Link>
      )}
    </div>
  );
}

export function RichPage({ children }: { children: React.ReactNode }) {
  return <SiteShell>{children}</SiteShell>;
}
