import { useCan, useInfiniteAssets } from '@erp/core';
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

type Props = StackScreenProps<OperationsStackParamList, 'AssetsList'>;

const STATUS_FILTERS = ['all', 'active', 'assigned', 'maintenance', 'retired'] as const;
type StatusFilter = (typeof STATUS_FILTERS)[number];

const STATUS_TONES: Record<string, 'brand' | 'success' | 'warning' | 'danger' | 'info'> = {
  active: 'success',
  assigned: 'info',
  maintenance: 'warning',
  retired: 'danger',
};

export const AssetsListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette } = useTheme();
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<StatusFilter>('all');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const listQuery = useInfiniteAssets({
    enabled: canView,
    search: search.trim() || undefined,
    status: status === 'all' ? undefined : status,
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
        hero={
          <View>
            <AcademicScreenHeader title="Fixed assets" subtitle="Asset registry" onBack={() => navigation.goBack()} />
            <View style={{ marginBottom: 8 }}>
              <Button label="Register asset" onPress={() => navigation.navigate('AssetForm', {})} />
            </View>
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search assets…" />}
        activeFilterCount={countActiveFilters([status])}
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
          <OpsListCard
            title={item.name}
            lines={[
              [item.asset_tag, item.category].filter(Boolean).join(' · ') || null,
              item.assigned_to ? `Assigned to ${item.assigned_to}` : item.location,
            ]}
            badge={
              item.status
                ? { label: capitalizeStatus(item.status), tone: STATUS_TONES[item.status] ?? 'brand' }
                : undefined
            }
            onPress={() => navigation.navigate('AssetDetail', { assetId: item.id })}
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
              title="Could not load assets"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No assets"
              message="No fixed assets match your filters."
              icon="hardware-chip-outline"
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
