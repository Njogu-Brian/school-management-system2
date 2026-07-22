import { useLiveFleet } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { Linking, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'LiveFleet'>;

export const LiveFleetScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, radius } = useTheme();
  const fleetQuery = useLiveFleet({ refetchInterval: 5_000 });

  const openMaps = async (lat: number, lng: number) => {
    const url = `https://www.google.com/maps?q=${lat},${lng}`;
    const supported = await Linking.canOpenURL(url);
    if (!supported) {
      showError('Cannot open maps', 'No maps app is available on this device.');
      return;
    }
    await Linking.openURL(url);
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Live fleet"
        subtitle="Buses sharing location now"
        onBack={() => navigation.goBack()}
      />

      {fleetQuery.isLoading ? (
        <SkeletonListRows count={4} />
      ) : fleetQuery.isError ? (
        <EmptyState
          title="Could not load fleet"
          message={fleetQuery.error instanceof Error ? fleetQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void fleetQuery.refetch()}
        />
      ) : (fleetQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No live buses"
          message="Active trips will appear here when drivers start sharing location."
          icon="bus-outline"
        />
      ) : (
        (fleetQuery.data ?? []).map((bus) => {
          const hasCoords = bus.latitude != null && bus.longitude != null;
          return (
            <View
              key={`${bus.trip_id ?? bus.run_id ?? bus.vehicle_registration}`}
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700', flex: 1 }}>
                  {bus.trip_name ?? bus.vehicle_registration ?? 'Active trip'}
                </Text>
                <StatusBadge label={bus.live ? 'live' : 'offline'} tone={bus.live ? 'success' : 'warning'} compact />
              </View>
              <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                {[bus.vehicle_registration, bus.driver_name, bus.direction].filter(Boolean).join(' · ')}
              </Text>
              {bus.age_seconds != null ? (
                <Text style={{ color: palette.textMuted, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                  Updated {bus.age_seconds}s ago
                  {bus.student_count != null ? ` · ${bus.student_count} students` : ''}
                </Text>
              ) : null}
              {hasCoords ? (
                <Button
                  label="Open in Maps"
                  variant="secondary"
                  onPress={() => void openMaps(bus.latitude!, bus.longitude!)}
                  style={{ marginTop: spacing.sm }}
                />
              ) : null}
            </View>
          );
        })
      )}
    </ScreenContainer>
  );
};
