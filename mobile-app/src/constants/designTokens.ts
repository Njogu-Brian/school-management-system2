/**
 * ScholarCore brand tokens — align with `theme.ts` and docs/SCHOLARCORE_UI_UX.md.
 */
export const BRAND = {
    primary: '#004A99',
    primaryDark: '#003d7a',
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

/** ScholarCore: 4px cards/buttons; pill for chips. */
export const RADIUS = { card: 4, button: 4, pill: 999 };

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
