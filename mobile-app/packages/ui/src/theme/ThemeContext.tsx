import React, { createContext, useContext, useMemo } from 'react';
import { useColorScheme } from 'react-native';
import {
  BORDER_RADIUS,
  COLORS,
  ColorTokens,
  ELEVATION,
  FONT_SIZES,
  SEMANTIC,
  SHADOWS,
  SPACING,
  TYPOGRAPHY,
} from './tokens';

export type ThemeMode = 'light' | 'dark' | 'auto';

export interface SurfaceHierarchy {
  background: string;
  surface: string;
  surfaceRaised: string;
  surfaceMuted: string;
  surfaceOverlay: string;
}

export interface ResolvedPalette extends SurfaceHierarchy {
  textPrimary: string;
  textSecondary: string;
  textMuted: string;
  border: string;
  borderSubtle: string;
  accent: string;
}

export interface ThemeValue {
  isDark: boolean;
  mode: 'light' | 'dark';
  themeMode: ThemeMode;
  colors: ColorTokens;
  spacing: typeof SPACING;
  fontSizes: typeof FONT_SIZES;
  typography: typeof TYPOGRAPHY;
  radius: typeof BORDER_RADIUS;
  shadows: typeof SHADOWS;
  elevation: typeof ELEVATION;
  semantic: typeof SEMANTIC;
  palette: ResolvedPalette;
  setThemeMode: (mode: ThemeMode) => void;
  toggleTheme: () => void;
}

function resolvePalette(colors: ColorTokens, isDark: boolean): ResolvedPalette {
  return {
    background: isDark ? colors.backgroundDark : colors.backgroundLight,
    surface: isDark ? colors.surfaceDark : colors.surfaceLight,
    surfaceRaised: isDark ? colors.surfaceRaisedDark : colors.surfaceRaisedLight,
    surfaceMuted: isDark ? colors.surfaceMutedDark : colors.surfaceMutedLight,
    surfaceOverlay: isDark ? colors.surfaceOverlayDark : colors.surfaceOverlayLight,
    textPrimary: isDark ? colors.textMainDark : colors.textMainLight,
    textSecondary: isDark ? colors.textSubDark : colors.textSubLight,
    textMuted: isDark ? colors.textMutedDark : colors.textMutedLight,
    border: isDark ? colors.borderDark : colors.borderLight,
    borderSubtle: isDark ? colors.borderSubtleDark : colors.borderSubtleLight,
    accent: isDark ? colors.accentDark : colors.accentLight,
  };
}

const ThemeContext = createContext<ThemeValue | undefined>(undefined);

export interface ThemeProviderProps {
  children: React.ReactNode;
  forcedMode?: 'light' | 'dark';
  themeMode?: ThemeMode;
  onThemeModeChange?: (mode: ThemeMode) => void;
  colorOverrides?: Partial<Record<keyof ColorTokens, string>>;
}

export const ThemeProvider: React.FC<ThemeProviderProps> = ({
  children,
  forcedMode,
  themeMode = 'auto',
  onThemeModeChange,
  colorOverrides,
}) => {
  const scheme = useColorScheme();
  const isDark = forcedMode
    ? forcedMode === 'dark'
    : themeMode === 'dark' || (themeMode === 'auto' && scheme === 'dark');

  const setThemeMode = (mode: ThemeMode) => {
    onThemeModeChange?.(mode);
  };

  const toggleTheme = () => {
    const next = isDark ? 'light' : 'dark';
    onThemeModeChange?.(next);
  };

  const value = useMemo<ThemeValue>(() => {
    const colors = { ...COLORS, ...colorOverrides } as ColorTokens;
    return {
      isDark,
      mode: isDark ? 'dark' : 'light',
      themeMode,
      colors,
      spacing: SPACING,
      fontSizes: FONT_SIZES,
      typography: TYPOGRAPHY,
      radius: BORDER_RADIUS,
      shadows: SHADOWS,
      elevation: ELEVATION,
      semantic: SEMANTIC,
      palette: resolvePalette(colors, isDark),
      setThemeMode,
      toggleTheme,
    };
  }, [isDark, themeMode, colorOverrides, onThemeModeChange]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export function useTheme(): ThemeValue {
  const ctx = useContext(ThemeContext);
  if (!ctx) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return ctx;
}
