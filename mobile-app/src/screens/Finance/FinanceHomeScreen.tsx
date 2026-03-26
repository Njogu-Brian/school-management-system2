import React, { useMemo, useState } from 'react';
import { useNavigation } from '@react-navigation/native';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    TouchableOpacity,
    ScrollView,
    RefreshControl,
} from 'react-native';
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
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
}

export const FinanceHomeScreen: React.FC<Props> = ({ navigation }) => {
    const rootNav = useNavigation<any>();
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);

    const [stats] = useState({
        todayCollection: 125000,
        weekCollection: 580000,
        monthCollection: 2340000,
        pendingInvoices: 45,
        overduePayments: 12,
    });

    const [recentPayments] = useState([
        { id: 1, student: 'John Doe', amount: 15000, method: 'M-Pesa', time: '10:30 AM' },
        { id: 2, student: 'Jane Smith', amount: 25000, method: 'Bank', time: '09:15 AM' },
    ]);

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 1000);
    };

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
                        title="Collections trend (sample)"
                        labels={['Mon', 'Tue', 'Wed', 'Thu', 'Fri']}
                        data={[45, 52, 38, 61, 55]}
                    />
                    <DashboardBarChart title="By channel (sample)" labels={['M-Pesa', 'Bank', 'Cash']} data={[120, 80, 40]} />

                    <DashboardMenuGrid title="Finance workspace" items={menuItems} />

                    <View style={styles.sectionHead}>
                        <Text style={[styles.sectionTitle, { color: textMain, marginBottom: 0 }]}>Recent payments</Text>
                        <TouchableOpacity onPress={() => navigation.navigate('PaymentsList')}>
                            <Text style={[styles.viewAll, { color: colors.primary }]}>View all</Text>
                        </TouchableOpacity>
                    </View>

                    {recentPayments.map((p) => (
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
                    ))}

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
