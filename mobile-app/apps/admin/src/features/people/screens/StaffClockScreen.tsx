import {
  useCan,
  useStaffClockActions,
  useStaffClockHistory,
  useStaffClockRoster,
  useStaffClockToday,
  useStaffGeofence,
  useStaffGeofenceUpdate,
} from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, TextField, useTheme } from '@erp/ui';
import * as Location from 'expo-location';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'StaffClock'>;

export const StaffClockScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing } = useTheme();
  const canViewTeam = useCan('staff.view');
  const geoQuery = useStaffGeofence();
  const todayQuery = useStaffClockToday();
  const historyQuery = useStaffClockHistory();
  const rosterQuery = useStaffClockRoster({ enabled: canViewTeam });
  const { clockIn, clockOut } = useStaffClockActions();
  const updateGeofence = useStaffGeofenceUpdate();
  const [radiusMeters, setRadiusMeters] = useState('150');

  const isConfigured = Boolean(geoQuery.data?.is_configured);
  const canManageGeofence = Boolean(geoQuery.data?.can_manage);
  const busy = clockIn.isPending || clockOut.isPending || updateGeofence.isPending;

  const getCoords = async () => {
    const permission = await Location.requestForegroundPermissionsAsync();
    if (permission.status !== 'granted') {
      throw new Error('Location permission is required to clock in or out.');
    }
    const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
    return {
      latitude: pos.coords.latitude,
      longitude: pos.coords.longitude,
      accuracy_meters: pos.coords.accuracy ?? undefined,
    };
  };

  const perform = useCallback(
    async (mode: 'in' | 'out') => {
      if (!isConfigured) {
        showError(
          'Geofence not configured',
          canManageGeofence
            ? 'Set the school geofence using your device location below.'
            : 'Ask an admin to configure the school geofence.',
        );
        return;
      }
      try {
        const payload = await getCoords();
        const res = mode === 'in' ? await clockIn.mutateAsync(payload) : await clockOut.mutateAsync(payload);
        showSuccess('Success', res.message ?? `Clock-${mode} recorded.`);
      } catch (err) {
        showError('Unable to continue', (err as Error).message);
      }
    },
    [canManageGeofence, clockIn, clockOut, isConfigured],
  );

  const setGeofenceFromDevice = useCallback(async () => {
    const radius = Number(radiusMeters);
    if (!Number.isFinite(radius) || radius < 25 || radius > 5000) {
      showError('Invalid radius', 'Enter a radius between 25 and 5000 meters.');
      return;
    }
    try {
      const coords = await getCoords();
      await updateGeofence.mutateAsync({
        latitude: coords.latitude,
        longitude: coords.longitude,
        radius_meters: Math.round(radius),
      });
      showSuccess('Geofence updated', `School fence set to ${Math.round(radius)}m around your current location.`);
    } catch (err) {
      showError('Unable to set geofence', (err as Error).message);
    }
  }, [radiusMeters, updateGeofence]);

  const loading = geoQuery.isLoading || todayQuery.isLoading;

  useEffect(() => {
    if (geoQuery.data?.radius_meters) {
      setRadiusMeters(String(geoQuery.data.radius_meters));
    }
  }, [geoQuery.data?.radius_meters]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Sign in / out"
          subtitle="Clock in and out with GPS geofence"
          onBack={() => navigation.goBack()}
        />
        {canViewTeam ? (
          <>
            <Button
              label="Team attendance records"
              variant="secondary"
              onPress={() => navigation.navigate('StaffClockTeam')}
              style={{ marginBottom: spacing.sm }}
            />
            {rosterQuery.isSuccess && (rosterQuery.data?.length ?? 0) > 0 ? (
              <Text style={{ color: palette.textSecondary, fontSize: 12, marginBottom: spacing.md }}>
                View last 90 days of clock records for {(rosterQuery.data ?? []).length} staff member(s).
              </Text>
            ) : null}
          </>
        ) : null}

        {loading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : (
          <>
            <FinanceFieldSection
              title="Today"
              rows={[
                {
                  label: 'Geofence',
                  value: isConfigured
                    ? `${geoQuery.data?.radius_meters ?? 0}m radius`
                    : 'Not configured',
                },
                { label: 'Check in', value: todayQuery.data?.check_in_time ?? '—' },
                { label: 'Check out', value: todayQuery.data?.check_out_time ?? '—' },
              ]}
            />
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md }}>
              <Button label="Sign in" onPress={() => void perform('in')} loading={clockIn.isPending} disabled={busy} />
              <Button
                label="Sign out"
                variant="secondary"
                onPress={() => void perform('out')}
                loading={clockOut.isPending}
                disabled={busy}
              />
            </View>

            {canManageGeofence ? (
              <View style={{ marginTop: spacing.lg, gap: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>School geofence</Text>
                <Text style={{ color: palette.textSecondary, fontSize: 12 }}>
                  Set the fence center to your device’s current location. Staff must be inside this radius to sign in or out.
                </Text>
                <TextField
                  label="Radius (meters)"
                  value={radiusMeters}
                  onChangeText={setRadiusMeters}
                  keyboardType="number-pad"
                  placeholder="150"
                />
                <Button
                  label={isConfigured ? 'Update geofence from my location' : 'Set geofence from my location'}
                  variant="secondary"
                  onPress={() => void setGeofenceFromDevice()}
                  loading={updateGeofence.isPending}
                  disabled={busy}
                />
              </View>
            ) : null}

            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
              Recent history
            </Text>
            {(historyQuery.data ?? []).slice(0, 10).map((row) => (
              <Text key={row.id} style={{ color: palette.textSecondary, fontSize: 12, marginBottom: 4 }}>
                {row.date} · {row.check_in_time ?? '—'} → {row.check_out_time ?? '—'}
              </Text>
            ))}
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
