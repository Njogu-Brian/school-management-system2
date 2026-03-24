/**
 * Aligns mobile UI with root `styles.md` (finance branding + layout tokens).
 */
export const BRAND = {
    primary: '#0f766e',
    primaryDark: '#0b5c54',
    accent: '#14b8a6',
    bg: '#f5f7fb',
    surface: '#ffffff',
    border: '#e5e7eb',
    text: '#0f172a',
    muted: '#6b7280',
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e00',
    navy: '#2d3e50',
};

export const RADIUS = { card: 15, button: 12, pill: 999 };

/** Screen chrome: headers, list rows, login shell */
export const SCREEN = {
    paddingHorizontal: 20,
    paddingVertical: 16,
    headerGap: 12,
};

export const HEADER = {
    titleSize: 22,
    subtitleSize: 14,
    iconSize: 24,
};

/** Card shadow (iOS); Android uses elevation on Card */
export const CARD_STYLE = {
    radius: RADIUS.card,
    borderWidth: 1,
    shadowOpacity: 0.08,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 3,
};
