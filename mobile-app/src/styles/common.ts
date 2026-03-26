/**
 * Shared layout styles (ScholarCore 16px gutters — see `SCREEN` in designTokens).
 * Use with dynamic colors from `useTheme()` for text/surfaces.
 */
import { StyleSheet } from 'react-native';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND, SCREEN, RADIUS } from '@constants/designTokens';

export const layoutStyles = StyleSheet.create({
    flex1: { flex: 1 },
    screenPadded: {
        flex: 1,
        paddingHorizontal: SCREEN.paddingHorizontal,
        paddingVertical: SCREEN.paddingVertical,
    },
    row: { flexDirection: 'row', alignItems: 'center' },
    rowBetween: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
    },
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '700',
        marginBottom: SPACING.sm,
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginBottom: SPACING.md,
        lineHeight: 20,
    },
    sectionLabel: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
        letterSpacing: 0.5,
    },
    cardRadius: { borderRadius: RADIUS.card },
    separator: {
        height: StyleSheet.hairlineWidth,
        backgroundColor: BRAND.border,
    },
    hitSlop: { padding: SPACING.sm },
});

export const commonRadius = BORDER_RADIUS;
