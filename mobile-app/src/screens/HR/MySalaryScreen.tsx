import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import { Payroll } from '@types/hr.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface MySalaryScreenProps {
    navigation: any;
}

export const MySalaryScreen: React.FC<MySalaryScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [payrolls, setPayrolls] = useState<Payroll[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const staffId = (user as any)?.staff_id ?? user?.teacher_id;

    const fetchPayrolls = useCallback(async () => {
        if (!staffId) {
            setLoading(false);
            setRefreshing(false);
            return;
        }
        try {
            setLoading(true);
            const response = await hrApi.getPayrolls({ staff_id: staffId, per_page: 24 });
            if (response.success && response.data) {
                const data = response.data as any;
                setPayrolls(data?.data ?? data ?? []);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load salary records');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [staffId]);

    useEffect(() => {
        fetchPayrolls();
    }, [staffId]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchPayrolls();
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'paid':
                return colors.success;
            case 'processed':
                return colors.primary;
            case 'draft':
                return colors.warning || '#f59e0b';
            default:
                return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const renderPayrollCard = ({ item }: { item: Payroll }) => {
        const monthLabel = item.month ? new Date(item.month + '-01').toLocaleString('en-US', { month: 'long', year: 'numeric' }) : item.month;
        return (
            <Card>
                <View style={styles.cardRow}>
                    <View style={styles.cardMain}>
                        <Text style={[styles.month, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {monthLabel}
                        </Text>
                        <Text style={[styles.net, { color: colors.primary }]}>
                            {formatters.formatCurrency(item.net_salary)}
                        </Text>
                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Gross: {formatters.formatCurrency(item.gross_salary)} • Deductions: {formatters.formatCurrency(item.deductions)}
                        </Text>
                    </View>
                    <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                        <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                            {formatters.capitalize(item.status)}
                        </Text>
                    </View>
                </View>
            </Card>
        );
    };

    if (!staffId) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        My Salary
                    </Text>
                </View>
                <EmptyState
                    icon="payments"
                    title="No salary data"
                    message="Salary records are linked to your staff profile. Contact admin if you don't see your records."
                />
            </SafeAreaView>
        );
    }

    if (loading && !refreshing) {
        return <LoadingState message="Loading salary records..." />;
    }

    const list = Array.isArray(payrolls) ? payrolls : [];

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    My Salary
                </Text>
            </View>
            {list.length === 0 ? (
                <EmptyState
                    icon="payments"
                    title="No salary records"
                    message="Your payroll history will appear here once processed."
                />
            ) : (
                <FlatList
                    data={list}
                    renderItem={renderPayrollCard}
                    keyExtractor={(item) => `${item.id}`}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                    }
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    backBtn: { marginRight: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    list: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    cardRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
    cardMain: { flex: 1 },
    month: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: 4 },
    net: { fontSize: FONT_SIZES.xl, fontWeight: 'bold', marginBottom: 4 },
    meta: { fontSize: FONT_SIZES.sm },
    statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8 },
    statusText: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
