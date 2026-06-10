import { useCan, useInfiniteExpenses } from '@erp/core';
import {
  AcademicScreenHeader,
  countActiveFilters,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { capitalizeStatus, formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'ExpensesList'>;

const STATUS_FILTERS = ['all', 'draft', 'submitted', 'approved', 'rejected', 'paid'] as const;
type StatusFilter = (typeof STATUS_FILTERS)[number];

const STATUS_TONES: Record<string, 'brand' | 'success' | 'warning' | 'danger' | 'info'> = {
  draft: 'info',
  submitted: 'warning',
  approved: 'success',
  rejected: 'danger',
  paid: 'brand',
};

const formatAmount = (value: number) =>
  `KES ${Number(value ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

export const ExpensesListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, radius, typography } = useTheme();
  const [status, setStatus] = useState<StatusFilter>('all');
  const [search, setSearch] = useState('');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const listQuery = useInfiniteExpenses({
    enabled: canView,
    status: status === 'all' ? undefined : status,
    search: search.trim() || undefined,
  });

  const items = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

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
        hero={
          <AcademicScreenHeader
            title="Expenses"
            subtitle="School expenditure registry"
            onBack={() => navigation.goBack()}
          />
        }
        searchBar={
          <SearchBar value={search} onChangeText={setSearch} placeholder="Search expense no, vendor…" />
        }
        activeFilterCount={countActiveFilters([status === 'all' ? null : status])}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={() => {
          setStatus('all');
          setSearch('');
          setFiltersOpen(false);
        }}
        filterContent={
          <FilterChipRow label="Status">
            {STATUS_FILTERS.map((s) => (
              <FilterChip key={s} label={capitalizeStatus(s)} active={status === s} onPress={() => setStatus(s)} />
            ))}
          </FilterChipRow>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('ExpenseDetail', { expenseId: item.id })}
            accessibilityRole="button"
            style={({ pressed }) => [
              styles.card,
              {
                backgroundColor: palette.surfaceRaised,
                borderColor: palette.borderSubtle,
                borderRadius: radius.lg,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <View style={styles.cardHeader}>
              <Text
                style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize, flex: 1 }}
                numberOfLines={1}
              >
                {item.expense_no ?? `Expense #${item.id}`}
              </Text>
              <StatusBadge label={capitalizeStatus(item.status ?? 'draft')} tone={STATUS_TONES[item.status ?? ''] ?? 'brand'} />
            </View>
            <Text style={{ color: palette.textSecondary, marginTop: 4 }} numberOfLines={1}>
              {[item.vendor, formatDateLabel(item.expense_date)].filter(Boolean).join(' · ') || '—'}
            </Text>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: 6 }}>
              {formatAmount(item.total)}
            </Text>
          </Pressable>
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
              title="Could not load expenses"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No expenses"
              message="No expenses match your filters."
              icon="wallet-outline"
              onClearFilters={() => {
                setStatus('all');
                setSearch('');
              }}
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
    padding: 14,
    marginBottom: 10,
    shadowColor: '#0f172a',
    shadowOpacity: 0.05,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
