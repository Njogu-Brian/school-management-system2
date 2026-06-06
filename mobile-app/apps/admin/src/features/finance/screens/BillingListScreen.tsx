import { useCan, useInfiniteInvoiceList, type InvoiceSummary } from '@erp/core';
import {
  FinanceScreenHeader,
  FinanceSearchBar,
  InvoiceFilters,
  InvoiceListItem,
  ListEmptyState,
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
import { useBillingRegistryState } from '../hooks/useBillingRegistryState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'BillingList'>;

export const BillingListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { colors, palette, spacing } = useTheme();
  const { searchInput, setSearchInput, status, setStatus, filters } = useBillingRegistryState();
  const listQuery = useInfiniteInvoiceList(filters, { enabled: canView });

  const invoices = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const openDetail = useCallback(
    (summary: InvoiceSummary) => {
      navigation.navigate('InvoiceDetail', { invoiceId: summary.id, summary });
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
        data={invoices}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <FinanceScreenHeader title="Billing" subtitle="Invoices (read-only)" onBack={() => navigation.goBack()} />
            <FinanceSearchBar value={searchInput} onChangeText={setSearchInput} placeholder="Search student or invoice…" />
            <InvoiceFilters status={status} onStatusChange={setStatus} />
          </View>
        }
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.sm }}>
            <InvoiceListItem
              invoice={item}
              onPress={() => openDetail(item)}
              formatAmount={formatKes}
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
            <ListEmptyState
              entityName="invoices"
              icon="receipt-outline"
              onClearFilters={() => {
                setSearchInput('');
                setStatus('all');
              }}
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
