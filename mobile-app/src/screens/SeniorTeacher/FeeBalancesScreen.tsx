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
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { seniorTeacherApi, FeeBalanceItem } from '@api/seniorTeacher.api';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface FeeBalancesScreenProps {
    navigation: any;
}

export const FeeBalancesScreen: React.FC<FeeBalancesScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [items, setItems] = useState<FeeBalanceItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const load = useCallback(async () => {
        try {
            setLoading(true);
            const response = await seniorTeacherApi.getFeeBalances();
            if (response.success && response.data) {
                const data = response.data as any;
                setItems(Array.isArray(data) ? data : data?.data ?? []);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load fee balances');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        load();
    };

    if (loading && !refreshing) {
        return <LoadingState message="Loading fee balances..." />;
    }

    const list = Array.isArray(items) ? items : [];

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Fee Balances
                </Text>
            </View>
            {list.length === 0 ? (
                <EmptyState
                    icon="account-balance-wallet"
                    title="No fee balance data"
                    message="Student fee balances under your supervision will appear here."
                />
            ) : (
                <FlatList
                    data={list}
                    keyExtractor={(item) => String(item.student_id)}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                    }
                    renderItem={({ item }) => (
                        <Card>
                            <View style={styles.row}>
                                <View style={styles.info}>
                                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {item.student_name}
                                    </Text>
                                    {(item.admission_number || item.class_name) && (
                                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {[item.admission_number, item.class_name].filter(Boolean).join(' • ')}
                                        </Text>
                                    )}
                                </View>
                                <Text style={[styles.balance, { color: item.balance > 0 ? colors.error : colors.success }]}>
                                    {formatters.formatCurrency(item.balance)}
                                </Text>
                            </View>
                        </Card>
                    )}
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
    row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    info: { flex: 1 },
    name: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: 4 },
    meta: { fontSize: FONT_SIZES.sm, marginBottom: 2 },
    balance: { fontSize: FONT_SIZES.lg, fontWeight: '600' },
});
