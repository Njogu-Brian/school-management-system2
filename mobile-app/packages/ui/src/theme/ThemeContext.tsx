import React, { createContext, useContext, useMemo } from 'react';
import { useColorScheme } from 'react-native';
import {
  BORDER_RADIUS,
  COLORS,
  ColorTokens,
  FONT_SIZES,
  SHADOWS,
  SPACING,
} from './tokens';

export type ThemeMode = 'light' | 'dark' | 'auto';

export interface ResolvedPalette {
  background: string;
  surface: string;
  textPrimary: string;
  textSecondary: string;
  border: string;
  accent: string;
}

export interface ThemeValue {
  isDark: boolean;
  mode: 'light' | 'dark';
  themeMode: ThemeMode;
  colors: ColorTokens;
  spacing: typeof SPACING;
  fontSizes: typeof FONT_SIZES;
  radius: typeof BORDER_RADIUS;
  shadows: typeof SHADOWS;
  palette: ResolvedPalette;
  setThemeMode: (mode: ThemeMode) => void;
  toggleTheme: () => void;
}

function resolvePalette(colors: ColorTokens, isDark: boolean): ResolvedPalette {
  return {
    background: isDark ? colors.backgroundDark : colors.backgroundLight,
    surface: isDark ? colors.surfaceDark : colors.surfaceLight,
    textPrimary: isDark ? colors.textMainDark : colors.textMainLight,
    textSecondary: isDark ? colors.textSubDark : colors.textSubLight,
    border: isDark ? colors.borderDark : colors.borderLight,
    accent: isDark ? colors.accentDark : colors.accentLight,
  };
}

const ThemeContext = createContext<ThemeValue | undefined>(undefined);

export interface ThemeProviderProps {
  children: React.ReactNode;
  /** Force a mode for testing/previews. */
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
      radius: BORDER_RADIUS,
      shadows: SHADOWS,
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
