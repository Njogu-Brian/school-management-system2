import React, { useCallback, useMemo, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { dashboardApi } from '@api/dashboard.api';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { formatters } from '@utils/formatters';
import { getDashboardRoleLabel } from '@utils/dashboardRoleLabel';
import { DashboardHero, DashboardLineChart, DashboardBarChart, DashboardMenuGrid } from '@components/dashboard';
import type { DashboardMenuItem } from '@components/dashboard';
import { tileColorForIndex } from '@styles/sections/dashboard';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import type { DashboardStats } from '@api/dashboard.api';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void; getParent: () => { navigate: (name: string, params?: object) => void } | null };
}

export const ParentDashboardScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [childrenCount, setChildrenCount] = useState<number | null>(null);
    const [balance, setBalance] = useState<number | null>(null);
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [statsLoadError, setStatsLoadError] = useState<string | null>(null);
    const [refreshing, setRefreshing] = useState(false);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        try {
            const res = await dashboardApi.getStats();
            if (res.success && res.data) {
                setStatsLoadError(null);
                setStats(res.data);
                setChildrenCount(res.data.children_count ?? null);
                setBalance(typeof res.data.total_fee_balance === 'number' ? res.data.total_fee_balance : null);
            } else {
                setStats(null);
                setChildrenCount(null);
                setBalance(null);
                setStatsLoadError(res.message || 'Could not load your dashboard.');
            }
        } catch (e: any) {
            setStats(null);
            setChildrenCount(null);
            setBalance(null);
            setStatsLoadError(e?.message || 'Could not load your dashboard.');
        } finally {
            setRefreshing(false);
        }
    }, []);

    React.useEffect(() => {
        load();
    }, [load]);

    const onRefresh = () => {
        setRefreshing(true);
        load();
    };

    const parentNav = () => navigation.getParent();

    const menuItems: DashboardMenuItem[] = useMemo(
        () => [
            {
                id: 'children',
                title: 'My children',
                icon: 'child-care',
                color: tileColorForIndex(0),
                onPress: () => parentNav()?.navigate('ParentChildrenTab', { screen: 'ChildrenList' }),
            },
            {
                id: 'fees',
                title: 'Fees & pay',
                icon: 'payment',
                color: tileColorForIndex(1),
                onPress: () => parentNav()?.navigate('ParentPaymentsTab', { screen: 'ParentPaymentsMain' }),
            },
            {
                id: 'results',
                title: 'Results',
                icon: 'assessment',
                color: tileColorForIndex(2),
                onPress: () => parentNav()?.navigate('ParentChildrenTab', { screen: 'ChildrenList' }),
            },
            {
                id: 'announce',
                title: 'News',
                icon: 'campaign',
                color: tileColorForIndex(3),
                onPress: () => parentNav()?.navigate('ParentMoreTab', { screen: 'Announcements' }),
            },
            {
                id: 'notif',
                title: 'Alerts',
                icon: 'notifications',
                color: tileColorForIndex(4),
                onPress: () => parentNav()?.navigate('ParentMoreTab', { screen: 'Notifications' }),
            },
            {
                id: 'more',
                title: 'More',
                icon: 'menu',
                color: tileColorForIndex(5),
                onPress: () => parentNav()?.navigate('ParentMoreTab', { screen: 'MoreMenu' }),
            },
        ],
        [navigation]
    );

    const greeting = useMemo(() => {
        const h = new Date().getHours();
        if (h < 12) return 'Good morning';
        if (h < 17) return 'Good afternoon';
        return 'Good evening';
    }, []);

    const roleLabel = getDashboardRoleLabel(user?.role);

    const lineChart = stats?.charts?.line;
    const barChart = stats?.charts?.bar;
    const showLine = lineChart && lineChart.labels?.length > 0;
    const showBar = barChart && barChart.labels?.length > 0;

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <ScrollView
                showsVerticalScrollIndicator={false}
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />
                }
            >
                <DashboardHero
                    greeting={greeting}
                    userName={user?.name || 'Parent'}
                    roleLabel={roleLabel}
                    avatarUrl={user?.avatar}
                    showSettings={false}
                    onPressNotifications={() => parentNav()?.navigate('ParentMoreTab', { screen: 'Notifications' })}
                />

                <View style={styles.pad}>
                    {statsLoadError ? (
                        <LoadErrorBanner
                            message={statsLoadError}
                            onRetry={() => {
                                setStatsLoadError(null);
                                setRefreshing(true);
                                void load();
                            }}
                            surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                            borderColor={isDark ? colors.borderDark : BRAND.border}
                            textColor={isDark ? colors.textMainDark : textMain}
                            subColor={isDark ? colors.textSubDark : textSub}
                            accentColor={colors.primary}
                        />
                    ) : null}
                    <View style={styles.summaryRow}>
                        <Card
                            style={{
                                ...styles.summaryCard,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Icon name="child-care" size={28} color={colors.primary} />
                            <Text style={[styles.summaryVal, { color: textMain }]}>
                                {childrenCount != null ? childrenCount : '—'}
                            </Text>
                            <Text style={[styles.summaryLbl, { color: textSub }]}>Children</Text>
                        </Card>
                        <Card
                            style={{
                                ...styles.summaryCard,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Icon name="account-balance" size={28} color={colors.error} />
                            <Text style={[styles.summaryVal, { color: textMain }]} numberOfLines={1}>
                                {balance != null ? formatters.formatCurrency(balance) : '—'}
                            </Text>
                            <Text style={[styles.summaryLbl, { color: textSub }]}>Fee balance</Text>
                        </Card>
                    </View>

                    <View style={styles.familyRow}>
                        <Avatar name={user?.name || 'P'} size={44} />
                        <View style={styles.familyText}>
                            <Text style={[styles.familyTitle, { color: textMain }]}>Family overview</Text>
                            <Text style={[styles.familySub, { color: textSub }]}>
                                Tap a shortcut below or open the Children tab for profiles and statements.
                            </Text>
                        </View>
                    </View>

                    {showLine ? (
                        <DashboardLineChart title="Attendance trend (your children)" labels={lineChart!.labels} data={lineChart!.values} />
                    ) : null}
                    {showBar ? (
                        <DashboardBarChart title="Outstanding balance by child" labels={barChart!.labels} data={barChart!.values} />
                    ) : null}
                    {!statsLoadError && stats && !showLine && !showBar ? (
                        <Text style={[styles.chartsHint, { color: textSub }]}>
                            Charts will show here once your children have attendance and fee records.
                        </Text>
                    ) : null}

                    <DashboardMenuGrid title="Shortcuts" items={menuItems} />

                    <Text style={[styles.sectionTitle, { color: textMain }]}>Quick links</Text>
                    <TouchableOpacity
                        style={[
                            styles.link,
                            {
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            },
                        ]}
                        onPress={() => parentNav()?.navigate('ParentChildrenTab', { screen: 'ChildrenList' })}
                    >
                        <Icon name="school" size={22} color={colors.primary} />
                        <Text style={[styles.linkText, { color: textMain }]}>View children & statements</Text>
                        <Icon name="chevron-right" size={22} color={textSub} />
                    </TouchableOpacity>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { paddingBottom: SPACING.xxl },
    pad: { paddingHorizontal: SPACING.xl, paddingTop: SPACING.lg },
    summaryRow: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.lg },
    summaryCard: {
        flex: 1,
        padding: SPACING.md,
        alignItems: 'center',
        gap: SPACING.xs,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
    },
    summaryVal: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    summaryLbl: { fontSize: FONT_SIZES.xs },
    familyRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        marginBottom: SPACING.lg,
    },
    familyText: { flex: 1 },
    familyTitle: { fontSize: FONT_SIZES.md, fontWeight: '800' },
    familySub: { fontSize: FONT_SIZES.sm, marginTop: 4, lineHeight: 20 },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '800', marginBottom: SPACING.sm },
    link: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        marginBottom: SPACING.sm,
        gap: SPACING.md,
    },
    linkText: { flex: 1, fontSize: FONT_SIZES.md, fontWeight: '600' },
    chartsHint: { fontSize: FONT_SIZES.sm, lineHeight: 20, marginBottom: SPACING.md },
});
