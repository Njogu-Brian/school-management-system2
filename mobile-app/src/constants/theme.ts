/**
 * ScholarCore brand (docs/SCHOLARCORE_UI_UX.md).
 * Primary #004A99 — keep in sync with `designTokens.BRAND` and `app.config.js` splash.
 */
export const COLORS = {
    primary: '#004A99',
    primaryDark: '#003d7a',
    primaryLight: '#1a6bc4',
    /** `finance_secondary_color` in portal; accents, links, secondary CTAs */
    secondary: '#14b8a6',

    backgroundLight: '#f0f5fa',
    backgroundDark: '#0a1628',

    surfaceLight: '#ffffff',
    surfaceDark: '#111c2e',

    textMainLight: '#0f172a',
    textMainDark: '#f8fafc',
    textSubLight: '#64748b',
    textSubDark: '#94a3b8',

    borderLight: '#E5E7EB',
    borderDark: '#334155',

    success: '#059669',
    warning: '#d97706',
    error: '#dc2626',
    info: '#2563eb',

    present: '#059669',
    absent: '#dc2626',
    late: '#d97706',
    excused: '#7c3aed',

    accentLight: '#e6f0fa',
    accentDark: '#002a5c',
};

/** Login hero: top → bottom (ScholarCore blue ramp). */
export const LOGIN_GRADIENT_LIGHT = ['#002a5c', '#003d7a', '#004A99', '#1a6bc4'] as const;
export const LOGIN_GRADIENT_DARK = ['#001a33', '#002a5c', '#003d7a', '#004A99'] as const;

export const SHADOWS = {
    sm: {
        shadowColor: '#004A99',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.08,
        shadowRadius: 3,
        elevation: 2,
    },
    md: {
        shadowColor: '#003d7a',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.12,
        shadowRadius: 12,
        elevation: 6,
    },
    lg: {
        shadowColor: '#001a33',
        shadowOffset: { width: 0, height: 8 },
        shadowOpacity: 0.18,
        shadowRadius: 20,
        elevation: 12,
    },
};

export const SPACING = {
    xs: 4,
    sm: 8,
    md: 16,
    lg: 24,
    xl: 32,
    xxl: 48,
};

export const FONT_SIZES = {
    xs: 12,
    sm: 14,
    md: 16,
    lg: 18,
    xl: 20,
    xxl: 24,
    xxxl: 32,
};

export const BORDER_RADIUS = {
    sm: 4,
    md: 8,
    lg: 12,
    xl: 16,
    full: 9999,
};
