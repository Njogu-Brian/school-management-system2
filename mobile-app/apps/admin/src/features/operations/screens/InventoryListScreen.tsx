import { useCan, useInventoryItems } from '@erp/core';
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
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { RefreshControl, StyleSheet, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'InventoryList'>;

export const InventoryListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette } = useTheme();
  const [search, setSearch] = useState('');
  const [lowStockOnly, setLowStockOnly] = useState(false);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const query = useInventoryItems({
    enabled: canView,
    search: search.trim() || undefined,
    lowStock: lowStockOnly || undefined,
  });

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
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        hero={
          <AcademicScreenHeader title="Inventory" subtitle="Stock registry" onBack={() => navigation.goBack()} />
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search inventory…" />}
        activeFilterCount={countActiveFilters([lowStockOnly])}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={() => {
          setLowStockOnly(false);
          setFiltersOpen(false);
        }}
        filterContent={
          <FilterChipRow label="Stock level">
            <FilterChip label="All items" active={!lowStockOnly} onPress={() => setLowStockOnly(false)} />
            <FilterChip label="Low stock only" active={lowStockOnly} onPress={() => setLowStockOnly(true)} />
          </FilterChipRow>
        }
        renderItem={({ item }) => (
          <OpsListCard
            title={item.name}
            lines={[
              [item.category, item.brand].filter(Boolean).join(' · ') || null,
              `${item.quantity} ${item.unit ?? ''}`.trim(),
            ]}
            badge={
              item.is_low_stock
                ? { label: 'Low stock', tone: 'danger' }
                : { label: 'In stock', tone: 'success' }
            }
            onPress={() => navigation.navigate('InventoryItemDetail', { itemId: item.id })}
          />
        )}
        refreshControl={
          <RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} colors={[colors.primary]} />
        }
        ListEmptyComponent={
          query.isLoading ? (
            <SkeletonListRows variant="card" />
          ) : query.isError ? (
            <ListEmptyState
              title="Could not load inventory"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No inventory items"
              message="No items match your filters."
              icon="cube-outline"
              onClearFilters={
                search || lowStockOnly
                  ? () => {
                      setSearch('');
                      setLowStockOnly(false);
                    }
                  : undefined
              }
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
