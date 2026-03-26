/**
 * Dashboard tile & stat colors — ScholarCore primary leads; other hues for wayfinding.
 */
import { COLORS } from '@constants/theme';

/** Rotation for quick-action grids (teacher 14 tiles, finance 6, parent/student 4). */
export const DASHBOARD_TILE_COLORS = [
    COLORS.primary,
    COLORS.info,
    COLORS.success,
    COLORS.warning,
    '#7c3aed',
    '#ec4899',
    '#06b6d4',
    '#84cc16',
    '#6366f1',
    '#22c55e',
    '#a855f7',
    '#0ea5e9',
    '#64748b',
    '#ca8a04',
    '#eab308',
] as const;

export function tileColorForIndex(indexZeroBased: number): string {
    return DASHBOARD_TILE_COLORS[indexZeroBased % DASHBOARD_TILE_COLORS.length];
}

/** Teacher / finance stat row (4 KPI cards). */
export const DASHBOARD_STAT_COLORS = [COLORS.info, COLORS.success, COLORS.warning, '#7c3aed'] as const;

/** Finance dashboard money stats + alerts */
export const FINANCE_STAT_COLORS = {
    today: COLORS.info,
    week: COLORS.success,
    month: '#7c3aed',
    pending: COLORS.warning,
    overdue: COLORS.error,
} as const;
