import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { financeApi } from '@api/finance.api';
import { FinanceTransaction } from 'types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette, withAlpha } from '@styles/palette';

const VIEW_FILTERS: { key: string; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'unassigned', label: 'Unassigned' },
    { key: 'auto-assigned', label: 'Auto' },
    { key: 'manual-assigned', label: 'Manual' },
    { key: 'draft', label: 'Draft' },
    { key: 'collected', label: 'Collected' },
    { key: 'swimming', label: 'Swimming' },
    { key: 'duplicate', label: 'Duplicates' },
    { key: 'archived', label: 'Archived' },
];

interface Props {
    navigation: { navigate: (name: string, params?: object) => void };
    embedded?: boolean;
}

export const TransactionsListScreen: React.FC<Props> = ({ navigation, embedded = false }) => {
    const { isDark, colors } = useTheme();
    const [rows, setRows] = useState<FinanceTransaction[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [view, setView] = useState('all');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const fetchRows = useCallback(
        async (pageNum: number = 1, search?: string, viewOverride?: string) => {
            try {
                if (pageNum === 1) setLoading(true);
                const v = viewOverride ?? view;
                const response = await financeApi.getFinanceTransactions({
                    view: v === 'all' ? undefined : v,
                    search: search ?? searchQuery,
                    page: pageNum,
                    per_page: 20,
                    date_from: dateFrom.trim() || undefined,
                    date_to: dateTo.trim() || undefined,
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
                }
            } catch (error: any) {
                Alert.alert('Transactions', error.message || 'Failed to load');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [searchQuery, view, dateFrom, dateTo]
    );

    useEffect(() => {
        const delay = searchQuery.length === 0 ? 0 : 400;
        const t = setTimeout(() => {
            fetchRows(1, searchQuery);
        }, delay);
        return () => clearTimeout(t);
    }, [searchQuery, view, dateFrom, dateTo]);

    const onRefresh = () => {
        setRefreshing(true);
        fetchRows(1);
    };

    const onEndReached = () => {
        if (!loading && hasMore) {
            fetchRows(page + 1);
        }
    };

    const renderItem = ({ item }: { item: FinanceTransaction }) => (
        <Card
            onPress={() =>
                navigation.navigate('TransactionDetail', {
                    transactionId: item.id,
                    transactionType: item.transaction_type,
                })
            }
        >
            <View style={styles.row}>
                <View style={styles.info}>
                    <View style={styles.topRow}>
                        <Text style={[styles.code, { color: textMain }]} numberOfLines={1}>
                            {item.trans_code || `#${item.id}`}
                        </Text>
                        <View
                            style={[
                                styles.badge,
                                {
                                    backgroundColor:
                                        item.transaction_type === 'bank' ? withAlpha(Palette.bank, '22') : `${colors.primary}22`,
                                },
                            ]}
                        >
                            <Text
                                style={[
                                    styles.badgeText,
                                    { color: item.transaction_type === 'bank' ? Palette.bank : colors.primary },
                                ]}
                            >
                                {item.transaction_type === 'bank' ? 'Bank' : 'M-Pesa'}
                            </Text>
                        </View>
                    </View>
                    <Text style={[styles.student, { color: textSub }]} numberOfLines={1}>
                        {item.student_name || item.payer_name || item.description || '—'}
                    </Text>
                    <Text style={[styles.meta, { color: textSub }]} numberOfLines={2}>
                        {item.trans_date ? formatters.formatDate(String(item.trans_date)) : '—'}
                        {item.recorded_at
                            ? ` · ${new Date(item.recorded_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
                            : ''}
                        {' · '}
                        {item.match_status || item.status || '—'}
                        {item.bill_ref_number ? ` · Ref ${item.bill_ref_number}` : ''}
                        {item.is_swimming_transaction ? ' · Swimming' : ''}
                    </Text>
                    {item.description ? (
                        <Text style={[styles.desc, { color: textSub }]} numberOfLines={2}>
                            {item.description}
                        </Text>
                    ) : null}
                </View>
                <Text style={[styles.amount, { color: colors.success }]}>
                    {item.trans_amount != null ? formatters.formatCurrency(item.trans_amount) : '—'}
                </Text>
            </View>
        </Card>
    );

    if (loading && page === 1) {
        const loader = <LoadingState message="Loading transactions..." />;
        if (embedded) {
            return <View style={[styles.container, styles.embedded, { backgroundColor: bg }]}>{loader}</View>;
        }
        return <View style={[styles.container, { backgroundColor: bg }]}>{loader}</View>;
    }

    return (
        <View style={[styles.container, embedded && styles.embedded, { backgroundColor: bg }]}>
            <View style={styles.filterRow}>
                {VIEW_FILTERS.map((f) => {
                    const active = view === f.key;
                    return (
                        <TouchableOpacity
                            key={f.key}
                            style={[
                                styles.chip,
                                {
                                    backgroundColor: active ? colors.primary + '22' : isDark ? colors.surfaceDark : BRAND.surface,
                                    borderColor: active ? colors.primary : isDark ? colors.borderDark : BRAND.border,
                                },
                            ]}
                            onPress={() => setView(f.key)}
                        >
                            <Text
                                style={{
                                    fontSize: FONT_SIZES.xs,
                                    fontWeight: '600',
                                    color: active ? colors.primary : textSub,
                                }}
                            >
                                {f.label}
                            </Text>
                        </TouchableOpacity>
                    );
                })}
            </View>
            <View style={styles.searchWrap}>
                <Input
                    placeholder="Search ref, phone, description…"
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
                <View style={styles.dateRow}>
                    <Input
                        placeholder="From (YYYY-MM-DD)"
                        value={dateFrom}
                        onChangeText={setDateFrom}
                        containerStyle={styles.dateInput}
                    />
                    <Input
                        placeholder="To (YYYY-MM-DD)"
                        value={dateTo}
                        onChangeText={setDateTo}
                        containerStyle={styles.dateInput}
                    />
                </View>
            </View>
            <FlatList
                style={embedded ? { flex: 1 } : undefined}
                data={rows}
                renderItem={renderItem}
                keyExtractor={(item) => `${item.transaction_type}-${item.id}`}
                contentContainerStyle={[styles.list, embedded && { flexGrow: 1 }]}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />
                }
                onEndReached={onEndReached}
                onEndReachedThreshold={0.5}
                ListEmptyComponent={
                    <EmptyState
                        icon="account-balance"
                        title="No transactions"
                        message="Try another filter or search"
                    />
                }
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    embedded: { minHeight: 400 },
    filterRow: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.xs,
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.sm,
    },
    chip: {
        paddingHorizontal: SPACING.sm,
        paddingVertical: 6,
        borderRadius: RADIUS.button,
        borderWidth: 1,
    },
    searchWrap: { paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm },
    searchInput: { marginBottom: 0 },
    dateRow: { flexDirection: 'row', gap: SPACING.sm, marginTop: SPACING.sm },
    dateInput: { flex: 1, marginBottom: 0 },
    desc: { fontSize: FONT_SIZES.xs, marginTop: 4 },
    list: { paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xl, gap: SPACING.sm },
    row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
    info: { flex: 1, paddingRight: SPACING.sm },
    topRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm },
    code: { fontSize: FONT_SIZES.sm, fontWeight: '600', flex: 1 },
    badge: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6 },
    badgeText: { fontSize: 10, fontWeight: '700' },
    student: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    meta: { fontSize: FONT_SIZES.xs, marginTop: 4 },
    amount: { fontSize: FONT_SIZES.md, fontWeight: '700' },
});
