import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import { Payroll } from 'types/hr.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params?: { staffId?: number; title?: string } };
}

export const PayrollRecordsScreen: React.FC<Props> = ({ navigation, route }) => {
    const staffId = route.params?.staffId;
    const titleSuffix = route.params?.title;
    const { isDark, colors } = useTheme();
    const [rows, setRows] = useState<Payroll[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchRows = useCallback(async () => {
        try {
            setLoading(true);
            const response = await hrApi.getPayrolls({
                staff_id: staffId,
                per_page: 40,
                page: 1,
            });
            if (response.success && response.data) {
                const data = response.data as { data?: Payroll[] };
                setRows(Array.isArray(data.data) ? data.data : []);
            } else {
                setRows([]);
            }
        } catch {
            setRows([]);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [staffId]);

    useEffect(() => {
        fetchRows();
    }, [fetchRows]);

    const onRefresh = () => {
        setRefreshing(true);
        fetchRows();
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'paid':
                return colors.success;
            case 'processed':
            case 'approved':
                return colors.primary;
            case 'draft':
                return colors.warning;
            default:
                return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const renderItem = ({ item }: { item: Payroll }) => {
        const monthLabel = item.month
            ? new Date(item.month + '-01').toLocaleString('en-US', { month: 'long', year: 'numeric' })
            : item.month;
        return (
            <Card>
                <View style={styles.cardRow}>
                    <View style={styles.cardMain}>
                        {!staffId && item.staff_name ? (
                            <Text
                                style={[styles.staffName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}
                                numberOfLines={1}
                            >
                                {item.staff_name}
                            </Text>
                        ) : null}
                        <Text style={[styles.month, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {monthLabel}
                        </Text>
                        <Text style={[styles.net, { color: colors.primary }]}>
                            {formatters.formatCurrency(item.net_salary)}
                        </Text>
                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Gross: {formatters.formatCurrency(item.gross_salary)} • Deductions:{' '}
                            {formatters.formatCurrency(item.deductions)}
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

    if (loading && !refreshing) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]} numberOfLines={1}>
                        {titleSuffix ? `Payroll · ${titleSuffix}` : 'Payroll records'}
                    </Text>
                </View>
                <LoadingState message="Loading payroll..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]} numberOfLines={1}>
                    {titleSuffix ? `Payroll · ${titleSuffix}` : 'Payroll records'}
                </Text>
            </View>
            {rows.length === 0 ? (
                <EmptyState icon="payments" title="No payroll records" message="Try again later or check the web portal." />
            ) : (
                <FlatList
                    data={rows}
                    renderItem={renderItem}
                    keyExtractor={(item) => `${item.id}`}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />
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
    title: { flex: 1, fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    list: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    cardRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
    cardMain: { flex: 1 },
    staffName: { fontSize: FONT_SIZES.sm, fontWeight: '600', marginBottom: 4 },
    month: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: 4 },
    net: { fontSize: FONT_SIZES.xl, fontWeight: 'bold', marginBottom: 4 },
    meta: { fontSize: FONT_SIZES.sm },
    statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8 },
    statusText: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
