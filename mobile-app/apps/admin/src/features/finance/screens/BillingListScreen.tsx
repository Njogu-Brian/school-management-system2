import { useCan, useInfiniteInvoiceList, type InvoiceSummary } from '@erp/core';
import {
  countActiveFilters,
  EmptyState,
  FinanceListKpiStrip,
  FinanceScreenHeader,
  FinanceSearchBar,
  InvoiceFilters,
  InvoiceListItem,
  ListEmptyState,
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
import { useBillingRegistryState } from '../hooks/useBillingRegistryState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'BillingList'>;

export const BillingListScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('finance.view');
  const { palette, spacing } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);
  const { searchInput, setSearchInput, status, setStatus, filters } =
    useBillingRegistryState(route.params?.hasBalance);
  const listQuery = useInfiniteInvoiceList(filters, { enabled: canView });

  const invoices = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const activeFilterCount = countActiveFilters([status]);

  const openDetail = useCallback(
    (summary: InvoiceSummary) => {
      navigation.navigate('InvoiceDetail', { invoiceId: summary.id, summary });
    },
    [navigation],
  );

  const clearFilters = useCallback(() => {
    setSearchInput('');
    setStatus('all');
    setFiltersOpen(false);
  }, [setSearchInput, setStatus]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view billing."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={invoices}
        keyExtractor={(item) => String(item.id)}
        hero={
          <View style={{ gap: spacing.sm }}>
            <FinanceScreenHeader
              title="Billing"
              subtitle="Invoices (read-only)"
              onBack={() => navigation.goBack()}
            />
            <FinanceListKpiStrip
              variant="billing"
              onCellPress={(key) => {
                if (key === 'arrears' || key === 'outstanding') {
                  navigation.navigate('FeeBalances');
                }
              }}
            />
          </View>
        }
        searchBar={
          <FinanceSearchBar
            value={searchInput}
            onChangeText={setSearchInput}
            placeholder="Search student or invoice…"
          />
        }
        activeFilterCount={activeFilterCount}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={clearFilters}
        filterContent={<InvoiceFilters status={status} onStatusChange={setStatus} />}
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.sm }}>
            <InvoiceListItem invoice={item} onPress={() => openDetail(item)} formatAmount={formatKes} />
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
              title="Could not load invoices"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState entityName="invoices" icon="receipt-outline" onClearFilters={clearFilters} />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
