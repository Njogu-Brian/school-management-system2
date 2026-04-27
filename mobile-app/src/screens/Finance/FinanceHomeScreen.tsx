import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigation } from '@react-navigation/native';
import { View, Text, StyleSheet, SafeAreaView, TouchableOpacity, ScrollView, RefreshControl, Alert } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { getDashboardRoleLabel } from '@utils/dashboardRoleLabel';
import { DashboardHero, DashboardLineChart, DashboardBarChart, DashboardMenuGrid } from '@components/dashboard';
import type { DashboardMenuItem } from '@components/dashboard';
import { tileColorForIndex, FINANCE_STAT_COLORS } from '@styles/sections/dashboard';
import { financeApi } from '@api/finance.api';
import { Payment } from 'types/finance.types';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const FinanceHomeScreen: React.FC<Props> = ({ navigation }) => {
    const rootNav = useNavigation<any>();
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);

    const [stats, setStats] = useState({
        todayCollection: 0,
        weekCollection: 0,
        monthCollection: 0,
        pendingInvoices: 0,
        overduePayments: 0,
    });

    const [recentPayments, setRecentPayments] = useState<
        { id: number; student: string; amount: number; method: string; time: string }[]
    >([]);
    const [loadError, setLoadError] = useState<string | null>(null);

    const loadDashboardData = useCallback(
        async (isRefresh: boolean = false) => {
            try {
                if (!isRefresh) {
                    setLoading(true);
                }

                const [summaryRes, pendingRes, overdueRes, recentPaymentsRes] = await Promise.all([
                    financeApi.getFinanceSummary(),
                    financeApi.getInvoices({ status: 'issued', page: 1, per_page: 1 }),
                    financeApi.getInvoices({ status: 'overdue', page: 1, per_page: 1 }),
                    financeApi.getPayments({ page: 1, per_page: 5, active_only: true }),
                ]);

                const summary = summaryRes.data;
                setStats({
                    todayCollection: Number(summary?.payments_today ?? 0),
                    weekCollection: Number(summary?.payments_this_week ?? 0),
                    monthCollection: Number(summary?.payments_this_month ?? 0),
                    pendingInvoices: Number(pendingRes.data?.total ?? 0),
                    overduePayments: Number(overdueRes.data?.total ?? 0),
                });

                const payments = recentPaymentsRes.data?.data ?? [];
                setRecentPayments(
                    payments.map((payment: Payment) => ({
                        id: payment.id,
                        student: payment.student_name || 'Student',
                        amount: Number(payment.amount ?? 0),
                        method: formatters.capitalizeWords(String(payment.payment_method || '').replace(/_/g, ' ')),
                        time: formatters.getRelativeTime(payment.payment_date || payment.created_at),
                    }))
                );
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load finance dashboard data');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        []
    );

    const handleRefresh = () => {
        setRefreshing(true);
        loadDashboardData(true);
    };

    useEffect(() => {
        loadDashboardData();
    }, [loadDashboardData]);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const menuItems: DashboardMenuItem[] = useMemo(
        () =>
            [
                { id: '1', title: 'Record payment', icon: 'payment', screen: 'RecordPayment' as const },
                { id: '2', title: 'Invoices', icon: 'receipt-long', screen: 'InvoicesList' as const },
                { id: '3', title: 'Payments', icon: 'payments', screen: 'PaymentsList' as const },
                { id: '4', title: 'Defaulters', icon: 'warning', screen: 'Defaulters' as const },
                { id: '5', title: 'Fee structures', icon: 'account-balance', screen: 'FeeStructures' as const },
                { id: '6', title: 'Receipts', icon: 'receipt', screen: 'Receipts' as const },
            ].map((a, i) => ({
                id: a.id,
                title: a.title,
                icon: a.icon,
                color: tileColorForIndex(i),
                onPress: () => navigation.navigate(a.screen),
            })),
        [navigation]
    );

    const greeting = useMemo(() => {
        const h = new Date().getHours();
        if (h < 12) return 'Good morning';
        if (h < 17) return 'Good afternoon';
        return 'Good evening';
    }, []);

    const roleLabel = getDashboardRoleLabel(user?.role);

    const collectionsTrend = useMemo(() => {
        if (!recentPayments.length) {
            return { labels: ['No data'], data: [0] };
        }
        const ordered = [...recentPayments].slice().reverse();
        return {
            labels: ordered.map((p) => p.time),
            data: ordered.map((p) => p.amount),
        };
    }, [recentPayments]);

    const channelTotals = useMemo(() => {
        if (!recentPayments.length) {
            return { labels: ['No data'], data: [0] };
        }
        const totals = new Map<string, number>();
        for (const p of recentPayments) {
            totals.set(p.method, (totals.get(p.method) || 0) + p.amount);
        }
        const entries = Array.from(totals.entries());
        return {
            labels: entries.map(([label]) => label),
            data: entries.map(([, amount]) => amount),
        };
    }, [recentPayments]);

    const renderStat = (title: string, value: number | string, icon: string, color: string, isAmount = false) => (
        <Card
            style={{
                ...styles.statCard,
                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                borderColor: isDark ? colors.borderDark : BRAND.border,
            }}
        >
            <View style={[styles.statIcon, { backgroundColor: color + '22' }]}>
                <Icon name={icon} size={22} color={color} />
            </View>
            <Text style={[styles.statValue, { color: textMain }]}>
                {isAmount ? formatters.formatCurrency(value as number) : value}
            </Text>
            <Text style={[styles.statLabel, { color: textSub }]}>{title}</Text>
        </Card>
    );

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <ScrollView
                showsVerticalScrollIndicator={false}
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                }
            >
                <DashboardHero
                    greeting={greeting}
                    userName={user?.name || 'Finance'}
                    roleLabel={roleLabel}
                    avatarUrl={user?.avatar}
                    showSettings={false}
                    onPressNotifications={() => {
                        const nav = rootNav.getParent?.() ?? rootNav;
                        nav.navigate('More', { screen: 'Notifications' });
                    }}
                />

                <View style={styles.pad}>
                    {loadError ? (
                        <LoadErrorBanner
                            message={loadError}
                            onRetry={() => {
                                setLoadError(null);
                                setRefreshing(true);
                                loadDashboardData(true);
                            }}
                            surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                            borderColor={isDark ? colors.borderDark : BRAND.border}
                            textColor={isDark ? colors.textMainDark : textMain}
                            subColor={isDark ? colors.textSubDark : textSub}
                            accentColor={colors.primary}
                        />
                    ) : null}
                    <Text style={[styles.sectionTitle, { color: textMain }]}>Collections</Text>
                    <View style={styles.statsRow}>
                        {renderStat('Today', stats.todayCollection, 'today', FINANCE_STAT_COLORS.today, true)}
                        {renderStat('This week', stats.weekCollection, 'date-range', FINANCE_STAT_COLORS.week, true)}
                    </View>
                    <View style={styles.statsRow}>
                        {renderStat('This month', stats.monthCollection, 'calendar-month', FINANCE_STAT_COLORS.month, true)}
                    </View>

                    <Text style={[styles.sectionTitle, { color: textMain, marginTop: SPACING.md }]}>Attention</Text>
                    <View style={styles.statsRow}>
                        {renderStat('Pending invoices', stats.pendingInvoices, 'receipt-long', FINANCE_STAT_COLORS.pending, false)}
                        {renderStat('Overdue', stats.overduePayments, 'warning', FINANCE_STAT_COLORS.overdue, false)}
                    </View>

                    <DashboardLineChart
                        title="Collections trend"
                        labels={collectionsTrend.labels}
                        data={collectionsTrend.data}
                    />
                    <DashboardBarChart title="By channel" labels={channelTotals.labels} data={channelTotals.data} />

                    <DashboardMenuGrid title="Finance workspace" items={menuItems} />

                    <View style={styles.sectionHead}>
                        <Text style={[styles.sectionTitle, { color: textMain, marginBottom: 0 }]}>Recent payments</Text>
                        <TouchableOpacity onPress={() => navigation.navigate('PaymentsList')}>
                            <Text style={[styles.viewAll, { color: colors.primary }]}>View all</Text>
                        </TouchableOpacity>
                    </View>

                    {loading ? (
                        <Card
                            style={{
                                ...styles.payCard,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Text style={[styles.payMeta, { color: textSub }]}>Loading recent payments...</Text>
                        </Card>
                    ) : recentPayments.length === 0 ? (
                        <Card
                            style={{
                                ...styles.payCard,
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            }}
                        >
                            <Text style={[styles.payMeta, { color: textSub }]}>No recent payments available.</Text>
                        </Card>
                    ) : (
                        recentPayments.map((p) => (
                            <Card
                                key={p.id}
                                style={{
                                    ...styles.payCard,
                                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                    borderColor: isDark ? colors.borderDark : BRAND.border,
                                }}
                            >
                                <View style={styles.payRow}>
                                    <View style={[styles.payIcon, { backgroundColor: colors.success + '22' }]}>
                                        <Icon name="check-circle" size={20} color={colors.success} />
                                    </View>
                                    <View style={styles.payInfo}>
                                        <Text style={[styles.payName, { color: textMain }]}>{p.student}</Text>
                                        <Text style={[styles.payMeta, { color: textSub }]}>
                                            {p.method} · {p.time}
                                        </Text>
                                    </View>
                                    <Text style={[styles.payAmt, { color: colors.success }]}>
                                        {formatters.formatCurrency(p.amount)}
                                    </Text>
                                </View>
                            </Card>
                        ))
                    )}

                    <Card
                        style={{
                            ...styles.hint,
                            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                            borderColor: isDark ? colors.borderDark : BRAND.border,
                        }}
                    >
                        <Icon name="info-outline" size={20} color={colors.info} />
                        <Text style={[styles.hintText, { color: textSub }]}>
                            Batch invoicing and advanced reports are available in the web portal. Use this app to record payments
                            and review lists on the go.
                        </Text>
                    </Card>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    scroll: { paddingBottom: SPACING.xxl },
    pad: { paddingHorizontal: SPACING.xl, paddingTop: SPACING.lg },
    sectionTitle: {
        fontSize: FONT_SIZES.md,
        fontWeight: '800',
        marginBottom: SPACING.sm,
    },
    sectionHead: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.sm,
        marginTop: SPACING.sm,
    },
    viewAll: { fontSize: FONT_SIZES.sm, fontWeight: '700' },
    statsRow: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.sm },
    statCard: {
        flex: 1,
        padding: SPACING.md,
        alignItems: 'center',
        gap: SPACING.xs,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
    },
    statIcon: {
        width: 44,
        height: 44,
        borderRadius: BORDER_RADIUS.lg,
        alignItems: 'center',
        justifyContent: 'center',
    },
    statValue: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    statLabel: { fontSize: FONT_SIZES.xs, textAlign: 'center' },
    payCard: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.sm,
    },
    payRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm },
    payIcon: {
        width: 40,
        height: 40,
        borderRadius: 20,
        alignItems: 'center',
        justifyContent: 'center',
    },
    payInfo: { flex: 1 },
    payName: { fontSize: FONT_SIZES.sm, fontWeight: '700' },
    payMeta: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    payAmt: { fontSize: FONT_SIZES.md, fontWeight: '800' },
    hint: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        gap: SPACING.sm,
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        marginTop: SPACING.md,
    },
    hintText: { flex: 1, fontSize: FONT_SIZES.sm, lineHeight: 20 },
});
