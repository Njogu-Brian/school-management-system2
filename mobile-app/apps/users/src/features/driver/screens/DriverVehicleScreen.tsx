import { useDriverVehicle } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, SkeletonListRows, Soft3DIcon, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';

export const DriverVehicleScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography, radius } = useTheme();
  const vehicleQuery = useDriverVehicle();

  const rows = vehicleQuery.data
    ? [
        { label: 'Registration', value: vehicleQuery.data.vehicle_number },
        { label: 'Make / model', value: [vehicleQuery.data.make, vehicleQuery.data.model].filter(Boolean).join(' ') || '—' },
        { label: 'Type', value: vehicleQuery.data.type ?? '—' },
        { label: 'Capacity', value: vehicleQuery.data.capacity != null ? `${vehicleQuery.data.capacity} seats` : '—' },
        { label: 'Chassis number', value: vehicleQuery.data.chassis_number ?? '—' },
        { label: 'Insurance expiry', value: vehicleQuery.data.insurance_expiry ?? '—' },
        { label: 'Inspection expiry', value: vehicleQuery.data.inspection_expiry ?? '—' },
        { label: 'Status', value: vehicleQuery.data.status ?? '—' },
      ]
    : [];

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="My vehicle" subtitle="Assigned bus details" onBack={() => navigation.goBack()} />

      {vehicleQuery.isLoading ? (
        <SkeletonListRows count={5} />
      ) : vehicleQuery.isError || !vehicleQuery.data ? (
        <EmptyState
          title="No vehicle assigned"
          message={
            vehicleQuery.error instanceof Error
              ? vehicleQuery.error.message
              : 'You are not currently assigned to a vehicle.'
          }
          icon="bus-outline"
          actionLabel="Retry"
          onAction={() => void vehicleQuery.refetch()}
        />
      ) : (
        <View
          style={{
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
          }}
        >
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.md }}>
            <Soft3DIcon name="bus-outline" tone="cyan" size={48} />
            <View>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.headline.fontSize }}>
                {vehicleQuery.data.vehicle_number}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {vehicleQuery.data.driver_name ?? 'Driver'}
              </Text>
            </View>
          </View>
          {rows.map((row) => (
            <View
              key={row.label}
              style={{
                flexDirection: 'row',
                justifyContent: 'space-between',
                paddingVertical: spacing.xs,
                borderTopWidth: 1,
                borderTopColor: palette.border,
                paddingTop: spacing.sm,
                marginTop: spacing.sm,
              }}
            >
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{row.label}</Text>
              <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
                {row.value}
              </Text>
            </View>
          ))}
        </View>
      )}
    </ScreenContainer>
  );
};
