import { paymentMethodLabel, useCan, useInfinitePaymentList, type PaymentSummary } from '@erp/core';
import {
  FinanceScreenHeader,
  FinanceSearchBar,
  PaymentListItem,
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
import { useCollectionsRegistryState } from '../hooks/useCollectionsRegistryState';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'CollectionsList'>;

export const CollectionsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const { searchInput, setSearchInput, filters } = useCollectionsRegistryState();
  const listQuery = useInfinitePaymentList(filters, { enabled: canView });

  const payments = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const openDetail = useCallback(
    (summary: PaymentSummary) => {
      navigation.navigate('PaymentDetail', { paymentId: summary.id, summary });
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
        data={payments}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <FinanceScreenHeader title="Collections" subtitle="Payments (read-only)" onBack={() => navigation.goBack()} />
            <FinanceSearchBar value={searchInput} onChangeText={setSearchInput} placeholder="Search receipt or student…" />
          </View>
        }
        renderItem={({ item }) => (
          <View style={{ marginBottom: spacing.sm }}>
            <PaymentListItem
              payment={item}
              onPress={() => openDetail(item)}
              formatAmount={formatKes}
              methodLabel={paymentMethodLabel}
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
              No payments match your search.
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
