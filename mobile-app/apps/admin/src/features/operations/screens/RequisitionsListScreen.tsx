import { useCan, useInfiniteRequisitions } from '@erp/core';
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
import { capitalizeStatus } from '../../shared/utils/formatters';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'RequisitionsList'>;

const STATUS_FILTERS = ['all', 'pending', 'approved', 'rejected', 'fulfilled'] as const;
type StatusFilter = (typeof STATUS_FILTERS)[number];

const STATUS_TONES: Record<string, 'brand' | 'success' | 'warning' | 'danger' | 'info'> = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
  fulfilled: 'info',
};

export const RequisitionsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette } = useTheme();
  const [status, setStatus] = useState<StatusFilter>('pending');
  const [search, setSearch] = useState('');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const listQuery = useInfiniteRequisitions({
    enabled: canView,
    status: status === 'all' ? undefined : status,
  });

  const items = useMemo(() => {
    const all = listQuery.data?.pages.flatMap((p) => p.items) ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return all;
    return all.filter(
      (r) =>
        r.requisition_number.toLowerCase().includes(q) ||
        (r.requested_by ?? '').toLowerCase().includes(q) ||
        (r.purpose ?? '').toLowerCase().includes(q),
    );
  }, [listQuery.data, search]);

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
            <AcademicScreenHeader title="Requisitions" subtitle="Procurement queue" onBack={() => navigation.goBack()} />
            <View style={{ marginBottom: 8 }}>
              <Button label="New requisition" onPress={() => navigation.navigate('RequisitionForm')} />
            </View>
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search requisitions…" />}
        activeFilterCount={countActiveFilters([status === 'pending' ? null : status])}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={() => {
          setStatus('pending');
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
          <OpsListCard
            title={item.requisition_number}
            lines={[
              [item.requested_by, capitalizeStatus(item.type)].filter(Boolean).join(' · ') || null,
              item.purpose,
            ]}
            badge={{ label: capitalizeStatus(item.status), tone: STATUS_TONES[item.status] ?? 'brand' }}
            onPress={() => navigation.navigate('RequisitionDetail', { requisitionId: item.id })}
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
              title="Could not load requisitions"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No requisitions"
              message="No requisitions match your filters."
              icon="clipboard-outline"
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
});
