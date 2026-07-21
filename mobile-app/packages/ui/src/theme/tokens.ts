/**
 * Design System V3 tokens for @erp/ui.
 *
 * ScholarCore brand (#004A99) with premium surface hierarchy, semantic colors,
 * 4pt spacing grid, raised radius scale, elevation 0–5, and motion tokens.
 * Backward-compatible aliases retained for V2 consumers.
 */

export const COLORS = {
  primary: '#004A99',
  /** Elevated interactive primary for dark surfaces (V3). */
  primaryOnDark: '#4B9FFF',
  primaryDark: '#003366',
  primaryLight: '#1a6bc4',
  primaryMuted: '#e8f1fb',
  primaryMutedDark: '#1a2f4a',
  secondary: '#14b8a6',
  secondaryOnDark: '#2dd4bf',

  backgroundLight: '#eef2f7',
  backgroundDark: '#0c1018',
  backgroundAmoled: '#000000',

  surfaceLight: '#ffffff',
  surfaceDark: '#151a24',
  surfaceAmoled: '#0a0a0a',
  surfaceRaisedLight: '#ffffff',
  surfaceRaisedDark: '#1c2330',
  surfaceRaisedAmoled: '#121212',
  surfaceMutedLight: '#e8edf4',
  surfaceMutedDark: '#222938',
  surfaceMutedAmoled: '#1a1a1a',
  surfaceOverlayLight: 'rgba(255,255,255,0.96)',
  surfaceOverlayDark: 'rgba(21,26,36,0.96)',
  surfaceOverlayAmoled: 'rgba(18,18,18,0.98)',

  textMainLight: '#0b1220',
  textMainDark: '#f4f7fb',
  textSubLight: '#5b6b7c',
  textSubDark: '#9aa8b8',
  textMutedLight: '#8b9aab',
  textMutedDark: '#6b7a8c',
  textOnPrimary: '#ffffff',

  borderLight: '#d8e0ea',
  borderDark: '#2a3444',
  borderSubtleLight: '#e8eef5',
  borderSubtleDark: '#1f2836',

  disabledLight: '#cbd5e1',
  disabledDark: '#475569',
  disabledBgLight: '#f1f5f9',
  disabledBgDark: '#1e293b',

  success: '#059669',
  successLight: '#d1fae5',
  successDark: '#047857',
  successFgDark: '#34d399',
  successBgDark: '#064e3b',

  warning: '#d97706',
  warningLight: '#fef3c7',
  warningDark: '#b45309',
  warningFgDark: '#fbbf24',
  warningBgDark: '#78350f',

  error: '#dc2626',
  danger: '#dc2626',
  dangerLight: '#fee2e2',
  dangerDark: '#b91c1c',
  dangerFgDark: '#f87171',
  dangerBgDark: '#7f1d1d',

  info: '#2563eb',
  infoLight: '#dbeafe',
  infoDark: '#1d4ed8',
  infoFgDark: '#60a5fa',
  infoBgDark: '#1e3a8a',

  accentLight: '#e6f0fa',
  accentDark: '#002a5c',

  white: '#ffffff',
  black: '#000000',
} as const;

export type ColorTokens = typeof COLORS;

/** 4pt spacing grid — V3 extends to 56 / 64 with readable aliases. */
export const SPACING = {
  unit: 4,
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 48,
  /** V2 intermediate steps (aliases) */
  '2xs': 4,
  '3xs': 12,
  '2md': 20,
  '2lg': 40,
  /** V3 readable aliases */
  mdSm: 12,
  mdLg: 20,
  '2xl': 40,
  '3xl': 48,
  '4xl': 56,
  '5xl': 64,
} as const;

export type TypographyRole = {
  fontSize: number;
  lineHeight: number;
  fontWeight: '400' | '500' | '600' | '700';
  letterSpacing: number;
};

/** Typography scale — Design System V3 (V2 roles kept as aliases). */
export const TYPOGRAPHY = {
  displayLarge: { fontSize: 34, lineHeight: 40, fontWeight: '700' as const, letterSpacing: -0.6 },
  display: { fontSize: 28, lineHeight: 34, fontWeight: '700' as const, letterSpacing: -0.5 },
  headlineLarge: { fontSize: 24, lineHeight: 30, fontWeight: '700' as const, letterSpacing: -0.4 },
  /** V2 `heading` alias */
  headline: { fontSize: 22, lineHeight: 28, fontWeight: '700' as const, letterSpacing: -0.3 },
  heading: { fontSize: 22, lineHeight: 28, fontWeight: '700' as const, letterSpacing: -0.3 },
  title: { fontSize: 18, lineHeight: 24, fontWeight: '600' as const, letterSpacing: 0 },
  titleSmall: { fontSize: 16, lineHeight: 22, fontWeight: '600' as const, letterSpacing: 0 },
  subtitle: { fontSize: 15, lineHeight: 22, fontWeight: '500' as const, letterSpacing: 0 },
  bodyLarge: { fontSize: 16, lineHeight: 24, fontWeight: '400' as const, letterSpacing: 0 },
  body: { fontSize: 15, lineHeight: 22, fontWeight: '400' as const, letterSpacing: 0 },
  bodyMedium: { fontSize: 15, lineHeight: 22, fontWeight: '500' as const, letterSpacing: 0 },
  button: { fontSize: 15, lineHeight: 20, fontWeight: '600' as const, letterSpacing: 0.2 },
  label: { fontSize: 13, lineHeight: 18, fontWeight: '600' as const, letterSpacing: 0.2 },
  caption: { fontSize: 13, lineHeight: 18, fontWeight: '500' as const, letterSpacing: 0.1 },
  overline: { fontSize: 11, lineHeight: 14, fontWeight: '600' as const, letterSpacing: 0.6 },
  tiny: { fontSize: 10, lineHeight: 12, fontWeight: '500' as const, letterSpacing: 0.4 },
} as const;

export type TypographyTokens = typeof TYPOGRAPHY;

/** Legacy font sizes — soft-deprecated; prefer typography.* */
export const FONT_SIZES = {
  xs: 12,
  sm: 14,
  md: 16,
  lg: 18,
  xl: 20,
  xxl: 24,
  xxxl: 32,
} as const;

/** Unified radius scale — V3 raises control/card toward premium 18–24. */
export const BORDER_RADIUS = {
  none: 0,
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  '2xl': 24,
  full: 9999,
  /** Semantic aliases (V3) */
  control: 18,
  card: 24,
  sheet: 28,
  dialog: 32,
  chip: 9999,
} as const;

/** Surface elevation system — levels 0–5 (FAB). */
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
  5: {
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 24,
    elevation: 12,
  },
} as const;

/** Legacy shadows — mapped to elevation levels */
export const SHADOWS = {
  sm: ELEVATION[1],
  md: ELEVATION[2],
  lg: ELEVATION[3],
} as const;

/** Light-mode semantic defaults (dark resolved in ThemeContext). */
export const SEMANTIC = {
  brand: { fg: COLORS.primary, bg: COLORS.primaryMuted, border: `${COLORS.primary}33` },
  success: { fg: COLORS.success, bg: COLORS.successLight, border: `${COLORS.success}33` },
  warning: { fg: COLORS.warning, bg: COLORS.warningLight, border: `${COLORS.warning}33` },
  danger: { fg: COLORS.danger, bg: COLORS.dangerLight, border: `${COLORS.danger}33` },
  info: { fg: COLORS.info, bg: COLORS.infoLight, border: `${COLORS.info}33` },
} as const;

export type SemanticTone = keyof typeof SEMANTIC;

export type SemanticToneSet = { fg: string; bg: string; border: string };

export const SEMANTIC_DARK: Record<SemanticTone, SemanticToneSet> = {
  brand: { fg: COLORS.primaryOnDark, bg: COLORS.primaryMutedDark, border: `${COLORS.primaryOnDark}44` },
  success: { fg: COLORS.successFgDark, bg: COLORS.successBgDark, border: `${COLORS.successFgDark}44` },
  warning: { fg: COLORS.warningFgDark, bg: COLORS.warningBgDark, border: `${COLORS.warningFgDark}44` },
  danger: { fg: COLORS.dangerFgDark, bg: COLORS.dangerBgDark, border: `${COLORS.dangerFgDark}44` },
  info: { fg: COLORS.infoFgDark, bg: COLORS.infoBgDark, border: `${COLORS.infoFgDark}44` },
};

/** Motion tokens — V3 */
export const MOTION = {
  duration: {
    fast: 150,
    medium: 250,
    slow: 400,
  },
  easing: {
    standard: 'ease-in-out' as const,
    emphasized: 'cubic-bezier(0.2, 0, 0, 1)' as const,
    decelerate: 'cubic-bezier(0, 0, 0.2, 1)' as const,
    accelerate: 'cubic-bezier(0.4, 0, 1, 1)' as const,
  },
} as const;

export const OPACITY = {
  disabled: 0.4,
  pressed: 0.72,
  scrim: 0.45,
  hover: 0.08,
} as const;

export const Z_INDEX = {
  base: 0,
  sticky: 10,
  fab: 20,
  nav: 30,
  sheet: 40,
  dialog: 50,
  toast: 60,
} as const;
