/**
 * Shared login chrome — school-branded palette with a stable solid page color
 * so password ↔ OTP height changes never reveal a multi-stop gradient.
 */

export type LoginAppKind = 'admin' | 'users' | 'edulynk';

export interface LoginChrome {
  /** Solid full-screen fill (never a multi-stop gradient). */
  pageBg: string;
  /** Hero gradient stops (school primary → secondary). */
  heroGradient: [string, string, string];
  /** Primary CTA / active tab / focus ring. */
  accent: string;
  accentMuted: string;
  sheetBg: string;
  sheetBorder: string;
  ink: string;
  muted: string;
  line: string;
  fieldBg: string;
  badgeLabel: string;
  badgeBg: string;
  badgeBorder: string;
  badgeText: string;
  tagline: string;
  signInTitle: string;
}

function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
  const h = hex.replace('#', '').trim();
  if (h.length === 3) {
    const r = parseInt(h[0] + h[0], 16);
    const g = parseInt(h[1] + h[1], 16);
    const b = parseInt(h[2] + h[2], 16);
    return { r, g, b };
  }
  if (h.length !== 6) return null;
  return {
    r: parseInt(h.slice(0, 2), 16),
    g: parseInt(h.slice(2, 4), 16),
    b: parseInt(h.slice(4, 6), 16),
  };
}

export function mixHex(a: string, b: string, t: number): string {
  const A = hexToRgb(a);
  const B = hexToRgb(b);
  if (!A || !B) return a;
  const r = Math.round(A.r + (B.r - A.r) * t);
  const g = Math.round(A.g + (B.g - A.g) * t);
  const bl = Math.round(A.b + (B.b - A.b) * t);
  return `#${[r, g, bl].map((x) => x.toString(16).padStart(2, '0')).join('')}`;
}

const KIND_DEFAULTS: Record<
  LoginAppKind,
  { primary: string; secondary: string; badgeLabel: string; badgeText: string; tagline: string; signInTitle: string }
> = {
  admin: {
    primary: '#0F2744',
    secondary: '#1A3A5C',
    badgeLabel: 'ADMIN',
    badgeText: '#FCD34D',
    tagline: 'School management',
    signInTitle: 'Sign in to Admin',
  },
  users: {
    primary: '#0F2744',
    secondary: '#0D9488',
    badgeLabel: 'USERS',
    badgeText: '#5EEAD4',
    tagline: 'Parents · Teachers · Students · Drivers',
    signInTitle: 'Sign in',
  },
  edulynk: {
    primary: '#071A3D',
    secondary: '#1769FF',
    badgeLabel: 'EDULYNK',
    badgeText: '#67E8F9',
    tagline: 'Admin · Staff · Parents · Students',
    signInTitle: 'Sign in to Edulynk',
  },
};

export function buildLoginChrome(opts: {
  kind: LoginAppKind;
  primary?: string | null;
  secondary?: string | null;
}): LoginChrome {
  const defaults = KIND_DEFAULTS[opts.kind];
  const primary = opts.primary?.trim()?.startsWith('#') ? opts.primary.trim() : defaults.primary;
  const secondary = opts.secondary?.trim()?.startsWith('#')
    ? opts.secondary.trim()
    : defaults.secondary;

  const primaryDark = mixHex(primary, '#000000', 0.28);
  const pageBg = mixHex(primary, '#FFFFFF', 0.92);

  const badgeBg =
    opts.kind === 'admin'
      ? 'rgba(251,191,36,0.22)'
      : opts.kind === 'edulynk'
        ? 'rgba(22,199,194,0.22)'
        : 'rgba(45,212,191,0.22)';
  const badgeBorder =
    opts.kind === 'admin'
      ? 'rgba(251,191,36,0.5)'
      : opts.kind === 'edulynk'
        ? 'rgba(22,199,194,0.5)'
        : 'rgba(45,212,191,0.45)';

  return {
    pageBg,
    heroGradient: [primaryDark, primary, secondary],
    accent: primary,
    accentMuted: mixHex(primary, '#FFFFFF', 0.85),
    sheetBg: '#FFFFFF',
    sheetBorder: '#E5E7EB',
    ink: '#111827',
    muted: '#4B5563',
    line: '#D1D5DB',
    fieldBg: '#FFFFFF',
    badgeLabel: defaults.badgeLabel,
    badgeBg,
    badgeBorder,
    badgeText: defaults.badgeText,
    tagline: defaults.tagline,
    signInTitle: defaults.signInTitle,
  };
}
