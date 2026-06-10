import { useCan, useInfiniteVisitors } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  countActiveFilters,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { formatDateTimeLabel } from '../../shared/utils/formatters';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'VisitorsList'>;

type Filter = 'all' | 'on_site' | 'checked_out';

const FILTER_LABELS: Record<Filter, string> = {
  all: 'All',
  on_site: 'On site',
  checked_out: 'Checked out',
};

export const VisitorsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing } = useTheme();
  const [filter, setFilter] = useState<Filter>('all');
  const [search, setSearch] = useState('');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const listQuery = useInfiniteVisitors({
    enabled: canView,
    onSite: filter === 'on_site' ? true : undefined,
  });

  const items = useMemo(() => {
    let rows = listQuery.data?.pages.flatMap((p) => p.items) ?? [];
    if (filter === 'checked_out') rows = rows.filter((v) => !v.on_site);
    const q = search.trim().toLowerCase();
    if (!q) return rows;
    return rows.filter(
      (v) =>
        v.visitor_name.toLowerCase().includes(q) ||
        (v.purpose ?? '').toLowerCase().includes(q) ||
        (v.host_name ?? '').toLowerCase().includes(q) ||
        (v.organization ?? '').toLowerCase().includes(q),
    );
  }, [listQuery.data, filter, search]);

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
          <View>
            <AcademicScreenHeader title="Visitors" subtitle="Front desk log" onBack={() => navigation.goBack()} />
            <Button
              label="Check in visitor"
              onPress={() => navigation.navigate('VisitorCheckIn')}
              style={{ marginBottom: spacing.xs, alignSelf: 'flex-start' }}
            />
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search visitors…" />}
        activeFilterCount={countActiveFilters([filter])}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={() => {
          setFilter('all');
          setSearch('');
          setFiltersOpen(false);
        }}
        filterContent={
          <FilterChipRow label="Presence">
            {(Object.keys(FILTER_LABELS) as Filter[]).map((f) => (
              <FilterChip key={f} label={FILTER_LABELS[f]} active={filter === f} onPress={() => setFilter(f)} />
            ))}
          </FilterChipRow>
        }
        renderItem={({ item }) => (
          <OpsListCard
            title={item.visitor_name}
            lines={[
              [item.purpose, item.host_name].filter(Boolean).join(' · ') || null,
              formatDateTimeLabel(item.checked_in_at),
            ]}
            badge={
              item.on_site
                ? { label: 'On site', tone: 'success' }
                : { label: 'Checked out', tone: 'info' }
            }
            onPress={() => navigation.navigate('VisitorDetail', { visitorId: item.id })}
          />
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
              title="Could not load visitors"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No visitors"
              message="No visitor records match your filters."
              icon="person-outline"
              onClearFilters={() => {
                setFilter('all');
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
});
