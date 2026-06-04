/**
 * Design tokens for the School ERP design system (@erp/ui).
 *
 * Mirrors the ScholarCore brand used by the existing app (primary #004A99) so both
 * binaries share one visual language. Per-tenant branding overrides resolve these
 * at runtime via ThemeContext in a later batch (see build plan §1.3 / §4.2).
 */

export const COLORS = {
  primary: '#004A99',
  primaryDark: '#003d7a',
  primaryLight: '#1a6bc4',
  secondary: '#14b8a6',

  backgroundLight: '#f0f5fa',
  backgroundDark: '#0a1628',

  surfaceLight: '#ffffff',
  surfaceDark: '#111c2e',

  textMainLight: '#0f172a',
  textMainDark: '#f8fafc',
  textSubLight: '#64748b',
  textSubDark: '#94a3b8',

  borderLight: '#E5E7EB',
  borderDark: '#334155',

  success: '#059669',
  warning: '#d97706',
  error: '#dc2626',
  info: '#2563eb',

  accentLight: '#e6f0fa',
  accentDark: '#002a5c',

  white: '#ffffff',
} as const;

export type ColorTokens = typeof COLORS;

export const SPACING = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 48,
} as const;

export const FONT_SIZES = {
  xs: 12,
  sm: 14,
  md: 16,
  lg: 18,
  xl: 20,
  xxl: 24,
  xxxl: 32,
} as const;

export const BORDER_RADIUS = {
  sm: 4,
  md: 8,
  lg: 12,
  xl: 16,
  full: 9999,
} as const;

export const SHADOWS = {
  sm: {
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 3,
    elevation: 2,
  },
  md: {
    shadowColor: '#003d7a',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 12,
    elevation: 6,
  },
  lg: {
    shadowColor: '#001a33',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.18,
    shadowRadius: 20,
    elevation: 12,
  },
} as const;
