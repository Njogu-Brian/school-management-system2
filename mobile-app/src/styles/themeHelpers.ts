import { COLORS } from '@constants/theme';

type ThemeColors = typeof COLORS;

/**
 * Standard light/dark screen text + surfaces for list/detail screens.
 * Uses merged palette (portal branding + defaults).
 */
export function screenColors(isDark: boolean, colors: ThemeColors) {
    return {
        bg: isDark ? colors.backgroundDark : colors.backgroundLight,
        text: isDark ? colors.textMainDark : colors.textMainLight,
        textSub: isDark ? colors.textSubDark : colors.textSubLight,
        surface: isDark ? colors.surfaceDark : colors.surfaceLight,
        border: isDark ? colors.borderDark : colors.borderLight,
    };
}
