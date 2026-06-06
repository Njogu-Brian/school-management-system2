import { useCan, useInfiniteInvoiceList, type InvoiceSummary } from '@erp/core';
import {
  countActiveFilters,
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
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { useBillingRegistryState } from '../hooks/useBillingRegistryState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'BillingList'>;

export const BillingListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { colors, palette, spacing } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);
  const { searchInput, setSearchInput, status, setStatus, filters } = useBillingRegistryState();
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
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={invoices}
        keyExtractor={(item) => String(item.id)}
        hero={
          <FinanceScreenHeader title="Billing" subtitle="Invoices (read-only)" onBack={() => navigation.goBack()} />
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
            <ListEmptyState entityName="invoices" icon="receipt-outline" onClearFilters={clearFilters} />
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
