import React, { useState, useEffect } from 'react';
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
import { StatusBadge } from '@components/common/StatusBadge';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { financeApi } from '@api/finance.api';
import { FeeStructure } from '../types/finance-enhanced.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface FeeStructuresScreenProps {
    navigation: any;
}

export const FeeStructuresScreen: React.FC<FeeStructuresScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [feeStructures, setFeeStructures] = useState<FeeStructure[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    useEffect(() => {
        loadFeeStructures();
    }, []);

    const loadFeeStructures = async () => {
        try {
            setLoading(true);
            // API call would go here
            // const response = await financeApi.getFeeStructures();
            // setFeeStructures(response.data);

            // Mock data for now
            setFeeStructures([
                {
                    id: 1,
                    name: 'Form 1 - Term 1 2024',
                    class_name: 'Form 1',
                    academic_year_id: 1,
                    term_id: 1,
                    items: [],
                    total_amount: 45000,
                    is_active: true,
                    created_at: '2024-01-01',
                    updated_at: '2024-01-01',
                },
            ]);
        } catch (error: any) {
            Alert.alert('Error', 'Failed to load fee structures');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        loadFeeStructures();
    };

    const renderFeeStructure = ({ item }: { item: FeeStructure }) => (
        <Card onPress={() => navigation.navigate('FeeStructureDetail', { id: item.id })}>
            <View style={styles.structureCard}>
                <View style={styles.structureInfo}>
                    <Text style={[styles.structureName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {item.name}
                    </Text>
                    <Text style={[styles.className, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.class_name}
                    </Text>
                    <Text style={[styles.amount, { color: colors.primary }]}>
                        {formatters.formatCurrency(item.total_amount)}
                    </Text>
                </View>
                <View style={styles.statusContainer}>
                    <StatusBadge status={item.is_active ? 'active' : 'inactive'} />
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <LoadingState message="Loading fee structures..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Fee Structures
                </Text>
                <TouchableOpacity onPress={() => navigation.navigate('CreateFeeStructure')}>
                    <Icon name="add" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            <FlatList
                data={feeStructures}
                renderItem={renderFeeStructure}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} tintColor={colors.primary} />
                }
                ListEmptyComponent={<EmptyState icon="account-balance" title="No Fee Structures" message="No fee structures have been created" />}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    listContent: { paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xl },
    structureCard: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    structureInfo: { flex: 1, gap: 4 },
    structureName: { fontSize: FONT_SIZES.md, fontWeight: 'bold' },
    className: { fontSize: FONT_SIZES.sm },
    amount: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginTop: 4 },
    statusContainer: { alignItems: 'flex-end', gap: SPACING.xs },
});
