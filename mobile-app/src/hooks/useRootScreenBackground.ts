import { useTheme } from '@contexts/ThemeContext';
import { useIsAdminBrandedApp } from '@contexts/AdminBrandedContext';
import { BRAND } from '@constants/designTokens';

/**
 * Root SafeAreaView / scroll background. Transparent when inside admin branded shell (gradient shows through).
 */
export function useRootScreenBackground(): string {
    const { isDark, colors } = useTheme();
    const adminBranded = useIsAdminBrandedApp();
    if (adminBranded) {
        return 'transparent';
    }
    return isDark ? colors.backgroundDark : BRAND.bg;
}
