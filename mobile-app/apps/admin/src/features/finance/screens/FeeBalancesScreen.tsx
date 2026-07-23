import { useCan, useInfiniteInvoiceList, type InvoiceSummary } from '@erp/core';
import {
  EmptyState,
  FinanceScreenHeader,
  FinanceSearchBar,
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
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'FeeBalances'>;

type StudentArrearsRow = {
  studentId: number;
  studentName: string;
  admissionNumber: string | null;
  classroom: string | null;
  balance: number;
  invoiceCount: number;
  primaryInvoiceId: number;
  primarySummary: InvoiceSummary;
};

/**
 * Fee balance / arrears list — mirrors web Fee Balance Report “with balance”
 * by aggregating outstanding invoices per student.
 */
export const FeeBalancesScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('finance.view');
  const { palette, spacing, typography, radius, colors } = useTheme();
  const [searchInput, setSearchInput] = useState('');
  const [debounced, setDebounced] = useState('');

  React.useEffect(() => {
    const t = setTimeout(() => setDebounced(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  const filters = useMemo(
    () => ({
      has_balance: true as const,
      search: debounced || undefined,
      per_page: 50,
    }),
    [debounced],
  );

  const listQuery = useInfiniteInvoiceList(filters, { enabled: canView });

  const invoices = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const rows = useMemo((): StudentArrearsRow[] => {
    const map = new Map<number, StudentArrearsRow>();
    for (const inv of invoices) {
      // Skip orphaned invoices (archived/alumni students are hidden by API scope → null name).
      if (!inv.studentId || !inv.studentName?.trim()) {
        continue;
      }
      const existing = map.get(inv.studentId);
      if (!existing) {
        map.set(inv.studentId, {
          studentId: inv.studentId,
          studentName: inv.studentName.trim(),
          admissionNumber: inv.studentAdmissionNumber ?? null,
          classroom: null,
          balance: inv.balance,
          invoiceCount: 1,
          primaryInvoiceId: inv.id,
          primarySummary: inv,
        });
      } else {
        existing.balance += inv.balance;
        existing.invoiceCount += 1;
        if (inv.balance > existing.primarySummary.balance) {
          existing.primaryInvoiceId = inv.id;
          existing.primarySummary = inv;
        }
      }
    }
    return Array.from(map.values()).sort((a, b) => b.balance - a.balance);
  }, [invoices]);

  const openRow = useCallback(
    (row: StudentArrearsRow) => {
      navigation.navigate('InvoiceDetail', {
        invoiceId: row.primaryInvoiceId,
        summary: row.primarySummary,
      });
    },
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You do not have permission to view fee balances."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={rows}
        keyExtractor={(item) => String(item.studentId)}
        hero={
          <FinanceScreenHeader
            title="Students in arrears"
            subtitle="Outstanding fee balances (like web Fee Balance Report)"
            onBack={() => navigation.goBack()}
          />
        }
        searchBar={
          <FinanceSearchBar
            value={searchInput}
            onChangeText={setSearchInput}
            placeholder="Search student…"
          />
        }
        showFilterTrigger={false}
        renderItem={({ item }) => (
          <Pressable
            onPress={() => openRow(item)}
            style={{
              backgroundColor: palette.surfaceRaised,
              borderColor: palette.borderSubtle,
              borderWidth: StyleSheet.hairlineWidth,
              borderRadius: radius.card,
              padding: spacing.md,
              marginBottom: spacing.sm,
            }}
          >
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm }}>
              <View style={{ flex: 1 }}>
                <Text
                  style={{
                    color: palette.textMain,
                    fontWeight: '700',
                    fontSize: typography.body.fontSize,
                  }}
                  numberOfLines={1}
                >
                  {item.studentName}
                </Text>
                <Text
                  style={{
                    color: palette.textMuted,
                    fontSize: typography.caption.fontSize,
                    marginTop: 2,
                  }}
                  numberOfLines={1}
                >
                  {[item.admissionNumber, item.classroom].filter(Boolean).join(' · ') || '—'}
                </Text>
                {item.invoiceCount > 1 ? (
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontSize: typography.caption.fontSize,
                      marginTop: 4,
                    }}
                  >
                    {item.invoiceCount} invoices with balance
                  </Text>
                ) : null}
              </View>
              <Text
                style={{
                  color: colors.error,
                  fontWeight: '800',
                  fontSize: typography.body.fontSize,
                }}
              >
                {formatKes(item.balance)}
              </Text>
            </View>
          </Pressable>
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
            <ActivityIndicator color={colors.primary} style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load arrears"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState entityName="students in arrears" icon="checkmark-circle-outline" />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
