/**
 * Design System V2 tokens for @erp/ui.
 *
 * ScholarCore brand (#004A99) with modern surface hierarchy, semantic colors,
 * 4pt spacing grid, unified radius scale, and elevation system.
 */

export const COLORS = {
  primary: '#004A99',
  primaryDark: '#003366',
  primaryLight: '#1a6bc4',
  primaryMuted: '#e8f1fb',
  secondary: '#14b8a6',

  backgroundLight: '#f4f7fb',
  backgroundDark: '#0a1628',

  surfaceLight: '#ffffff',
  surfaceDark: '#111c2e',
  surfaceRaisedLight: '#ffffff',
  surfaceRaisedDark: '#162236',
  surfaceMutedLight: '#eef2f7',
  surfaceMutedDark: '#1a2740',
  surfaceOverlayLight: 'rgba(255,255,255,0.92)',
  surfaceOverlayDark: 'rgba(17,28,46,0.95)',

  textMainLight: '#0f172a',
  textMainDark: '#f8fafc',
  textSubLight: '#64748b',
  textSubDark: '#94a3b8',
  textMutedLight: '#94a3b8',
  textMutedDark: '#64748b',

  borderLight: '#e2e8f0',
  borderDark: '#334155',
  borderSubtleLight: '#f1f5f9',
  borderSubtleDark: '#1e293b',

  success: '#059669',
  successLight: '#d1fae5',
  successDark: '#047857',

  warning: '#d97706',
  warningLight: '#fef3c7',
  warningDark: '#b45309',

  error: '#dc2626',
  danger: '#dc2626',
  dangerLight: '#fee2e2',
  dangerDark: '#b91c1c',

  info: '#2563eb',
  infoLight: '#dbeafe',
  infoDark: '#1d4ed8',

  accentLight: '#e6f0fa',
  accentDark: '#002a5c',

  white: '#ffffff',
  black: '#000000',
} as const;

export type ColorTokens = typeof COLORS;

/** 4pt spacing grid */
export const SPACING = {
  unit: 4,
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 48,
  /** V2 intermediate steps */
  '2xs': 4,
  '3xs': 12,
  '2md': 20,
  '2lg': 40,
} as const;

/** Typography scale — Design System V2 */
export const TYPOGRAPHY = {
  display: { fontSize: 28, lineHeight: 34, fontWeight: '700' as const, letterSpacing: -0.5 },
  heading: { fontSize: 22, lineHeight: 28, fontWeight: '700' as const, letterSpacing: -0.3 },
  title: { fontSize: 18, lineHeight: 24, fontWeight: '600' as const, letterSpacing: 0 },
  body: { fontSize: 15, lineHeight: 22, fontWeight: '400' as const, letterSpacing: 0 },
  bodyMedium: { fontSize: 15, lineHeight: 22, fontWeight: '500' as const, letterSpacing: 0 },
  caption: { fontSize: 13, lineHeight: 18, fontWeight: '500' as const, letterSpacing: 0.1 },
  overline: { fontSize: 11, lineHeight: 14, fontWeight: '600' as const, letterSpacing: 0.6 },
} as const;

export type TypographyTokens = typeof TYPOGRAPHY;

/** Legacy font sizes — kept for backward compatibility */
export const FONT_SIZES = {
  xs: 12,
  sm: 14,
  md: 16,
  lg: 18,
  xl: 20,
  xxl: 24,
  xxxl: 32,
} as const;

/** Unified radius scale */
export const BORDER_RADIUS = {
  none: 0,
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  '2xl': 24,
  full: 9999,
  /** Semantic aliases */
  card: 16,
  control: 12,
  chip: 9999,
} as const;

/** Surface elevation system */
export const ELEVATION = {
  0: {},
  1: {
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 1,
  },
  2: {
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 6,
    elevation: 2,
  },
  3: {
    shadowColor: '#003d7a',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 12,
    elevation: 4,
  },
  4: {
    shadowColor: '#001a33',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.16,
    shadowRadius: 20,
    elevation: 8,
  },
} as const;

/** Legacy shadows — mapped to elevation levels */
export const SHADOWS = {
  sm: ELEVATION[1],
  md: ELEVATION[2],
  lg: ELEVATION[3],
} as const;

/** Semantic color roles for badges, alerts, and status indicators */
export const SEMANTIC = {
  brand: { fg: COLORS.primary, bg: COLORS.primaryMuted, border: `${COLORS.primary}33` },
  success: { fg: COLORS.success, bg: COLORS.successLight, border: `${COLORS.success}33` },
  warning: { fg: COLORS.warning, bg: COLORS.warningLight, border: `${COLORS.warning}33` },
  danger: { fg: COLORS.danger, bg: COLORS.dangerLight, border: `${COLORS.danger}33` },
  info: { fg: COLORS.info, bg: COLORS.infoLight, border: `${COLORS.info}33` },
} as const;

export type SemanticTone = keyof typeof SEMANTIC;
