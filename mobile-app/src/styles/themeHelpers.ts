import { COLORS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';

type ThemeColors = typeof COLORS;

/**
 * Standard light/dark screen text + surfaces for list/detail screens.
 */
export function screenColors(isDark: boolean, colors: ThemeColors) {
    return {
        bg: isDark ? colors.backgroundDark : BRAND.bg,
        text: isDark ? colors.textMainDark : BRAND.text,
        textSub: isDark ? colors.textSubDark : BRAND.muted,
        surface: isDark ? colors.surfaceDark : BRAND.surface,
        border: isDark ? colors.borderDark : BRAND.border,
    };
}
