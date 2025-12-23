import React, { useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface FinanceDashboardProps {
    navigation: any;
}

export const FinanceDashboard: React.FC<FinanceDashboardProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [refreshing, setRefreshing] = useState(false);

    const [stats] = useState({
        todayCollection: 125000,
        weekCollection: 580000,
        monthCollection: 2340000,
        pendingInvoices: 45,
        overduePayments: 12,
        totalDefaulters: 23,
    });

    const [recentPayments] = useState([
        { id: 1, student: 'John Doe', amount: 15000, method: 'M-Pesa', time: '10:30 AM' },
        { id: 2, student: 'Jane Smith', amount: 25000, method: 'Bank', time: '09:15 AM' },
    ]);

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 1000);
    };

    const quickActions = [
        { id: 1, title: 'Record Payment', icon: 'payment', screen: 'RecordPayment', color: '#3b82f6' },
        { id: 2, title: 'Generate Invoice', icon: 'receipt', screen: 'CreateInvoice', color: '#10b981' },
        { id: 3, title: 'Defaulters', icon: 'warning', screen: 'Defaulters', color: '#f59e0b' },
        { id: 4, title: 'Fee Structures', icon: 'account-balance', screen: 'FeeStructures', color: '#8b5cf6' },
        { id: 5, title: 'Reports', icon: 'assessment', screen: 'FinanceReports', color: '#ec4899' },
        { id: 6, title: 'Receipts', icon: 'print', screen: 'Receipts', color: '#14b8a6' },
    ];

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
            onPress={() => navigation.navigate(action.screen)}
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
                        Finance Dashboard
                    </Text>
                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {user?.name || 'Accountant'}
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
                        {renderStatCard('Today', stats.todayCollection, 'today', '#3b82f6', true)}
                        {renderStatCard('This Week', stats.weekCollection, 'date-range', '#10b981', true)}
                    </View>
                    <View style={styles.statsRow}>
                        {renderStatCard('This Month', stats.monthCollection, 'calendar-month', '#8b5cf6', true)}
                    </View>
                </View>

                {/* Pending Items */}
                <View style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Pending Items
                    </Text>
                    <View style={styles.statsRow}>
                        {renderStatCard('Pending Invoices', stats.pendingInvoices, 'receipt-long', '#f59e0b', false)}
                        {renderStatCard('Overdue', stats.overduePayments, 'warning', '#ef4444', false)}
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
                        <TouchableOpacity onPress={() => navigation.navigate('Payments')}>
                            <Text style={[styles.viewAll, { color: colors.primary }]}>View All</Text>
                        </TouchableOpacity>
                    </View>

                    {recentPayments.map((payment) => (
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
                    ))}
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
