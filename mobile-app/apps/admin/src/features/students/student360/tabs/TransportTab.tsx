import { useTransportRoute, type StudentDetail } from '@erp/core';
import { EmptyState, FinanceFieldSection, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, View } from 'react-native';

export interface TransportTabProps {
  student: StudentDetail;
}

/** Transport trip from `GET /routes/{trip_id}` (trip-backed routes API). */
export const TransportTab: React.FC<TransportTabProps> = ({ student }) => {
  const { colors, spacing } = useTheme();
  const routeQuery = useTransportRoute(student.tripId, { enabled: student.tripId != null });

  const assignmentRows = useMemo(
    () => [
      { label: 'Trip / route ID', value: student.tripId != null ? String(student.tripId) : '—' },
      {
        label: 'Drop-off point ID',
        value: student.dropOffPointId != null ? String(student.dropOffPointId) : '—',
      },
      { label: 'Drop-off (other)', value: student.dropOffPointOther ?? '—' },
    ],
    [student],
  );

  if (student.tripId == null) {
    return (
      <EmptyState
        title="No transport assignment"
        message="This student is not linked to a school transport trip."
        icon="bus-outline"
      />
    );
  }

  if (routeQuery.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (routeQuery.isError) {
    return (
      <EmptyState
        title="Could not load transport"
        message={(routeQuery.error as Error).message}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void routeQuery.refetch()}
      />
    );
  }

  const route = routeQuery.data;
  const routeRows = route
    ? [
        { label: 'Route name', value: route.name },
        { label: 'Vehicle', value: route.vehicle_registration ?? '—' },
        { label: 'Driver', value: route.driver_name ?? '—' },
        { label: 'Status', value: route.status ?? '—' },
      ]
    : [];

  const stopRows =
    route?.drop_points?.map((stop, index) => ({
      label: `Stop ${index + 1}`,
      value: [stop.name, stop.pickup_time].filter(Boolean).join(' · ') || '—',
    })) ?? [];

  return (
    <>
      <FinanceFieldSection title="Assignment" rows={assignmentRows} />
      {routeRows.length > 0 ? <FinanceFieldSection title="Route" rows={routeRows} /> : null}
      {stopRows.length > 0 ? <FinanceFieldSection title="Stops" rows={stopRows} /> : null}
    </>
  );
};
