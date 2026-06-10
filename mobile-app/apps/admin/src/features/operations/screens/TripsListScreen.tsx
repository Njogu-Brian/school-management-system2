import { useTransportRoutes } from '@erp/core';
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
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { RefreshControl, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'TripsList'>;

export const TripsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, spacing } = useTheme();
  const [search, setSearch] = useState('');
  const query = useTransportRoutes({ search: search.trim() || undefined });

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <RegistryListLayout
        data={query.data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        showFilterTrigger={false}
        hero={
          <View>
            <AcademicScreenHeader
              title="Transport trips"
              subtitle="Routes and trip templates"
              onBack={() => navigation.goBack()}
            />
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.xs }}>
              <Button label="Add trip" onPress={() => navigation.navigate('TripForm', {})} />
              <Button label="Vehicles" variant="secondary" onPress={() => navigation.navigate('VehiclesList')} />
            </View>
          </View>
        }
        searchBar={<SearchBar value={search} onChangeText={setSearch} placeholder="Search trips…" />}
        renderItem={({ item }) => (
          <OpsListCard
            title={item.name}
            lines={[
              [item.vehicle_registration, item.driver_name].filter(Boolean).join(' · ') || 'No vehicle assigned',
              item.drop_points?.length ? `${item.drop_points.length} drop point${item.drop_points.length === 1 ? '' : 's'}` : null,
            ]}
            onPress={() => navigation.navigate('TripDetail', { tripId: item.id, tripName: item.name })}
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
              title="Could not load trips"
              message={(query.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void query.refetch()}
            />
          ) : (
            <ListEmptyState
              title="No transport trips"
              message={search ? 'No trips match your search.' : 'Add your first trip to get started.'}
              icon="bus-outline"
            />
          )
        }
      />
    </ScreenContainer>
  );
};
