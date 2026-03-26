/**
 * ScholarCore — single source for UI colors not already on `COLORS` / `BRAND`.
 * Edit here to retheme the app consistently.
 */
import { COLORS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';

export const Palette = {
    /** Text/icons on primary-filled buttons and chips */
    onPrimary: '#ffffff',
    /** Muted surface blocks (stats, summaries) */
    surfaceMuted: '#f8fafc',
    /** Strong destructive actions (reject, remove row) */
    destructive: '#b91c1c',
    /** Hairline borders (legacy slate) */
    borderSlate: '#e2e8f0',
    /** iOS-style switch off */
    switchTrackOff: '#767577',
    switchThumbOff: '#f4f3f4',
    /** Card / modal shadow */
    shadowIOS: '#000000',
    /** Bank transaction badge (distinct from M-Pesa / primary) */
    bank: COLORS.info,
    /** Material-style notification accent (left border / icon) */
    notificationAccent: '#2196F3',
    /** Orange priority strip */
    notificationOrange: '#ff9800',
    /** Urgent / overdue badge */
    badgeUrgent: '#ef4444',
    /** Assignment / announcement delete FAB */
    fabDanger: '#ef4444',
    /** Modal sheet on light mode */
    modalSurfaceLight: '#ffffff',
    /** List/segmented control track (neutral gray) */
    neutralTrack: '#f0f0f0',
} as const;

/** 8-digit hex alpha suffix for RN */
export const withAlpha = (hex: string, alphaHex: string): string => `${hex}${alphaHex}`;

export { COLORS, BRAND };
