/**
 * ScholarCore brand tokens — align with `theme.ts` and docs/SCHOLARCORE_UI_UX.md.
 */
export const BRAND = {
    primary: '#004A99',
    primaryDark: '#003d7a',
    secondary: '#14b8a6',
    accent: '#1a6bc4',
    bg: '#f0f5fa',
    surface: '#ffffff',
    border: '#E5E7EB',
    text: '#0f172a',
    muted: '#64748b',
    success: '#059669',
    danger: '#dc2626',
    warning: '#d97706',
    navy: '#002a5c',
};

/** Slightly generous radius for 2024+ mobile; pill for filters/chips. */
export const RADIUS = { card: 12, button: 10, pill: 999 };

export const SCREEN = {
    paddingHorizontal: 16,
    paddingVertical: 16,
    headerGap: 12,
};

export const HEADER = {
    titleSize: 22,
    subtitleSize: 14,
    iconSize: 24,
};

export const CARD_STYLE = {
    radius: RADIUS.card,
    borderWidth: 1,
    shadowOpacity: 0.1,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 5,
};
