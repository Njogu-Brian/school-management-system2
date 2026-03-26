import { COLORS } from '@constants/theme';

/** Soft vertical background for main screens — uses portal-aware colors from theme. */
export function mainBackgroundGradient(
    colors: typeof COLORS,
    isDark: boolean
): readonly [string, string, string] {
    if (isDark) {
        return [colors.backgroundDark, colors.accentDark ?? '#0a1628', colors.backgroundDark] as const;
    }
    return [colors.accentLight ?? '#e6f0fa', colors.backgroundLight, '#ffffff'] as const;
}

/** Full-screen admin app shell: light tint from branding primary + accent (not flat white). */
export function adminAppBackgroundGradient(
    colors: typeof COLORS,
    isDark: boolean
): readonly [string, string, string] {
    if (isDark) {
        const deep = colors.accentDark ?? '#061018';
        const mid = colors.backgroundDark;
        return [deep, mid, colors.primaryDark + '33'] as const;
    }
    const wash = colors.accentLight ?? '#e8f2fc';
    const mid = colors.backgroundLight ?? '#f0f5fa';
    const top = colors.primaryLight ? `${colors.primaryLight}18` : wash;
    return [top, wash, mid] as const;
}

/** Accent strip under headers / hero cards. */
export function brandHeroGradient(colors: typeof COLORS): readonly [string, string] {
    return [colors.primaryDark ?? colors.primary, colors.primary] as const;
}
