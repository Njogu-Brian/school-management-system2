import { useDriverTrip, useDriverTripActions, useMarkTransportStop, useTripStops } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TransportMapView,
  useTheme,
  type MapPin,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { DriverStackParamList } from '../../../navigation/driver/driverStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<DriverStackParamList>;
type Route = RouteProp<DriverStackParamList, 'ActiveTrip'>;

export const ActiveTripScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const tripId = route.params.tripId;
  const { palette, spacing, typography, radius } = useTheme();
  const tripQuery = useDriverTrip(tripId, { refetchInterval: 15_000 });
  const { stop } = useDriverTripActions(tripId);
  const stopsQuery = useTripStops(tripId, { enabled: true, refetchInterval: 15_000 });
  const markStop = useMarkTransportStop(tripId);

  const trip = tripQuery.data;
  const status = trip?.status ?? 'not_started';
  const inProgress = status === 'in_progress';

  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [tapCoords, setTapCoords] = useState<{ latitude: number; longitude: number } | null>(null);

  const endTrip = () => {
    confirmAction('End trip', 'Mark this trip as completed?', 'End trip', async () => {
      try {
        await stop.mutateAsync();
        showSuccess('Trip ended', 'This trip is marked completed.');
        navigation.navigate('TripDetail', { tripId });
      } catch (err) {
        showError('Could not end trip', err instanceof Error ? err.message : 'Try again.');
      }
    });
  };

  const stopRows = stopsQuery.data?.stops ?? [];
  const kindLabel =
    stopsQuery.data?.kind === 'evening_dropoff' ? 'Evening drop-off' : 'Morning pickup';

  const mapPins: MapPin[] = useMemo(() => {
    const pins: MapPin[] = [];
    const busLat = (trip as { last_latitude?: number | null } | undefined)?.last_latitude;
    const busLng = (trip as { last_longitude?: number | null } | undefined)?.last_longitude;
    if (busLat != null && busLng != null) {
      pins.push({
        id: 'bus',
        latitude: busLat,
        longitude: busLng,
        title: 'Bus',
        subtitle: trip?.vehicle_registration ?? 'Live',
        tone: 'bus',
      });
    }
    for (const s of stopRows) {
      if (s.latitude == null || s.longitude == null) continue;
      pins.push({
        id: `stop-${s.student_id}`,
        latitude: s.latitude,
        longitude: s.longitude,
        title: s.full_name ?? `Student #${s.student_id}`,
        subtitle: kindLabel,
        tone: selectedStudentId === s.student_id ? 'selected' : 'stop',
      });
    }
    if (tapCoords) {
      pins.push({
        id: 'tap',
        latitude: tapCoords.latitude,
        longitude: tapCoords.longitude,
        title: 'Selected point',
        tone: 'user',
      });
    }
    return pins;
  }, [trip, stopRows, kindLabel, selectedStudentId, tapCoords]);

  const markWithCoords = async (latitude: number, longitude: number) => {
    if (!selectedStudentId) {
      showError('Select a child', 'Tap a student in the roster, then mark their stop.');
      return;
    }
    try {
      await markStop.mutateAsync({
        student_id: selectedStudentId,
        latitude,
        longitude,
        kind: stopsQuery.data?.kind,
      });
      setTapCoords(null);
      showSuccess('Stop saved', `${kindLabel} coordinates and time recorded.`);
      void stopsQuery.refetch();
    } catch (err) {
      showError('Could not save stop', err instanceof Error ? err.message : 'Try again.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Active trip"
        subtitle={trip?.name ?? trip?.route_name ?? `Trip #${tripId}`}
        onBack={() => navigation.goBack()}
      />

      {tripQuery.isLoading ? (
        <SkeletonListRows count={4} />
      ) : tripQuery.isError ? (
        <EmptyState
          title="Could not load trip"
          message={tripQuery.error instanceof Error ? tripQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Back"
          onAction={() => navigation.goBack()}
        />
      ) : !inProgress ? (
        <EmptyState
          title="Trip not in progress"
          message="Start the trip from the trip detail screen. Live bus location is turned off in this version."
          icon="bus-outline"
          actionLabel="Trip detail"
          onAction={() => navigation.navigate('TripDetail', { tripId })}
        />
      ) : (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.md }}>
            <StatusBadge label="in progress" tone="success" />
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {trip?.vehicle_registration ?? 'Vehicle assigned'}
            </Text>
          </View>

          <TransportMapView pins={mapPins} height={280} onMapPress={(coords) => setTapCoords(coords)} />

          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.md,
              padding: spacing.md,
              marginTop: spacing.md,
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Live location is off</Text>
            <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
              This version does not share the bus location. Mark a stop by tapping the map, then save it for the selected child.
            </Text>
          </View>

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
            {kindLabel} roster
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
            Select a child, tap the map, then save that point as their stop.
          </Text>

          {stopRows.map((s) => {
            const selected = selectedStudentId === s.student_id;
            return (
              <Pressable
                key={s.student_id}
                onPress={() => setSelectedStudentId(s.student_id)}
                style={{
                  padding: spacing.sm,
                  borderRadius: radius.md,
                  borderWidth: 1,
                  borderColor: selected ? palette.primary : palette.border,
                  backgroundColor: selected ? palette.surface : palette.background,
                  marginBottom: spacing.xs,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.full_name}</Text>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                  {s.admission_number ?? `ID ${s.student_id}`}
                  {s.latitude != null ? ' · pin saved' : ' · no pin yet'}
                  {s.marked_today ? ' · marked today' : ''}
                </Text>
              </Pressable>
            );
          })}

          <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
            <Button
              label="Mark stop — map tap"
              variant="primary"
              loading={markStop.isPending}
              disabled={!selectedStudentId || !tapCoords}
              onPress={() => tapCoords && void markWithCoords(tapCoords.latitude, tapCoords.longitude)}
            />
            <Button
              label="Boarding checklist"
              variant="secondary"
              onPress={() => navigation.navigate('BoardingChecklist', { tripId })}
            />
            <Button label="End trip" variant="primary" loading={stop.isPending} onPress={endTrip} />
          </View>
        </>
      )}
    </ScreenContainer>
  );
};
