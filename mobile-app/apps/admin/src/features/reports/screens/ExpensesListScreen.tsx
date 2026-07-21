import { useCan, useInfiniteExpenses } from '@erp/core';
import {
  AcademicScreenHeader,
  countActiveFilters,
  EmptyState,
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
  const { colors, palette, radius, spacing, typography, elevation } = useTheme();
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

  const clearFilters = () => {
    setStatus('all');
    setSearch('');
    setFiltersOpen(false);
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need reports.view permission to view expenses."
          icon="lock-closed-outline"
        />
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
        onClearFilters={clearFilters}
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
              elevation[1],
              {
                backgroundColor: palette.surfaceRaised,
                borderColor: palette.borderSubtle,
                borderRadius: radius.card,
                borderWidth: StyleSheet.hairlineWidth,
                padding: spacing.md,
                marginBottom: spacing.sm,
                opacity: pressed ? 0.9 : 1,
              },
            ]}
          >
            <View style={[styles.cardHeader, { gap: spacing.sm }]}>
              <Text
                style={{
                  color: palette.textPrimary,
                  fontWeight: typography.titleSmall.fontWeight,
                  fontSize: typography.titleSmall.fontSize,
                  lineHeight: typography.titleSmall.lineHeight,
                  flex: 1,
                }}
                numberOfLines={1}
              >
                {item.expense_no ?? `Expense #${item.id}`}
              </Text>
              <StatusBadge
                label={capitalizeStatus(item.status ?? 'draft')}
                tone={STATUS_TONES[item.status ?? ''] ?? 'brand'}
              />
            </View>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                fontWeight: typography.caption.fontWeight,
                lineHeight: typography.caption.lineHeight,
                marginTop: spacing.xs,
              }}
              numberOfLines={1}
            >
              {[item.vendor, formatDateLabel(item.expense_date)].filter(Boolean).join(' · ') || '—'}
            </Text>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: typography.titleSmall.fontWeight,
                fontSize: typography.body.fontSize,
                marginTop: spacing.sm,
              }}
            >
              {formatAmount(item.total)}
            </Text>
          </Pressable>
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
  cardHeader: { flexDirection: 'row', alignItems: 'center' },
});
