import {
  driverTransportApi,
  useDriverTrip,
  useDriverTripActions,
  useMarkTransportStop,
  useTripStops,
} from '@erp/core';
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
import { useIsFocused, useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { DriverStackParamList } from '../../../navigation/driver/driverStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<DriverStackParamList>;
type Route = RouteProp<DriverStackParamList, 'ActiveTrip'>;

const PING_INTERVAL_MS = 15_000;
const BACKGROUND_LOCATION_TASK = 'DRIVER_TRIP_LOCATION';

type BgLocationData = {
  locations?: Array<{
    coords: {
      latitude: number;
      longitude: number;
      accuracy: number | null;
      speed: number | null;
      heading: number | null;
    };
  }>;
};

// Task must be defined at module scope for expo-location background updates.
TaskManager.defineTask(BACKGROUND_LOCATION_TASK, async ({ data, error }) => {
  if (error) return;
  const tripId = (globalThis as { __activeDriverTripId?: number }).__activeDriverTripId;
  if (!tripId) return;
  const locs = (data as BgLocationData | undefined)?.locations;
  const loc = locs?.[locs.length - 1];
  if (!loc) return;
  try {
    await driverTransportApi.pingLocation(tripId, {
      latitude: loc.coords.latitude,
      longitude: loc.coords.longitude,
      accuracy_meters: loc.coords.accuracy ?? undefined,
      speed_kmh: loc.coords.speed != null ? loc.coords.speed * 3.6 : undefined,
      heading: loc.coords.heading ?? undefined,
    });
  } catch {
    // Best-effort background ping; foreground loop remains the primary path.
  }
});

async function startBackgroundPings(tripId: number) {
  (globalThis as { __activeDriverTripId?: number }).__activeDriverTripId = tripId;
  const fg = await Location.requestForegroundPermissionsAsync();
  if (fg.status !== 'granted') return;
  const bg = await Location.requestBackgroundPermissionsAsync();
  if (bg.status !== 'granted') return;
  const started = await Location.hasStartedLocationUpdatesAsync(BACKGROUND_LOCATION_TASK).catch(() => false);
  if (started) return;
  await Location.startLocationUpdatesAsync(BACKGROUND_LOCATION_TASK, {
    accuracy: Location.Accuracy.Balanced,
    timeInterval: PING_INTERVAL_MS,
    distanceInterval: 25,
    showsBackgroundLocationIndicator: true,
    foregroundService: {
      notificationTitle: 'School bus trip active',
      notificationBody: 'Sharing live location with the school.',
      notificationColor: '#004A99',
    },
  });
}

async function stopBackgroundPings() {
  (globalThis as { __activeDriverTripId?: number }).__activeDriverTripId = undefined;
  const started = await Location.hasStartedLocationUpdatesAsync(BACKGROUND_LOCATION_TASK).catch(() => false);
  if (started) {
    await Location.stopLocationUpdatesAsync(BACKGROUND_LOCATION_TASK).catch(() => undefined);
  }
}

export const ActiveTripScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const tripId = route.params.tripId;
  const isFocused = useIsFocused();
  const { palette, spacing, typography, radius, semantic } = useTheme();
  const tripQuery = useDriverTrip(tripId, { refetchInterval: 15_000 });
  const { stop, ping } = useDriverTripActions(tripId);
  const stopsQuery = useTripStops(tripId, { enabled: true, refetchInterval: 15_000 });
  const markStop = useMarkTransportStop(tripId);

  const trip = tripQuery.data;
  const status = trip?.status ?? 'not_started';
  const inProgress = status === 'in_progress';

  const [lastPingAt, setLastPingAt] = useState<Date | null>(null);
  const [lastPingError, setLastPingError] = useState<string | null>(null);
  const [pinging, setPinging] = useState(false);
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [tapCoords, setTapCoords] = useState<{ latitude: number; longitude: number } | null>(null);

  const sendPing = useCallback(async () => {
    setPinging(true);
    try {
      const { status: perm } = await Location.requestForegroundPermissionsAsync();
      if (perm !== 'granted') {
        setLastPingError('Location permission denied');
        return;
      }
      const loc = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      await ping.mutateAsync({
        latitude: loc.coords.latitude,
        longitude: loc.coords.longitude,
        accuracy_meters: loc.coords.accuracy ?? undefined,
        speed_kmh: loc.coords.speed != null ? loc.coords.speed * 3.6 : undefined,
        heading: loc.coords.heading ?? undefined,
      });
      setLastPingAt(new Date());
      setLastPingError(null);
      void tripQuery.refetch();
    } catch (err) {
      setLastPingError(err instanceof Error ? err.message : 'Location ping failed');
    } finally {
      setPinging(false);
    }
  }, [ping, tripQuery]);

  useEffect(() => {
    if (!inProgress) {
      void stopBackgroundPings();
      return;
    }
    void startBackgroundPings(tripId);
    return () => {
      // Keep background pings while trip is in progress even if leaving this screen.
    };
  }, [inProgress, tripId]);

  useEffect(() => {
    if (!isFocused || !inProgress) return;
    void sendPing();
    const id = setInterval(() => void sendPing(), PING_INTERVAL_MS);
    return () => clearInterval(id);
  }, [isFocused, inProgress, sendPing]);

  const endTrip = () => {
    confirmAction('End trip', 'Mark this trip as completed?', 'End trip', async () => {
      try {
        await stopBackgroundPings();
        await stop.mutateAsync();
        showSuccess('Trip ended', 'Location sharing has stopped.');
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

  const markMyLocation = async () => {
    try {
      const { status: perm } = await Location.requestForegroundPermissionsAsync();
      if (perm !== 'granted') {
        showError('Location needed', 'Allow location access to mark this stop.');
        return;
      }
      const loc = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      await markWithCoords(loc.coords.latitude, loc.coords.longitude);
    } catch (err) {
      showError('Could not get location', err instanceof Error ? err.message : 'Try again.');
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
          message="Start the trip from the trip detail screen to share live location."
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

          <TransportMapView
            pins={mapPins}
            height={280}
            showsUserLocation
            onMapPress={(coords) => setTapCoords(coords)}
          />

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
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>GPS ping</Text>
            <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
              Sharing location every 15 seconds while the trip is in progress (including in the background when allowed).
            </Text>
            <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
              {pinging
                ? 'Sending location…'
                : lastPingAt
                  ? `Last ping: ${lastPingAt.toLocaleTimeString()}`
                  : 'Waiting for first ping…'}
            </Text>
            {lastPingError ? (
              <Text style={{ color: semantic.danger.fg, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                {lastPingError}
              </Text>
            ) : lastPingAt ? (
              <Text style={{ color: semantic.success.fg, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                Location shared successfully
              </Text>
            ) : null}
          </View>

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
            {kindLabel} roster
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
            Select a child, then use your location or tap the map to save coordinates and time.
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
              label="Mark stop — use my location"
              variant="primary"
              loading={markStop.isPending}
              disabled={!selectedStudentId}
              onPress={() => void markMyLocation()}
            />
            <Button
              label="Mark stop — map tap"
              variant="secondary"
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
            <Button label="Ping now" variant="ghost" loading={pinging} onPress={() => void sendPing()} />
          </View>
        </>
      )}
    </ScreenContainer>
  );
};
