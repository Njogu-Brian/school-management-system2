import { StyleSheet } from 'react-native';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

export const adminDashboardStyles = StyleSheet.create({
    flex: { flex: 1 },
    gradient: { flex: 1 },
    scrollContent: {
        padding: SPACING.xl,
        paddingBottom: SPACING.xxl,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: SPACING.lg,
    },
    headerAccent: {
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.md,
        borderRadius: BORDER_RADIUS.xl,
        marginBottom: SPACING.md,
        overflow: 'hidden',
    },
    greeting: {
        fontSize: FONT_SIZES.sm,
        marginBottom: SPACING.xs,
        fontWeight: '600',
    },
    name: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '800',
        letterSpacing: -0.3,
    },
    logoutButton: {
        padding: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
    },
    cardsContainer: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
        marginBottom: SPACING.lg,
    },
    card: {
        padding: SPACING.md,
        alignItems: 'flex-start',
    },
    iconContainer: {
        width: 48,
        height: 48,
        borderRadius: BORDER_RADIUS.xl,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.md,
    },
    cardValue: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: '800',
        marginBottom: SPACING.xs,
    },
    cardTitle: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    section: {
        marginBottom: SPACING.xl,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: '800',
        marginBottom: SPACING.md,
        letterSpacing: -0.2,
    },
    actionsContainer: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    actionButton: {
        flex: 1,
        minWidth: '28%',
        padding: SPACING.md,
        alignItems: 'center',
        gap: SPACING.xs,
    },
    actionText: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
        fontWeight: '700',
    },
    listCard: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.sm,
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.06,
        shadowRadius: 8,
        elevation: 3,
    },
    listIconWrap: {
        width: 44,
        height: 44,
        borderRadius: 22,
        alignItems: 'center',
        justifyContent: 'center',
    },
    listMain: { flex: 1 },
    listTitle: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    listSub: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    emptyBox: {
        padding: SPACING.lg,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        alignItems: 'center',
    },
    emptyText: { fontSize: FONT_SIZES.sm, textAlign: 'center' },
});
