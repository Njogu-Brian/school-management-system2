import { useLiveFleet } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TransportMapView,
  useTheme,
  type MapPin,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'LiveFleet'>;

export const LiveFleetScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, radius } = useTheme();
  const fleetQuery = useLiveFleet({ refetchInterval: 5_000 });
  const [selectedKey, setSelectedKey] = useState<string | null>(null);

  const buses = fleetQuery.data ?? [];

  const mapPins: MapPin[] = useMemo(() => {
    return buses
      .filter((bus) => bus.latitude != null && bus.longitude != null)
      .map((bus) => {
        const key = `${bus.trip_id ?? bus.run_id ?? bus.vehicle_registration}`;
        return {
          id: key,
          latitude: bus.latitude as number,
          longitude: bus.longitude as number,
          title: bus.trip_name ?? bus.vehicle_registration ?? 'Bus',
          subtitle: [bus.driver_name, bus.direction].filter(Boolean).join(' · ') || undefined,
          tone: selectedKey === key ? 'selected' : 'bus',
        } as MapPin;
      });
  }, [buses, selectedKey]);

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
      ) : buses.length === 0 ? (
        <EmptyState
          title="No live buses"
          message="Active trips will appear here when drivers start sharing location."
          icon="bus-outline"
        />
      ) : (
        <>
          <TransportMapView
            pins={mapPins}
            height={280}
            onPinPress={(pin) => setSelectedKey(pin.id)}
          />

          <View style={{ height: spacing.md }} />

          {buses.map((bus) => {
            const key = `${bus.trip_id ?? bus.run_id ?? bus.vehicle_registration}`;
            const selected = selectedKey === key;
            const age = bus.age_seconds ?? bus.freshness_seconds;
            const live = bus.live ?? (bus.latitude != null && bus.longitude != null);
            return (
              <Pressable
                key={key}
                onPress={() => setSelectedKey(key)}
                style={{
                  backgroundColor: palette.surface,
                  borderColor: selected ? palette.primary : palette.border,
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
                  <StatusBadge label={live ? 'live' : 'offline'} tone={live ? 'success' : 'warning'} compact />
                </View>
                <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                  {[bus.vehicle_registration, bus.driver_name, bus.direction].filter(Boolean).join(' · ')}
                </Text>
                {age != null ? (
                  <Text style={{ color: palette.textMuted, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
                    Updated {age}s ago
                    {bus.student_count != null ? ` · ${bus.student_count} students` : ''}
                  </Text>
                ) : null}
              </Pressable>
            );
          })}
        </>
      )}
    </ScreenContainer>
  );
};
