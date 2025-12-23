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
import { StatusBadge } from '@components/common/StatusBadge';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { financeApi } from '@api/finance.api';
import { Invoice, FinanceFilters } from '../types/finance.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface InvoicesListScreenProps {
    navigation: any;
}

export const InvoicesListScreen: React.FC<InvoicesListScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    // Fetch invoices
    const fetchInvoices = useCallback(
        async (pageNum: number = 1, search?: string) => {
            try {
                if (pageNum === 1) {
                    setLoading(true);
                }

                const response = await financeApi.getInvoices({
                    search: search || searchQuery,
                    page: pageNum,
                    per_page: 20,
                });

                if (response.success && response.data) {
                    if (pageNum === 1) {
                        setInvoices(response.data.data);
                    } else {
                        setInvoices((prev) => [...prev, ...response.data.data]);
                    }

                    setHasMore(response.data.current_page < response.data.last_page);
                    setPage(pageNum);
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load invoices');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [searchQuery]
    );

    useEffect(() => {
        fetchInvoices(1);
    }, []);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchQuery !== undefined) {
                fetchInvoices(1, searchQuery);
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchInvoices(1);
    };

    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchInvoices(page + 1);
        }
    };

    const handleInvoicePress = (invoice: Invoice) => {
        navigation.navigate('InvoiceDetail', { invoiceId: invoice.id });
    };

    const getStatusVariant = (status: string) => {
        switch (status) {
            case 'paid':
                return 'success';
            case 'partially_paid':
                return 'info';
            case 'overdue':
                return 'error';
            default:
                return 'default';
        }
    };

    const renderInvoiceCard = ({ item }: { item: Invoice }) => (
        <Card onPress={() => handleInvoicePress(item)}>
            <View style={styles.invoiceCard}>
                <View style={styles.invoiceHeader}>
                    <View style={styles.invoiceInfo}>
                        <Text style={[styles.invoiceNumber, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            #{item.invoice_number}
                        </Text>
                        <Text style={[styles.studentName, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.student_name}
                        </Text>
                        <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.student_admission_number}
                        </Text>
                    </View>
                    <StatusBadge status={item.status} variant={getStatusVariant(item.status)} />
                </View>

                <View style={styles.divider} />

                <View style={styles.amounts}>
                    <View style={styles.amountRow}>
                        <Text style={[styles.amountLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Total:
                        </Text>
                        <Text style={[styles.amountValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {formatters.formatCurrency(item.total_amount)}
                        </Text>
                    </View>
                    <View style={styles.amountRow}>
                        <Text style={[styles.amountLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Paid:
                        </Text>
                        <Text style={[styles.amountValue, { color: colors.success }]}>
                            {formatters.formatCurrency(item.paid_amount)}
                        </Text>
                    </View>
                    <View style={styles.amountRow}>
                        <Text style={[styles.amountLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Balance:
                        </Text>
                        <Text style={[styles.amountValue, { color: item.balance > 0 ? colors.error : colors.success }]}>
                            {formatters.formatCurrency(item.balance)}
                        </Text>
                    </View>
                </View>

                <View style={styles.invoiceFooter}>
                    <View style={styles.dateInfo}>
                        <Icon name="calendar-today" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.dateText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {formatters.formatDate(item.issue_date)}
                        </Text>
                    </View>
                    <TouchableOpacity style={styles.actionButton}>
                        <Icon name="chevron-right" size={20} color={colors.primary} />
                    </TouchableOpacity>
                </View>
            </View>
        </Card>
    );

    if (loading && page === 1) {
        return (
            <SafeAreaView
                style={[
                    styles.container,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <LoadingState message="Loading invoices..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
            ]}
        >
            {/* Header */}
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Invoices
                </Text>
                <TouchableOpacity
                    style={styles.addButton}
                    onPress={() => navigation.navigate('CreateInvoice')}
                >
                    <Icon name="add" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            {/* Search Bar */}
            <View style={styles.searchContainer}>
                <Input
                    placeholder="Search invoices..."
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
            </View>

            {/* Invoices List */}
            <FlatList
                data={invoices}
                renderItem={renderInvoiceCard}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
                onEndReached={handleLoadMore}
                onEndReachedThreshold={0.5}
                ListEmptyComponent={
                    <EmptyState
                        icon="receipt"
                        title="No Invoices Found"
                        message="No invoices match your search criteria"
                    />
                }
            />
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
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    addButton: {
        padding: SPACING.sm,
    },
    searchContainer: {
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.md,
    },
    searchInput: {
        marginBottom: 0,
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    invoiceCard: {
        gap: SPACING.sm,
    },
    invoiceHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
    },
    invoiceInfo: {
        flex: 1,
        gap: 4,
    },
    invoiceNumber: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
    },
    studentName: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    admissionNumber: {
        fontSize: FONT_SIZES.xs,
    },
    divider: {
        height: 1,
        backgroundColor: '#e2e8f0',
        marginVertical: SPACING.xs,
    },
    amounts: {
        gap: 4,
    },
    amountRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    amountLabel: {
        fontSize: FONT_SIZES.sm,
    },
    amountValue: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    invoiceFooter: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginTop: SPACING.xs,
    },
    dateInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    dateText: {
        fontSize: FONT_SIZES.xs,
    },
    actionButton: {
        padding: 4,
    },
});
