import { useCan, useInfiniteLedgerPostings } from '@erp/core';
import {
  AcademicScreenHeader,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatDateLabel, formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'Ledger'>;

const ACCOUNT_FILTERS = [
  { value: '', label: 'All accounts' },
  { value: 'EXPENSE', label: 'Expense' },
  { value: 'CASH_BANK', label: 'Cash & bank' },
] as const;

export const LedgerScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, typography } = useTheme();
  const [account, setAccount] = useState('');

  const listQuery = useInfiniteLedgerPostings({
    enabled: canView,
    accountCode: account || undefined,
  });

  const items = useMemo(() => listQuery.data?.pages.flatMap((p) => p.items) ?? [], [listQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={items}
        keyExtractor={(item) => String(item.id)}
        showFilterTrigger={false}
        searchBar={null}
        hero={
          <View>
            <AcademicScreenHeader
              title="General ledger"
              subtitle="Posted double entries"
              onBack={() => navigation.goBack()}
            />
            <FilterChipRow>
              {ACCOUNT_FILTERS.map((a) => (
                <FilterChip key={a.value} label={a.label} active={account === a.value} onPress={() => setAccount(a.value)} />
              ))}
            </FilterChipRow>
          </View>
        }
        renderItem={({ item }) => (
          <View style={[styles.card, { backgroundColor: palette.surfaceRaised, borderColor: palette.borderSubtle }]}>
            <View style={styles.cardHeader}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>{item.account_code}</Text>
              <StatusBadge
                label={item.dr_cr === 'dr' ? 'Debit' : 'Credit'}
                tone={item.dr_cr === 'dr' ? 'info' : 'success'}
                compact
              />
            </View>
            <View style={styles.cardFooter}>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {formatDateLabel(item.posting_date)}
                {item.source_type ? ` · ${item.source_type.replace(/_/g, ' ')} #${item.source_id ?? ''}` : ''}
              </Text>
              <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>{formatKes(item.amount)}</Text>
            </View>
          </View>
        )}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) void listQuery.fetchNextPage();
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} /> : null}
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load ledger"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No postings"
              message="Ledger entries are created automatically when expenses are paid."
              icon="book-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
  },
});
