import { DISTINCTIVE_EDGE } from "@/content/brandDefaults";

export function DistinctiveEdge() {
  return (
    <section className="bg-[var(--rk-surface)] py-16 sm:py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="text-center font-serif text-3xl font-bold text-[var(--rk-purple-dark)]">Our Distinctive Edge</h2>
        <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {DISTINCTIVE_EDGE.map((item) => (
            <article key={item.title} className="rounded-2xl bg-white p-6 shadow-[var(--rk-shadow-soft)] ring-1 ring-[var(--rk-border)]">
              <span className="text-2xl">{item.icon}</span>
              <h3 className="mt-2 font-serif text-lg font-semibold text-[var(--rk-purple)]">{item.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-[var(--rk-muted)]">{item.description}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
