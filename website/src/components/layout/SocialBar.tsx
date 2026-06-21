import { SOCIAL } from "@/content/schoolContent";
import { FacebookIcon, InstagramIcon, TikTokIcon } from "@/components/icons/BrandIcons";

const LINKS = [
  { href: SOCIAL.facebook, label: "Facebook", Icon: FacebookIcon },
  { href: SOCIAL.instagram, label: "Instagram", Icon: InstagramIcon },
  { href: SOCIAL.tiktok, label: "TikTok", Icon: TikTokIcon },
] as const;

export function SocialBar({ compact = false }: { compact?: boolean }) {
  return (
    <div className={`flex items-center gap-2 ${compact ? "" : "gap-3"}`}>
      {LINKS.map(({ href, label, Icon }) => (
        <a
          key={label}
          href={href}
          target="_blank"
          rel="noopener noreferrer"
          className="rk-social-link"
          aria-label={`Follow us on ${label}`}
          title={label}
        >
          <Icon className="h-5 w-5" />
        </a>
      ))}
    </div>
  );
}

export function SocialBarLight() {
  return (
    <div className="flex items-center gap-3">
      {LINKS.map(({ href, label, Icon }) => (
        <a
          key={label}
          href={href}
          target="_blank"
          rel="noopener noreferrer"
          className="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--rk-surface)] text-[var(--rk-purple)] ring-1 ring-[var(--rk-border)] transition hover:bg-[var(--rk-purple)] hover:text-white"
          aria-label={`Follow us on ${label}`}
          title={label}
        >
          <Icon className="h-5 w-5" />
        </a>
      ))}
    </div>
  );
}
