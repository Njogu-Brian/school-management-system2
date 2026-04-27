import React, { useCallback, useEffect, useState } from 'react';
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
import { StatusBadge } from '@components/common/StatusBadge';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import { financeApi } from '@api/finance.api';
import { FeeStructure } from 'types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
}

export const FeeStructuresListScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [rows, setRows] = useState<FeeStructure[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [listError, setListError] = useState<string | null>(null);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const fetchRows = useCallback(
        async (pageNum: number = 1, search?: string) => {
            setListError(null);
            try {
                if (pageNum === 1) setLoading(true);
                const response = await financeApi.getFeeStructures({
                    search: search ?? searchQuery,
                    page: pageNum,
                    per_page: 20,
                });
                if (response.success && response.data) {
                    const pageData = response.data;
                    if (pageNum === 1) {
                        setRows(pageData.data);
                    } else {
                        setRows((prev) => [...prev, ...pageData.data]);
                    }
                    setHasMore(pageData.current_page < pageData.last_page);
                    setPage(pageNum);
                } else {
                    if (pageNum === 1) setRows([]);
                    setListError(response.message || 'Could not load fee structures.');
                }
            } catch (e: any) {
                if (pageNum === 1) setRows([]);
                setListError(e?.message || 'Could not load fee structures.');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [searchQuery]
    );

    useEffect(() => {
        const delay = searchQuery.length === 0 ? 0 : 400;
        const t = setTimeout(() => {
            fetchRows(1, searchQuery);
        }, delay);
        return () => clearTimeout(t);
    }, [searchQuery]);

    const onRefresh = () => {
        setRefreshing(true);
        fetchRows(1);
    };

    const loadMore = () => {
        if (!loading && hasMore) fetchRows(page + 1);
    };

    const renderItem = ({ item }: { item: FeeStructure }) => (
        <Card>
            <View style={styles.cardInner}>
                <View style={{ flex: 1 }}>
                    <Text style={[styles.name, { color: textMain }]}>{item.name}</Text>
                    <Text style={[styles.meta, { color: textSub }]}>
                        {[item.class_name, item.status === 'active' ? 'Active' : 'Inactive'].filter(Boolean).join(' · ')}
                    </Text>
                    <Text style={[styles.amount, { color: colors.primary }]}>
                        Total {formatters.formatCurrency(item.total_amount)}
                    </Text>
                </View>
                <StatusBadge status={item.status} variant={item.status === 'active' ? 'success' : 'default'} />
            </View>
        </Card>
    );

    if (loading && page === 1) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
                <LoadingState message="Loading fee structures…" />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()} hitSlop={12}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: textMain }]}>Fee structures</Text>
                <View style={{ width: 40 }} />
            </View>
            <Text style={[styles.hint, { color: textSub }]}>
                View-only — create or edit structures in the web portal.
            </Text>
            {listError ? (
                <View style={{ paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm }}>
                    <LoadErrorBanner
                        message={listError}
                        onRetry={() => {
                            setRefreshing(true);
                            fetchRows(1);
                        }}
                        surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                        borderColor={isDark ? colors.borderDark : BRAND.border}
                        textColor={textMain}
                        subColor={textSub}
                        accentColor={colors.primary}
                    />
                </View>
            ) : null}
            <View style={{ paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm }}>
                <Input
                    placeholder="Search by name…"
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={{ marginBottom: 0 }}
                />
            </View>
            <FlatList
                data={rows}
                keyExtractor={(item) => String(item.id)}
                renderItem={renderItem}
                contentContainerStyle={{ paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xxl, gap: SPACING.sm }}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
                onEndReached={loadMore}
                onEndReachedThreshold={0.5}
                ListEmptyComponent={
                    <EmptyState icon="account-balance" title="No fee structures" message="Try another search or check the portal." />
                }
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    backBtn: { padding: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '700', flex: 1, textAlign: 'center' },
    hint: { fontSize: FONT_SIZES.xs, paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm },
    cardInner: { flexDirection: 'row', alignItems: 'flex-start', gap: SPACING.md },
    name: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    amount: { fontSize: FONT_SIZES.sm, fontWeight: '600', marginTop: 6 },
});
