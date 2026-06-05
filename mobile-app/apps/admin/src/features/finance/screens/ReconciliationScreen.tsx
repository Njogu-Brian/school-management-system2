import { useCan, useInfiniteFinanceTransactions, type FinanceTransactionSummary } from '@erp/core';
import {
  FinanceScreenHeader,
  FinanceSearchBar,
  FinanceTransactionListItem,
  ReconciliationFilters,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useMemo } from 'react';
import {
  ActivityIndicator,
  FlatList,
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
  const { colors, palette, spacing, fontSizes } = useTheme();
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

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={transactions}
        keyExtractor={(item) => `${item.type}-${item.summary.id}`}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <FinanceScreenHeader
              title="Reconciliation"
              subtitle="Bank & M-Pesa transactions"
              onBack={() => navigation.goBack()}
            />
            <FinanceSearchBar value={searchInput} onChangeText={setSearchInput} placeholder="Search reference or student…" />
            <ReconciliationFilters queue={queue} onQueueChange={setQueue} />
          </View>
        }
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
          !listQuery.isLoading && !listQuery.isError ? (
            <Text style={{ color: palette.textSecondary, textAlign: 'center', marginTop: spacing.lg, fontSize: fontSizes.sm }}>
              No transactions in this queue.
            </Text>
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
