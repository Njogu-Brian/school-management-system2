import { useDriverTrip, useDriverTripActions } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useIsFocused, useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import * as Location from 'expo-location';
import React, { useCallback, useEffect, useState } from 'react';
import { Text, View } from 'react-native';
import type { DriverStackParamList } from '../../../navigation/driver/driverStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<DriverStackParamList>;
type Route = RouteProp<DriverStackParamList, 'ActiveTrip'>;

const PING_INTERVAL_MS = 15_000;

export const ActiveTripScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const tripId = route.params.tripId;
  const isFocused = useIsFocused();
  const { palette, spacing, typography, radius, semantic } = useTheme();
  const tripQuery = useDriverTrip(tripId);
  const { stop, ping } = useDriverTripActions(tripId);

  const trip = tripQuery.data;
  const status = trip?.status ?? 'not_started';
  const inProgress = status === 'in_progress';

  const [lastPingAt, setLastPingAt] = useState<Date | null>(null);
  const [lastPingError, setLastPingError] = useState<string | null>(null);
  const [pinging, setPinging] = useState(false);

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
    } catch (err) {
      setLastPingError(err instanceof Error ? err.message : 'Location ping failed');
    } finally {
      setPinging(false);
    }
  }, [ping]);

  useEffect(() => {
    if (!isFocused || !inProgress) return;
    void sendPing();
    const id = setInterval(() => void sendPing(), PING_INTERVAL_MS);
    return () => clearInterval(id);
  }, [isFocused, inProgress, sendPing]);

  const endTrip = () => {
    confirmAction('End trip', 'Mark this trip as completed?', 'End trip', async () => {
      try {
        await stop.mutateAsync();
        showSuccess('Trip ended', 'Location sharing has stopped.');
        navigation.navigate('TripDetail', { tripId });
      } catch (err) {
        showError('Could not end trip', err instanceof Error ? err.message : 'Try again.');
      }
    });
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Active trip"
        subtitle={trip?.name ?? `Trip #${tripId}`}
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

          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.md,
              padding: spacing.md,
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>GPS ping</Text>
            <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
              Sharing location every 15 seconds while this screen is open.
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

          <View style={{ gap: spacing.sm }}>
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
