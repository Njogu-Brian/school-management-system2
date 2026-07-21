import { useCan, useVehicleMutations, useVehicles } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { confirmAction, showError } from '../../shared/utils/feedback';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'VehiclesList'>;

export const VehiclesListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing } = useTheme();
  const [search, setSearch] = useState('');
  const query = useVehicles({ enabled: canView, search: search.trim() || undefined });
  const { remove } = useVehicleMutations();

  const onDelete = (id: number, label: string) => {
    confirmAction(
      'Delete vehicle',
      `Remove ${label}?`,
      'Delete',
      () => void remove.mutateAsync(id).catch((e) => showError('Failed', (e as Error).message)),
      true,
    );
  };

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
        showFilterTrigger={false}
        hero={
          <View>
            <AcademicScreenHeader title="Vehicles" subtitle="Fleet registry" onBack={() => navigation.goBack()} />
            <Button
              label="Add vehicle"
              onPress={() => navigation.navigate('VehicleForm', {})}
              style={{ marginBottom: spacing.xs, alignSelf: 'flex-start' }}
            />
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search vehicles…" />}
        renderItem={({ item }) => (
          <OpsListCard
            title={item.vehicle_number}
            lines={[
              [item.driver_name, item.make, item.model, item.capacity ? `Cap ${item.capacity}` : null]
                .filter(Boolean)
                .join(' · ') || 'No details',
              `${item.trips_count ?? 0} trip${(item.trips_count ?? 0) === 1 ? '' : 's'}`,
            ]}
            onPress={() => navigation.navigate('VehicleForm', { vehicleId: item.id })}
            right={
              <Pressable
                onPress={() => onDelete(item.id, item.vehicle_number)}
                hitSlop={8}
                accessibilityLabel={`Delete ${item.vehicle_number}`}
              >
                <Ionicons name="trash-outline" size={20} color={colors.error} />
              </Pressable>
            }
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
              title="Could not load vehicles"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No vehicles"
              message={search ? 'No vehicles match your search.' : 'Add your first vehicle to get started.'}
              icon="car-sport-outline"
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
