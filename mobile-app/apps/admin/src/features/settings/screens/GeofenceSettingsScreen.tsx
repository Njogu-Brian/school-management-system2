import { useStaffGeofence, useStaffGeofenceUpdate } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

export interface GeofenceSettingsScreenProps {
  onBack?: () => void;
}

export const GeofenceSettingsScreen: React.FC<GeofenceSettingsScreenProps> = ({ onBack }) => {
  const { colors, spacing } = useTheme();
  const geofenceQuery = useStaffGeofence();
  const updateMutation = useStaffGeofenceUpdate();
  const [lat, setLat] = useState('');
  const [lng, setLng] = useState('');
  const [radius, setRadius] = useState('');

  useEffect(() => {
    const g = geofenceQuery.data;
    if (!g) return;
    setLat(String(g.latitude ?? ''));
    setLng(String(g.longitude ?? ''));
    setRadius(String(g.radius_meters ?? ''));
  }, [geofenceQuery.data]);

  const save = async () => {
    try {
      await updateMutation.mutateAsync({
        latitude: parseFloat(lat),
        longitude: parseFloat(lng),
        radius_meters: parseInt(radius, 10),
      });
      showSuccess('Saved', 'Geofence updated.');
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not save geofence.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        {onBack ? <AcademicScreenHeader title="Staff geofence" onBack={onBack} /> : null}

        {geofenceQuery.isLoading ? (
          <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
            <ActivityIndicator color={colors.primary} />
          </View>
        ) : geofenceQuery.isError ? (
          <EmptyState
            title="Could not load geofence"
            message={(geofenceQuery.error as Error)?.message ?? 'Try again in a moment.'}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void geofenceQuery.refetch()}
          />
        ) : (
          <>
            <TextField
              label="Latitude"
              value={lat}
              onChangeText={setLat}
              keyboardType="decimal-pad"
            />
            <View style={{ height: spacing.sm }} />
            <TextField
              label="Longitude"
              value={lng}
              onChangeText={setLng}
              keyboardType="decimal-pad"
            />
            <View style={{ height: spacing.sm }} />
            <TextField
              label="Radius (meters)"
              value={radius}
              onChangeText={setRadius}
              keyboardType="number-pad"
            />
            <Button
              label="Save geofence"
              onPress={() => void save()}
              loading={updateMutation.isPending}
              style={{ marginTop: spacing.md, minHeight: 48 }}
            />
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
