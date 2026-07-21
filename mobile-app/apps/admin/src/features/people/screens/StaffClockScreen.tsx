import { useCan, useStaffClockActions, useStaffClockHistory, useStaffClockRoster, useStaffClockToday, useStaffGeofence } from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import * as Location from 'expo-location';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback } from 'react';
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

  const isConfigured = Boolean(geoQuery.data?.is_configured);
  const busy = clockIn.isPending || clockOut.isPending;

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
        showError('Geofence not configured', 'Ask an admin to configure the school geofence on the web portal.');
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
    [clockIn, clockOut, isConfigured],
  );

  const loading = geoQuery.isLoading || todayQuery.isLoading;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Staff clock" subtitle="Clock in/out with GPS geofence" onBack={() => navigation.goBack()} />
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
                { label: 'Geofence', value: isConfigured ? `${geoQuery.data?.radius_meters ?? 0}m radius` : 'Not configured' },
                { label: 'Check in', value: todayQuery.data?.check_in_time ?? '—' },
                { label: 'Check out', value: todayQuery.data?.check_out_time ?? '—' },
              ]}
            />
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md }}>
              <Button label="Clock in" onPress={() => void perform('in')} loading={clockIn.isPending} disabled={busy} />
              <Button label="Clock out" variant="secondary" onPress={() => void perform('out')} loading={clockOut.isPending} disabled={busy} />
            </View>
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
