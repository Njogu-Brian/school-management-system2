import type { BrandItem } from "@/types/brand";

export function ScriptureBanner({ items }: { items: BrandItem[] }) {
  const verse = items[0];
  if (!verse?.body) return null;

  return (
    <section className="border-y border-[var(--rk-border)] bg-[var(--rk-cream)] py-8">
      <div className="mx-auto max-w-3xl px-4 text-center">
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-[var(--rk-gold)]">Weekly Scripture</p>
        <p className="mt-3 font-serif text-lg italic leading-relaxed text-[var(--rk-purple-dark)] sm:text-xl">&ldquo;{verse.body}&rdquo;</p>
      </div>
    </section>
  );
}

export function FaithPillars({ pillars }: { pillars: BrandItem[] }) {
  if (!pillars.length) return null;

  return (
    <section className="py-10">
      <div className="mx-auto flex max-w-3xl flex-wrap justify-center gap-8 px-4">
        {pillars.map((p) => (
          <div key={p.title} className="text-center">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[var(--rk-purple)] font-serif text-lg font-bold text-white">
              {p.title?.charAt(0)}
            </div>
            <p className="mt-2 font-serif font-bold text-[var(--rk-purple)]">{p.title}</p>
            <p className="text-xs text-[var(--rk-muted)]">{p.body}</p>
          </div>
        ))}
      </div>
    </section>
  );
}
