import { useStaffGeofence, useStaffGeofenceUpdate } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import React, { useEffect, useState } from 'react';
import { Alert, ScrollView } from 'react-native';

export interface GeofenceSettingsScreenProps {
  onBack?: () => void;
}

export const GeofenceSettingsScreen: React.FC<GeofenceSettingsScreenProps> = ({ onBack }) => {
  const { spacing } = useTheme();
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
      Alert.alert('Saved', 'Geofence updated.');
    } catch (err) {
      Alert.alert('Error', err instanceof Error ? err.message : 'Could not save geofence.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        {onBack ? <AcademicScreenHeader title="Staff geofence" onBack={onBack} /> : null}
        <TextField label="Latitude" value={lat} onChangeText={setLat} keyboardType="decimal-pad" />
        <TextField label="Longitude" value={lng} onChangeText={setLng} keyboardType="decimal-pad" />
        <TextField label="Radius (meters)" value={radius} onChangeText={setRadius} keyboardType="number-pad" />
        <Button label="Save geofence" onPress={() => void save()} loading={updateMutation.isPending} style={{ marginTop: spacing.md }} />
      </ScrollView>
    </ScreenContainer>
  );
};
