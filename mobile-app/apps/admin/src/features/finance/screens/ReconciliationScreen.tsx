import { useCan, useInfiniteFinanceTransactions, type FinanceTransactionSummary } from '@erp/core';
import {
  countActiveFilters,
  EmptyState,
  FinanceScreenHeader,
  FinanceSearchBar,
  FinanceTransactionListItem,
  ListEmptyState,
  ReconciliationFilters,
  RegistryListLayout,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
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
import { useReconciliationRegistryState } from '../hooks/useReconciliationRegistryState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'ReconciliationList'>;

export const ReconciliationScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { palette, spacing } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);
  const { searchInput, setSearchInput, queue, setQueue, filters } = useReconciliationRegistryState();
  const listQuery = useInfiniteFinanceTransactions(filters, { enabled: canView });

  const transactions = useMemo(() => {
    const pages = listQuery.data?.pages ?? [];
    return pages.flatMap((p) =>
      p.raw.map((raw, idx) => ({
        summary: p.items[idx],
        type: raw.transaction_type as 'bank' | 'c2b',
      })),
    );
  }, [listQuery.data]);

  const activeFilterCount = countActiveFilters([queue !== 'pending' ? queue : null]);

  const openDetail = useCallback(
    (summary: FinanceTransactionSummary, type: 'bank' | 'c2b') => {
      navigation.navigate('TransactionDetail', {
        transactionId: summary.id,
        transactionType: type,
        summary,
      });
    },
    [navigation],
  );

  const clearFilters = useCallback(() => {
    setSearchInput('');
    setQueue('pending');
    setFiltersOpen(false);
  }, [setSearchInput, setQueue]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view reconciliation."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={transactions}
        keyExtractor={(item) => `${item.type}-${item.summary.id}`}
        hero={
          <FinanceScreenHeader
            title="Reconciliation"
            subtitle="Bank & M-Pesa transactions"
            onBack={() => navigation.goBack()}
          />
        }
        searchBar={
          <FinanceSearchBar
            value={searchInput}
            onChangeText={setSearchInput}
            placeholder="Search reference or student…"
          />
        }
        activeFilterCount={activeFilterCount}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={clearFilters}
        filterContent={<ReconciliationFilters queue={queue} onQueueChange={setQueue} />}
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.sm }}>
            <FinanceTransactionListItem
              transaction={item.summary}
              onPress={() => openDetail(item.summary, item.type)}
              formatAmount={(n) => formatKes(n ?? 0)}
            />
          </View>
        )}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[palette.primary]}
            tintColor={palette.primary}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
            void listQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? (
            <ActivityIndicator color={palette.primary} style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load queue"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="Queue is empty"
              message="No transactions in the reconciliation queue."
              icon="git-compare-outline"
              onClearFilters={clearFilters}
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
