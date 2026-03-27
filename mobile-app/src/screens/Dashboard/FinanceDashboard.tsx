import React, { useCallback, useEffect, useState } from 'react';
import {
    Alert,
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { tileColorForIndex, FINANCE_STAT_COLORS } from '@styles/sections/dashboard';
import { financeApi } from '@api/finance.api';
import { Payment } from '@types/finance.types';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface FinanceDashboardProps {
    navigation: any;
}

export const FinanceDashboard: React.FC<FinanceDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [refreshing, setRefreshing] = useState(false);
    const [loading, setLoading] = useState(true);

    const [stats, setStats] = useState({
        todayCollection: 0,
        weekCollection: 0,
        monthCollection: 0,
        pendingInvoices: 0,
        overduePayments: 0,
        totalDefaulters: 0,
    });

    const [recentPayments, setRecentPayments] = useState<
        { id: number; student: string; amount: number; method: string; time: string }[]
    >([]);

    const loadDashboardData = useCallback(
        async (isRefresh: boolean = false) => {
            try {
                if (!isRefresh) {
                    setLoading(true);
                }

                const [summaryRes, pendingRes, overdueRes, defaultersRes, recentPaymentsRes] = await Promise.all([
                    financeApi.getFinanceSummary(),
                    financeApi.getInvoices({ status: 'issued', page: 1, per_page: 1 }),
                    financeApi.getInvoices({ status: 'overdue', page: 1, per_page: 1 }),
                    financeApi.getInvoices({ status: 'overdue', page: 1, per_page: 100 }),
                    financeApi.getPayments({ page: 1, per_page: 5, active_only: true }),
                ]);

                const summary = summaryRes.data;
                const overdueInvoices = defaultersRes.data?.data ?? [];
                const uniqueDefaulters = new Set(
                    overdueInvoices.map((invoice) => invoice.student_id).filter((id): id is number => Boolean(id))
                );

                setStats({
                    todayCollection: Number(summary?.payments_today ?? 0),
                    weekCollection: Number(summary?.payments_this_week ?? 0),
                    monthCollection: Number(summary?.payments_this_month ?? 0),
                    pendingInvoices: Number(pendingRes.data?.total ?? 0),
                    overduePayments: Number(overdueRes.data?.total ?? 0),
                    totalDefaulters: uniqueDefaulters.size,
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

    useEffect(() => {
        loadDashboardData();
    }, [loadDashboardData]);

    const handleRefresh = () => {
        setRefreshing(true);
        loadDashboardData(true);
    };

    const quickActions = [
        { id: 1, title: 'Record Payment', icon: 'payment', screen: 'RecordPayment' },
        { id: 2, title: 'Active invoices', icon: 'receipt-long', screen: 'InvoicesList' },
        { id: 3, title: 'Payments', icon: 'payments', screen: 'PaymentsList' },
        { id: 4, title: 'Defaulters', icon: 'warning', screen: 'Defaulters' },
        { id: 5, title: 'Fee Structures', icon: 'account-balance', screen: 'FeeStructures' },
        { id: 6, title: 'Reports', icon: 'assessment', screen: 'FinanceReports' },
    ].map((a, i) => ({ ...a, color: tileColorForIndex(i) }));

    const renderStatCard = (title: string, value: any, icon: string, color: string, isAmount = false) => (
        <Card style={styles.statCard}>
            <View style={[styles.statIconContainer, { backgroundColor: color + '20' }]}>
                <Icon name={icon} size={24} color={color} />
            </View>
            <Text style={[styles.statValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {isAmount ? formatters.formatCurrency(value) : value}
            </Text>
            <Text style={[styles.statLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                {title}
            </Text>
        </Card>
    );

    const renderQuickAction = (action: any) => (
        <TouchableOpacity
            key={action.id}
            style={[styles.actionCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}
            onPress={() =>
                navigation.navigate('Finance', {
                    screen: action.screen,
                })
            }
        >
            <View style={[styles.actionIcon, { backgroundColor: action.color + '20' }]}>
                <Icon name={action.icon} size={24} color={action.color} />
            </View>
            <Text style={[styles.actionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                {action.title}
            </Text>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <View>
                    <Text style={[styles.greeting, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        You are in
                    </Text>
                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Finance
                    </Text>
                </View>
                <TouchableOpacity onPress={() => navigation.navigate('Notifications')}>
                    <Icon name="notifications" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
            </View>

            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
            >
                {/* Collection Stats */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Collections
                    </Text>
                    <View style={styles.statsRow}>
                        {renderStatCard('Today', stats.todayCollection, 'today', FINANCE_STAT_COLORS.today, true)}
                        {renderStatCard('This Week', stats.weekCollection, 'date-range', FINANCE_STAT_COLORS.week, true)}
                    </View>
                    <View style={styles.statsRow}>
                        {renderStatCard('This Month', stats.monthCollection, 'calendar-month', FINANCE_STAT_COLORS.month, true)}
                    </View>
                </View>

                {/* Pending Items */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Pending Items
                    </Text>
                    <View style={styles.statsRow}>
                        {renderStatCard('Pending Invoices', stats.pendingInvoices, 'receipt-long', FINANCE_STAT_COLORS.pending, false)}
                        {renderStatCard('Overdue', stats.overduePayments, 'warning', FINANCE_STAT_COLORS.overdue, false)}
                    </View>
                </View>

                {/* Quick Actions */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Quick Actions
                    </Text>
                    <View style={styles.actionsGrid}>
                        {quickActions.map(renderQuickAction)}
                    </View>
                </View>

                {/* Recent Payments */}
                <View style={styles.section}>
                    <View style={styles.sectionHeader}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Recent Payments
                        </Text>
                        <TouchableOpacity
                            onPress={() => navigation.navigate('Finance', { screen: 'PaymentsList' })}
                        >
                            <Text style={[styles.viewAll, { color: colors.primary }]}>View All</Text>
                        </TouchableOpacity>
                    </View>

                    {loading ? (
                        <Card style={styles.paymentCard}>
                            <Text style={[styles.paymentMethod, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Loading recent payments...
                            </Text>
                        </Card>
                    ) : recentPayments.length === 0 ? (
                        <Card style={styles.paymentCard}>
                            <Text style={[styles.paymentMethod, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                No recent payments available.
                            </Text>
                        </Card>
                    ) : (
                        recentPayments.map((payment) => (
                            <Card key={payment.id} style={styles.paymentCard}>
                                <View style={styles.paymentRow}>
                                    <View style={[styles.paymentIcon, { backgroundColor: colors.success + '20' }]}>
                                        <Icon name="check-circle" size={20} color={colors.success} />
                                    </View>
                                    <View style={styles.paymentInfo}>
                                        <Text style={[styles.paymentStudent, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                            {payment.student}
                                        </Text>
                                        <Text style={[styles.paymentMethod, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {payment.method} • {payment.time}
                                        </Text>
                                    </View>
                                    <Text style={[styles.paymentAmount, { color: colors.success }]}>
                                        {formatters.formatCurrency(payment.amount)}
                                    </Text>
                                </View>
                            </Card>
                        ))
                    )}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    greeting: {
        fontSize: FONT_SIZES.sm,
    },
    name: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    content: {
        padding: SPACING.xl,
    },
    section: {
        marginBottom: SPACING.xl,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.md,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
    },
    viewAll: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    statsRow: {
        flexDirection: 'row',
        gap: SPACING.md,
        marginBottom: SPACING.sm,
    },
    statCard: {
        flex: 1,
        padding: SPACING.md,
        alignItems: 'center',
        gap: SPACING.xs,
    },
    statIconContainer: {
        width: 48,
        height: 48,
        borderRadius: 24,
        alignItems: 'center',
        justifyContent: 'center',
    },
    statValue: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
    },
    statLabel: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
    },
    actionsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
    },
    actionCard: {
        flex: 1,
        minWidth: '30%',
        padding: SPACING.md,
        borderRadius: 12,
        alignItems: 'center',
        gap: SPACING.xs,
    },
    actionIcon: {
        width: 48,
        height: 48,
        borderRadius: 24,
        alignItems: 'center',
        justifyContent: 'center',
    },
    actionTitle: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
        fontWeight: '600',
    },
    paymentCard: {
        marginBottom: SPACING.sm,
    },
    paymentRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    paymentIcon: {
        width: 40,
        height: 40,
        borderRadius: 20,
        alignItems: 'center',
        justifyContent: 'center',
    },
    paymentInfo: {
        flex: 1,
    },
    paymentStudent: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    paymentMethod: {
        fontSize: FONT_SIZES.xs,
        marginTop: 2,
    },
    paymentAmount: {
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
    },
});
