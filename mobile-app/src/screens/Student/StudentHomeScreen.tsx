import React, { useMemo, useCallback, useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, RefreshControl } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
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
import { dashboardApi, type DashboardStats } from '@api/dashboard.api';

interface Props {
    navigation: { getParent: () => { navigate: (name: string, params?: object) => void } | null };
}

export const StudentHomeScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [statsLoadError, setStatsLoadError] = useState<string | null>(null);
    const [refreshing, setRefreshing] = useState(false);

    const parentNav = () => navigation.getParent();

    const load = useCallback(async () => {
        try {
            const res = await dashboardApi.getStats();
            if (res.success && res.data) {
                setStatsLoadError(null);
                setStats(res.data);
            } else {
                setStats(null);
                setStatsLoadError(res.message || 'Could not load your dashboard.');
            }
        } catch (e: any) {
            setStats(null);
            setStatsLoadError(e?.message || 'Could not load your dashboard.');
        } finally {
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const onRefresh = () => {
        setRefreshing(true);
        void load();
    };

    const className = stats?.class_name?.trim() || null;
    const attendancePct = stats?.attendance_pct;
    const feeBalance = typeof stats?.fee_balance === 'number' ? stats.fee_balance : null;
    const pendingAssignments = stats?.pending_assignments ?? 0;
    const linked = stats?.student_id != null;

    const lineChart = stats?.charts?.line;
    const barChart = stats?.charts?.bar;
    const showLine = lineChart && lineChart.labels?.length > 0;
    const showBar =
        barChart &&
        barChart.labels?.length > 0 &&
        (barChart.values ?? []).some((v) => Number(v) > 0);

    const menuItems: DashboardMenuItem[] = useMemo(
        () => [
            {
                id: 'hw',
                title: 'Homework',
                icon: 'assignment',
                color: tileColorForIndex(0),
                onPress: () => parentNav()?.navigate('StudentHomeworkTab'),
            },
            {
                id: 'res',
                title: 'Results',
                icon: 'grade',
                color: tileColorForIndex(1),
                onPress: () => parentNav()?.navigate('StudentResultsTab'),
            },
            {
                id: 'ann',
                title: 'News',
                icon: 'campaign',
                color: tileColorForIndex(2),
                onPress: () => parentNav()?.navigate('StudentMoreTab', { screen: 'Announcements' }),
            },
            {
                id: 'bell',
                title: 'Alerts',
                icon: 'notifications',
                color: tileColorForIndex(3),
                onPress: () => parentNav()?.navigate('StudentMoreTab', { screen: 'Notifications' }),
            },
            {
                id: 'more',
                title: 'More',
                icon: 'menu',
                color: tileColorForIndex(4),
                onPress: () => parentNav()?.navigate('StudentMoreTab', { screen: 'MoreMenu' }),
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

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <ScrollView
                showsVerticalScrollIndicator={false}
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} tintColor={colors.primary} />
                }
            >
                <DashboardHero
                    greeting={greeting}
                    userName={user?.name || 'Student'}
                    roleLabel={roleLabel}
                    avatarUrl={user?.avatar}
                    showSettings={false}
                    onPressNotifications={() => parentNav()?.navigate('StudentMoreTab', { screen: 'Notifications' })}
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

                    <Text style={[styles.classTag, { color: textSub }]}>
                        {className || (linked ? 'Class' : 'Account not linked to a learner profile')}
                    </Text>

                    <View style={styles.kpiRow}>
                        <Card
                            style={{
                                ...styles.kpi,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Icon name="event-available" size={26} color={colors.success} />
                            <Text style={[styles.kpiVal, { color: textMain }]}>
                                {attendancePct != null ? `${attendancePct}%` : '—'}
                            </Text>
                            <Text style={[styles.kpiLbl, { color: textSub }]}>Attendance</Text>
                        </Card>
                        <Card
                            style={{
                                ...styles.kpi,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Icon name="payment" size={26} color={(feeBalance ?? 0) > 0 ? colors.error : colors.success} />
                            <Text
                                style={[
                                    styles.kpiVal,
                                    { color: (feeBalance ?? 0) > 0 ? colors.error : colors.success },
                                ]}
                            >
                                {feeBalance != null ? formatters.formatCurrency(feeBalance) : '—'}
                            </Text>
                            <Text style={[styles.kpiLbl, { color: textSub }]}>Fee balance</Text>
                        </Card>
                    </View>

                    <Card
                        style={{
                            ...styles.notice,
                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: isDark ? colors.borderDark : BRAND.border,
                        }}
                    >
                        <View style={styles.noticeRow}>
                            <Icon name="assignment-late" size={22} color={colors.warning} />
                            <Text style={[styles.noticeText, { color: textSub }]}>
                                {!linked
                                    ? 'Ask your school to link this login to your student record to see live attendance and fees.'
                                    : pendingAssignments > 0
                                      ? `${pendingAssignments} assignment${pendingAssignments === 1 ? '' : 's'} due soon — open Homework for details.`
                                      : 'No pending assignments shown — open Homework when your school publishes tasks.'}
                            </Text>
                        </View>
                    </Card>

                    {showLine ? (
                        <DashboardLineChart title="Attendance (recent weeks)" labels={lineChart!.labels} data={lineChart!.values} />
                    ) : null}
                    {showBar ? (
                        <DashboardBarChart title="Overview" labels={barChart!.labels} data={barChart!.values} />
                    ) : null}
                    {!statsLoadError && stats && linked && !showLine && !showBar ? (
                        <Text style={[styles.chartsHint, { color: textSub }]}>
                            Charts will appear when your school records more attendance data.
                        </Text>
                    ) : null}

                    <DashboardMenuGrid title="Go to" items={menuItems} />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { paddingBottom: SPACING.xxl },
    pad: { paddingHorizontal: SPACING.xl, paddingTop: SPACING.lg },
    classTag: { fontSize: FONT_SIZES.sm, fontWeight: '600', marginBottom: SPACING.md },
    kpiRow: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.md },
    kpi: {
        flex: 1,
        padding: SPACING.md,
        alignItems: 'center',
        gap: SPACING.xs,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
    },
    kpiVal: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    kpiLbl: { fontSize: FONT_SIZES.xs },
    notice: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.lg,
    },
    noticeRow: { flexDirection: 'row', alignItems: 'flex-start', gap: SPACING.sm },
    noticeText: { flex: 1, fontSize: FONT_SIZES.sm, lineHeight: 20 },
    chartsHint: { fontSize: FONT_SIZES.sm, lineHeight: 20, marginBottom: SPACING.md },
});
