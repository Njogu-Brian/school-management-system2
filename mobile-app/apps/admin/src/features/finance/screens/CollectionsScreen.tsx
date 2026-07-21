import {
  paymentMethodLabel,
  useCan,
  useInfiniteFinanceTransactions,
  useInfinitePaymentList,
  type FinanceTransactionSummary,
  type PaymentSummary,
} from '@erp/core';
import {
  EmptyState,
  FinanceScreenHeader,
  FinanceSearchBar,
  FinanceTransactionListItem,
  ListEmptyState,
  PaymentListItem,
  RegistryListLayout,
  ScreenContainer,
  ScrollableTabBar,
  SkeletonListRows,
  TransactionViewFilters,
  useTheme,
  type FinanceTransactionViewFilter,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { useCollectionsRegistryState } from '../hooks/useCollectionsRegistryState';
import { useCollectionsTransactionState } from '../hooks/useCollectionsTransactionState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'CollectionsList'>;

type CollectionsTab = 'payments' | 'transactions';

const TABS = [
  { key: 'payments' as const, label: 'Payments' },
  { key: 'transactions' as const, label: 'Transactions' },
];

export const CollectionsScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('finance.view');
  const { palette, spacing } = useTheme();
  const initialTab = route.params?.initialTab ?? 'payments';
  const initialView = (route.params?.transactionView ?? 'all') as FinanceTransactionViewFilter;

  const [tab, setTab] = useState<CollectionsTab>(initialTab);
  const [txnFiltersOpen, setTxnFiltersOpen] = useState(false);

  const paymentsState = useCollectionsRegistryState();
  const txnState = useCollectionsTransactionState(initialView);

  const paymentsQuery = useInfinitePaymentList(paymentsState.filters, {
    enabled: canView && tab === 'payments',
  });
  const txnQuery = useInfiniteFinanceTransactions(txnState.filters, {
    enabled: canView && tab === 'transactions',
  });

  const payments = useMemo(
    () => paymentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [paymentsQuery.data],
  );

  const transactions = useMemo(() => {
    const pages = txnQuery.data?.pages ?? [];
    return pages.flatMap((p) =>
      p.raw.map((raw, idx) => ({
        summary: p.items[idx],
        type: raw.transaction_type as 'bank' | 'c2b',
      })),
    );
  }, [txnQuery.data]);

  const openPayment = useCallback(
    (summary: PaymentSummary) => {
      navigation.navigate('PaymentDetail', { paymentId: summary.id, summary });
    },
    [navigation],
  );

  const openTransaction = useCallback(
    (summary: FinanceTransactionSummary, type: 'bank' | 'c2b') => {
      navigation.navigate('TransactionDetail', {
        transactionId: summary.id,
        transactionType: type,
        summary,
      });
    },
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view collections."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  const listProps = {
    showFilterTrigger: tab === 'transactions',
    activeFilterCount: tab === 'transactions' && txnState.view !== 'all' ? 1 : 0,
    filtersOpen: txnFiltersOpen,
    onOpenFilters: () => setTxnFiltersOpen(true),
    onCloseFilters: () => setTxnFiltersOpen(false),
    onApplyFilters: () => setTxnFiltersOpen(false),
    onClearFilters: () => {
      txnState.setView('all');
      txnState.setSearchInput('');
      setTxnFiltersOpen(false);
    },
    filterContent: (
      <TransactionViewFilters view={txnState.view} onViewChange={txnState.setView} />
    ),
    hero: (
      <View style={{ gap: spacing.sm }}>
        <FinanceScreenHeader
          title="Collections"
          subtitle="Payments & bank/M-Pesa transactions"
          onBack={() => navigation.goBack()}
        />
        <ScrollableTabBar variant="segmented" tabs={TABS} activeTab={tab} onTabChange={setTab} />
      </View>
    ),
  };

  if (tab === 'payments') {
    return (
      <ScreenContainer scroll={false} style={{ flex: 1 }}>
        <RegistryListLayout
          {...listProps}
          data={payments}
          keyExtractor={(item) => String(item.id)}
          searchBar={
            <FinanceSearchBar
              value={paymentsState.searchInput}
              onChangeText={paymentsState.setSearchInput}
              placeholder="Search receipt or student…"
            />
          }
          renderItem={({ item }) => (
            <View style={{ marginBottom: spacing.sm }}>
              <PaymentListItem
                payment={item}
                onPress={() => openPayment(item)}
                formatAmount={formatKes}
                methodLabel={paymentMethodLabel}
              />
            </View>
          )}
          refreshControl={
            <RefreshControl
              refreshing={paymentsQuery.isRefetching && !paymentsQuery.isFetchingNextPage}
              onRefresh={() => void paymentsQuery.refetch()}
              colors={[palette.primary]}
              tintColor={palette.primary}
            />
          }
          onEndReached={() => {
            if (paymentsQuery.hasNextPage && !paymentsQuery.isFetchingNextPage) {
              void paymentsQuery.fetchNextPage();
            }
          }}
          onEndReachedThreshold={0.4}
          ListFooterComponent={
            paymentsQuery.isFetchingNextPage ? (
              <ActivityIndicator color={palette.primary} style={{ marginVertical: spacing.md }} />
            ) : null
          }
          ListEmptyComponent={
            paymentsQuery.isLoading ? (
              <SkeletonListRows variant="card" />
            ) : paymentsQuery.isError ? (
              <ListEmptyState
                title="Could not load payments"
                message={(paymentsQuery.error as Error).message}
                icon="alert-circle-outline"
                actionLabel="Retry"
                onAction={() => void paymentsQuery.refetch()}
              />
            ) : (
              <ListEmptyState
                entityName="payments"
                icon="cash-outline"
                onClearFilters={() => paymentsState.setSearchInput('')}
              />
            )
          }
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        {...listProps}
        data={transactions}
        keyExtractor={(item) => `${item.type}-${item.summary.id}`}
        searchBar={
          <FinanceSearchBar
            value={txnState.searchInput}
            onChangeText={txnState.setSearchInput}
            placeholder="Search reference or student…"
          />
        }
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.sm }}>
            <FinanceTransactionListItem
              transaction={item.summary}
              onPress={() => openTransaction(item.summary, item.type)}
              formatAmount={formatKes}
            />
          </View>
        )}
        refreshControl={
          <RefreshControl
            refreshing={txnQuery.isRefetching && !txnQuery.isFetchingNextPage}
            onRefresh={() => void txnQuery.refetch()}
            colors={[palette.primary]}
            tintColor={palette.primary}
          />
        }
        onEndReached={() => {
          if (txnQuery.hasNextPage && !txnQuery.isFetchingNextPage) {
            void txnQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={
          txnQuery.isFetchingNextPage ? (
            <ActivityIndicator color={palette.primary} style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          txnQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : txnQuery.isError ? (
            <ListEmptyState
              title="Could not load transactions"
              message={(txnQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void txnQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              entityName="transactions"
              icon="swap-horizontal-outline"
              onClearFilters={() => txnState.setSearchInput('')}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
