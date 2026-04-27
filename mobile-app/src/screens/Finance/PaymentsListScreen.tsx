import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, FlatList, SafeAreaView, TouchableOpacity, RefreshControl } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { financeApi } from '@api/finance.api';
import { Payment } from 'types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';

interface Props {
    navigation: { goBack?: () => void; navigate: (name: string, params?: object) => void };
    route?: { params?: { title?: string } };
    /** When true, used inside Payments hub (no back button / outer title). */
    embedded?: boolean;
}

export const PaymentsListScreen: React.FC<Props> = ({ navigation, route, embedded = false }) => {
    const { isDark, colors } = useTheme();
    const listTitle = route?.params?.title ?? 'Payments';
    const [payments, setPayments] = useState<Payment[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [listError, setListError] = useState<string | null>(null);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const fetchPayments = useCallback(
        async (pageNum: number = 1, search?: string) => {
            setListError(null);
            try {
                if (pageNum === 1) setLoading(true);
                const response = await financeApi.getPayments({
                    search: search ?? searchQuery,
                    page: pageNum,
                    per_page: 20,
                    active_only: true,
                });
                if (response.success && response.data) {
                    const pageData = response.data;
                    if (pageNum === 1) {
                        setPayments(pageData.data);
                    } else {
                        setPayments((prev) => [...prev, ...pageData.data]);
                    }
                    setHasMore(pageData.current_page < pageData.last_page);
                    setPage(pageNum);
                } else {
                    if (pageNum === 1) setPayments([]);
                    setListError(response.message || 'Failed to load payments');
                }
            } catch (error: any) {
                if (pageNum === 1) setPayments([]);
                setListError(error?.message || 'Failed to load payments');
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
            fetchPayments(1, searchQuery);
        }, delay);
        return () => clearTimeout(t);
    }, [searchQuery]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchPayments(1);
    };

    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchPayments(page + 1);
        }
    };

    const renderItem = ({ item }: { item: Payment }) => (
        <Card onPress={() => navigation.navigate('PaymentDetail', { paymentId: item.id })}>
            <View style={styles.row}>
                <View style={styles.info}>
                    <Text style={[styles.receipt, { color: textMain }]}>{item.receipt_number}</Text>
                    <Text style={[styles.student, { color: textSub }]}>
                        {item.student_name || '—'}
                        {item.student_admission_number ? ` · ${item.student_admission_number}` : ''}
                    </Text>
                    <Text style={[styles.meta, { color: textSub }]}>
                        {formatters.formatDate(item.payment_date)} · {item.payment_method}
                    </Text>
                </View>
                <Text style={[styles.amount, { color: colors.success }]}>{formatters.formatCurrency(item.amount)}</Text>
            </View>
        </Card>
    );

    const inner = (
        <>
            {!embedded && (
                <View style={styles.header}>
                    <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack?.()} hitSlop={12}>
                        <Icon name="arrow-back" size={24} color={colors.primary} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: textMain }]}>{listTitle}</Text>
                    <View style={{ width: 40 }} />
                </View>
            )}
            {listError ? (
                <View style={{ paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm }}>
                    <LoadErrorBanner
                        message={listError}
                        onRetry={() => {
                            setRefreshing(true);
                            fetchPayments(1);
                        }}
                        surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                        borderColor={isDark ? colors.borderDark : BRAND.border}
                        textColor={textMain}
                        subColor={textSub}
                        accentColor={colors.primary}
                    />
                </View>
            ) : null}
            <View style={styles.searchWrap}>
                <Input
                    placeholder="Search receipt, student, admission…"
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
            </View>
            <FlatList
                style={embedded ? { flex: 1 } : undefined}
                data={payments}
                renderItem={renderItem}
                keyExtractor={(item) => String(item.id)}
                contentContainerStyle={[styles.list, embedded && { flexGrow: 1 }]}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                }
                onEndReached={handleLoadMore}
                onEndReachedThreshold={0.5}
                ListEmptyComponent={
                    <EmptyState icon="payment" title="No payments" message="Try adjusting your search" />
                }
            />
        </>
    );

    if (loading && page === 1) {
        const loader = <LoadingState message="Loading payments..." />;
        if (embedded) {
            return (
                <View style={[styles.container, styles.embedded, { backgroundColor: bg }]}>{loader}</View>
            );
        }
        return <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>{loader}</SafeAreaView>;
    }

    if (embedded) {
        return (
            <View style={[styles.container, styles.embedded, { backgroundColor: bg }]}>{inner}</View>
        );
    }

    return <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>{inner}</SafeAreaView>;
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    embedded: { minHeight: 400 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    backBtn: { padding: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '700' },
    searchWrap: { paddingHorizontal: SPACING.xl, marginBottom: SPACING.sm },
    searchInput: { marginBottom: 0 },
    list: { paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xl, gap: SPACING.sm },
    row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
    info: { flex: 1, paddingRight: SPACING.sm },
    receipt: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    student: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    meta: { fontSize: FONT_SIZES.xs, marginTop: 4 },
    amount: { fontSize: FONT_SIZES.md, fontWeight: '700' },
});
