import React, { createContext, useCallback, useContext, useMemo } from 'react';
import { useColorScheme } from 'react-native';
import {
  BORDER_RADIUS,
  COLORS,
  ColorTokens,
  ELEVATION,
  FONT_SIZES,
  MOTION,
  OPACITY,
  SEMANTIC,
  SEMANTIC_DARK,
  SemanticTone,
  SemanticToneSet,
  SHADOWS,
  SPACING,
  TYPOGRAPHY,
  Z_INDEX,
} from './tokens';

export type ThemeMode = 'light' | 'dark' | 'auto';
export type SurfaceMode = 'default' | 'amoled';

export interface SurfaceHierarchy {
  background: string;
  surface: string;
  surfaceRaised: string;
  surfaceMuted: string;
  surfaceOverlay: string;
}

export interface ResolvedPalette extends SurfaceHierarchy {
  /** @deprecated Prefer textMain */
  textPrimary: string;
  /** @deprecated Prefer textSub */
  textSecondary: string;
  textMain: string;
  textSub: string;
  textMuted: string;
  textOnPrimary: string;
  textLink: string;
  border: string;
  borderSubtle: string;
  accent: string;
  primary: string;
  primaryDark: string;
  primaryLight: string;
  primaryMuted: string;
  secondary: string;
  disabled: string;
  disabledBg: string;
}

export type ResolvedSemantic = Record<SemanticTone, SemanticToneSet>;

export interface ThemeValue {
  isDark: boolean;
  mode: 'light' | 'dark';
  themeMode: ThemeMode;
  surfaceMode: SurfaceMode;
  colors: ColorTokens;
  spacing: typeof SPACING;
  fontSizes: typeof FONT_SIZES;
  typography: typeof TYPOGRAPHY;
  radius: typeof BORDER_RADIUS;
  shadows: typeof SHADOWS;
  elevation: typeof ELEVATION;
  semantic: ResolvedSemantic;
  motion: typeof MOTION;
  opacity: typeof OPACITY;
  zIndex: typeof Z_INDEX;
  palette: ResolvedPalette;
  setThemeMode: (mode: ThemeMode) => void;
  toggleTheme: () => void;
}

function resolvePalette(
  colors: ColorTokens,
  isDark: boolean,
  surfaceMode: SurfaceMode,
): ResolvedPalette {
  const amoled = isDark && surfaceMode === 'amoled';
  const background = amoled
    ? colors.backgroundAmoled
    : isDark
      ? colors.backgroundDark
      : colors.backgroundLight;
  const surface = amoled
    ? colors.surfaceAmoled
    : isDark
      ? colors.surfaceDark
      : colors.surfaceLight;
  const surfaceRaised = amoled
    ? colors.surfaceRaisedAmoled
    : isDark
      ? colors.surfaceRaisedDark
      : colors.surfaceRaisedLight;
  const surfaceMuted = amoled
    ? colors.surfaceMutedAmoled
    : isDark
      ? colors.surfaceMutedDark
      : colors.surfaceMutedLight;
  const surfaceOverlay = amoled
    ? colors.surfaceOverlayAmoled
    : isDark
      ? colors.surfaceOverlayDark
      : colors.surfaceOverlayLight;

  const textMain = isDark ? colors.textMainDark : colors.textMainLight;
  const textSub = isDark ? colors.textSubDark : colors.textSubLight;
  const textMuted = isDark ? colors.textMutedDark : colors.textMutedLight;
  const primary = isDark ? colors.primaryOnDark : colors.primary;

  return {
    background,
    surface,
    surfaceRaised,
    surfaceMuted,
    surfaceOverlay,
    textPrimary: textMain,
    textSecondary: textSub,
    textMain,
    textSub,
    textMuted,
    textOnPrimary: colors.textOnPrimary,
    textLink: primary,
    border: isDark ? colors.borderDark : colors.borderLight,
    borderSubtle: isDark ? colors.borderSubtleDark : colors.borderSubtleLight,
    accent: isDark ? colors.accentDark : colors.accentLight,
    primary,
    primaryDark: colors.primaryDark,
    primaryLight: colors.primaryLight,
    primaryMuted: isDark ? colors.primaryMutedDark : colors.primaryMuted,
    secondary: isDark ? colors.secondaryOnDark : colors.secondary,
    disabled: isDark ? colors.disabledDark : colors.disabledLight,
    disabledBg: isDark ? colors.disabledBgDark : colors.disabledBgLight,
  };
}

function resolveSemantic(isDark: boolean): ResolvedSemantic {
  return isDark ? SEMANTIC_DARK : { ...SEMANTIC };
}

const ThemeContext = createContext<ThemeValue | undefined>(undefined);

export interface ThemeProviderProps {
  children: React.ReactNode;
  forcedMode?: 'light' | 'dark';
  themeMode?: ThemeMode;
  onThemeModeChange?: (mode: ThemeMode) => void;
  /** When dark, use true-black AMOLED surfaces (V3 prepared). */
  surfaceMode?: SurfaceMode;
  colorOverrides?: Partial<Record<keyof ColorTokens, string>>;
}

export const ThemeProvider: React.FC<ThemeProviderProps> = ({
  children,
  forcedMode,
  themeMode = 'auto',
  onThemeModeChange,
  surfaceMode = 'default',
  colorOverrides,
}) => {
  const scheme = useColorScheme();
  const isDark = forcedMode
    ? forcedMode === 'dark'
    : themeMode === 'dark' || (themeMode === 'auto' && scheme === 'dark');

  const setThemeMode = useCallback(
    (mode: ThemeMode) => {
      onThemeModeChange?.(mode);
    },
    [onThemeModeChange],
  );

  const toggleTheme = useCallback(() => {
    const next = isDark ? 'light' : 'dark';
    onThemeModeChange?.(next);
  }, [isDark, onThemeModeChange]);

  const value = useMemo<ThemeValue>(() => {
    const colors = { ...COLORS, ...colorOverrides } as ColorTokens;
    return {
      isDark,
      mode: isDark ? 'dark' : 'light',
      themeMode,
      surfaceMode,
      colors,
      spacing: SPACING,
      fontSizes: FONT_SIZES,
      typography: TYPOGRAPHY,
      radius: BORDER_RADIUS,
      shadows: SHADOWS,
      elevation: ELEVATION,
      semantic: resolveSemantic(isDark),
      motion: MOTION,
      opacity: OPACITY,
      zIndex: Z_INDEX,
      palette: resolvePalette(colors, isDark, surfaceMode),
      setThemeMode,
      toggleTheme,
    };
  }, [isDark, themeMode, surfaceMode, colorOverrides, setThemeMode, toggleTheme]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export function useTheme(): ThemeValue {
  const ctx = useContext(ThemeContext);
  if (!ctx) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return ctx;
}

/** Soft theme access for primitives that may render outside ThemeProvider. */
export function useOptionalTheme(): ThemeValue | undefined {
  return useContext(ThemeContext);
}
