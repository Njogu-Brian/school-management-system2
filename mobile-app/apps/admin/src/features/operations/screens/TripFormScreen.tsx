import { useCan, useTransportRoute, useTripMutations, useVehicles } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'TripForm'>;

export const TripFormScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('operations.view');
  const tripId = route.params.tripId;
  const isEdit = tripId != null && tripId > 0;
  const { colors, palette, spacing, typography } = useTheme();
  const tripQuery = useTransportRoute(isEdit ? tripId : null, { enabled: isEdit && canView });
  const vehiclesQuery = useVehicles({ enabled: canView });
  const { create, update } = useTripMutations();

  const [name, setName] = useState('');
  const [direction, setDirection] = useState('');
  const [vehicleId, setVehicleId] = useState<number | null>(null);

  useEffect(() => {
    if (!tripQuery.data) return;
    setName(tripQuery.data.name);
    setDirection(tripQuery.data.description?.split(' · ')[0] ?? '');
    setVehicleId(tripQuery.data.vehicle_id ?? null);
  }, [tripQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const submit = async () => {
    if (!name.trim() || !vehicleId) {
      showError('Validation', 'Trip name and vehicle are required.');
      return;
    }
    const payload = {
      name: name.trim(),
      vehicle_id: vehicleId,
      direction: direction.trim() || undefined,
    };
    try {
      if (isEdit) {
        await update.mutateAsync({ id: tripId!, ...payload });
      } else {
        await create.mutateAsync(payload);
      }
      showSuccess('Saved', isEdit ? 'Trip updated.' : 'Trip created.', () => navigation.goBack());
    } catch (err) {
      showError('Failed', (err as Error).message);
    }
  };

  if (isEdit && tripQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title={isEdit ? 'Edit trip' : 'Add trip'} onBack={() => navigation.goBack()} />
      <TextField label="Trip name *" value={name} onChangeText={setName} />
      <TextField label="Direction" value={direction} onChangeText={setDirection} placeholder="e.g. Morning / Evening" />

      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.xs }}>Vehicle *</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: spacing.md }}>
        {(vehiclesQuery.data ?? []).map((v) => {
          const active = vehicleId === v.id;
          return (
            <Pressable
              key={v.id}
              onPress={() => setVehicleId(v.id)}
              style={{
                paddingHorizontal: 12,
                paddingVertical: 8,
                borderRadius: 8,
                borderWidth: 1,
                borderColor: active ? colors.primary : palette.border,
                backgroundColor: active ? `${colors.primary}18` : 'transparent',
                marginRight: 8,
              }}
            >
              <Text style={{ color: active ? colors.primary : palette.textPrimary, fontWeight: '600', fontSize: typography.body.fontSize }}>
                {v.vehicle_number}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>

      <Button
        label={isEdit ? 'Save changes' : 'Create trip'}
        onPress={() => void submit()}
        loading={create.isPending || update.isPending}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
