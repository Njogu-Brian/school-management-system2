import { useTransportRoute } from '@erp/core';
import { useTripMutations } from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'TripDetail'>;

export const TripDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { tripId, tripName } = route.params;
  const { colors, spacing } = useTheme();
  const query = useTransportRoute(tripId);
  const { remove } = useTripMutations();

  const rows = useMemo(() => {
    const trip = query.data;
    if (!trip) return [];
    return [
      { label: 'Route', value: trip.name },
      { label: 'Vehicle', value: trip.vehicle_registration ?? '—' },
      { label: 'Driver', value: trip.driver_name ?? '—' },
      { label: 'Status', value: trip.status ?? '—' },
      { label: 'Description', value: trip.description ?? '—' },
    ];
  }, [query.data]);

  const stopRows = useMemo(
    () =>
      (query.data?.drop_points ?? []).map((stop, i) => ({
        label: `Stop ${i + 1}`,
        value: [stop.name, stop.pickup_time].filter(Boolean).join(' · ') || '—',
      })),
    [query.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title={tripName ?? 'Trip detail'} subtitle={`Trip #${tripId}`} onBack={() => navigation.goBack()} />
        <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
          <Button label="Edit" onPress={() => navigation.navigate('TripForm', { tripId })} />
          <Button
            label="Delete"
            variant="secondary"
            onPress={() =>
              Alert.alert('Delete trip', 'Remove this trip?', [
                { text: 'Cancel', style: 'cancel' },
                {
                  text: 'Delete',
                  style: 'destructive',
                  onPress: () =>
                    void remove.mutateAsync(tripId).then(() => navigation.goBack()).catch((e) => Alert.alert('Failed', (e as Error).message)),
                },
              ])
            }
          />
        </View>
        {query.isLoading ? (
          <ActivityIndicator color={colors.primary} />
        ) : query.isError ? (
          <Text style={{ color: colors.error }}>{(query.error as Error).message}</Text>
        ) : (
          <>
            <FinanceFieldSection title="Route" rows={rows} />
            {stopRows.length > 0 ? <FinanceFieldSection title="Stops" rows={stopRows} /> : null}
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
