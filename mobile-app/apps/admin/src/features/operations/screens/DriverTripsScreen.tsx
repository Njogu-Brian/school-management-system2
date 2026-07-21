import { useDriverTrips } from '@erp/core';
import {
  AcademicScreenHeader,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { FlatList } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'DriverTrips'>;

export const DriverTripsScreen: React.FC<Props> = ({ navigation }) => {
  const { spacing } = useTheme();
  const query = useDriverTrips();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader title="Driver trips" subtitle="Today's assigned routes" onBack={() => navigation.goBack()} />
        }
        renderItem={({ item }) => (
          <OpsListCard
            title={item.name ?? `Trip #${item.id}`}
            lines={[[item.direction, item.departure_time, item.vehicle_registration].filter(Boolean).join(' · ') || null]}
            onPress={() =>
              navigation.navigate('DriverTripDetail', { tripId: item.id, tripName: item.name ?? undefined })
            }
          />
        )}
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
              title="No trips assigned"
              message="No trips are assigned to you today."
              icon="car-outline"
            />
          )
        }
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};
