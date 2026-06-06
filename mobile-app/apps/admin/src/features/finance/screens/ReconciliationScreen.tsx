import { useCan, useInfiniteFinanceTransactions, type FinanceTransactionSummary } from '@erp/core';
import {
  countActiveFilters,
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
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { useReconciliationRegistryState } from '../hooks/useReconciliationRegistryState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'ReconciliationList'>;

export const ReconciliationScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { colors, palette, spacing } = useTheme();
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
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
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
            colors={[colors.primary]}
            tintColor={colors.primary}
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
            <ActivityIndicator color={colors.primary} style={{ marginVertical: 16 }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : !listQuery.isError ? (
            <ListEmptyState
              title="Queue is empty"
              message="No transactions in the reconciliation queue."
              icon="git-compare-outline"
              onClearFilters={clearFilters}
            />
          ) : null
        }
      />
      {listQuery.isError ? (
        <View style={{ padding: spacing.md }}>
          <Text style={{ color: colors.error, textAlign: 'center' }}>
            {(listQuery.error as Error).message}
          </Text>
          <Pressable onPress={() => void listQuery.refetch()} style={{ marginTop: spacing.sm, alignSelf: 'center' }}>
            <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
          </Pressable>
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
