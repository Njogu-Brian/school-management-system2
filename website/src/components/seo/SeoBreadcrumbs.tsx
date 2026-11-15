import Link from "next/link";

export function SeoBreadcrumbs({
  items,
}: {
  items: Array<{ name: string; href?: string }>;
}) {
  return (
    <nav aria-label="Breadcrumb" className="rk-container pt-rk-6">
      <ol className="flex flex-wrap items-center gap-2 text-sm text-rk-muted">
        {items.map((item, index) => {
          const last = index === items.length - 1;
          return (
            <li key={`${item.name}-${index}`} className="inline-flex items-center gap-2">
              {index > 0 ? <span aria-hidden>/</span> : null}
              {last || !item.href ? (
                <span className={last ? "font-semibold text-rk-deep-purple" : undefined}>{item.name}</span>
              ) : (
                <Link href={item.href} className="hover:text-rk-purple">
                  {item.name}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
