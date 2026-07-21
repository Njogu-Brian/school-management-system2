import { useTransportRoute } from '@erp/core';
import { useTripMutations } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ConfirmDialog,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, ScrollView, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'TripDetail'>;

export const TripDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { tripId, tripName } = route.params;
  const { palette, spacing } = useTheme();
  const query = useTransportRoute(tripId);
  const { remove } = useTripMutations();
  const [deleteVisible, setDeleteVisible] = useState(false);

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

  const submitDelete = () => {
    setDeleteVisible(false);
    void remove
      .mutateAsync(tripId)
      .then(() => navigation.goBack())
      .catch((e) => showError('Failed', (e as Error).message));
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title={tripName ?? 'Trip detail'} subtitle={`Trip #${tripId}`} onBack={() => navigation.goBack()} />
        <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
          <Button label="Edit" onPress={() => navigation.navigate('TripForm', { tripId })} />
          <Button label="Delete" variant="secondary" onPress={() => setDeleteVisible(true)} />
        </View>
        {query.isLoading ? (
          <ActivityIndicator color={palette.primary} />
        ) : query.isError ? (
          <EmptyState
            title="Could not load trip"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : (
          <>
            <FinanceFieldSection title="Route" rows={rows} />
            {stopRows.length > 0 ? <FinanceFieldSection title="Stops" rows={stopRows} /> : null}
          </>
        )}
      </ScrollView>

      <ConfirmDialog
        visible={deleteVisible}
        title="Delete trip"
        message="Remove this trip?"
        confirmLabel="Delete"
        cancelLabel="Cancel"
        destructive
        loading={remove.isPending}
        onConfirm={submitDelete}
        onCancel={() => setDeleteVisible(false)}
      />
    </ScreenContainer>
  );
};
