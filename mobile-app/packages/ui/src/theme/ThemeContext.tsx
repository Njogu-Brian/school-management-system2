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

/** Semantic colors already resolved for the active light/dark mode. */
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
  colors: ColorTokens;
  spacing: typeof SPACING;
  fontSizes: typeof FONT_SIZES;
  radius: typeof BORDER_RADIUS;
  shadows: typeof SHADOWS;
  palette: ResolvedPalette;
}

function resolvePalette(isDark: boolean): ResolvedPalette {
  return {
    background: isDark ? COLORS.backgroundDark : COLORS.backgroundLight,
    surface: isDark ? COLORS.surfaceDark : COLORS.surfaceLight,
    textPrimary: isDark ? COLORS.textMainDark : COLORS.textMainLight,
    textSecondary: isDark ? COLORS.textSubDark : COLORS.textSubLight,
    border: isDark ? COLORS.borderDark : COLORS.borderLight,
    accent: isDark ? COLORS.accentDark : COLORS.accentLight,
  };
}

const ThemeContext = createContext<ThemeValue | undefined>(undefined);

export interface ThemeProviderProps {
  children: React.ReactNode;
  /** Force a mode for testing/previews; defaults to the OS color scheme. */
  forcedMode?: 'light' | 'dark';
}

export const ThemeProvider: React.FC<ThemeProviderProps> = ({ children, forcedMode }) => {
  const scheme = useColorScheme();
  const isDark = forcedMode ? forcedMode === 'dark' : scheme === 'dark';

  const value = useMemo<ThemeValue>(
    () => ({
      isDark,
      mode: isDark ? 'dark' : 'light',
      colors: COLORS,
      spacing: SPACING,
      fontSizes: FONT_SIZES,
      radius: BORDER_RADIUS,
      shadows: SHADOWS,
      palette: resolvePalette(isDark),
    }),
    [isDark],
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export function useTheme(): ThemeValue {
  const ctx = useContext(ThemeContext);
  if (!ctx) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return ctx;
}
