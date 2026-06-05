import { useCan, useVehicle, useVehicleMutations } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'VehicleForm'>;

export const VehicleFormScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan('operations.view');
  const vehicleId = route.params.vehicleId;
  const isEdit = vehicleId != null && vehicleId > 0;
  const { palette, spacing } = useTheme();
  const vehicleQuery = useVehicle(vehicleId ?? 0, { enabled: isEdit && canView });
  const { create, update } = useVehicleMutations();

  const [vehicleNumber, setVehicleNumber] = useState('');
  const [driverName, setDriverName] = useState('');
  const [make, setMake] = useState('');
  const [model, setModel] = useState('');
  const [type, setType] = useState('');
  const [capacity, setCapacity] = useState('');
  const [chassisNumber, setChassisNumber] = useState('');

  useEffect(() => {
    if (!vehicleQuery.data) return;
    const v = vehicleQuery.data;
    setVehicleNumber(v.vehicle_number);
    setDriverName(v.driver_name ?? '');
    setMake(v.make ?? '');
    setModel(v.model ?? '');
    setType(v.type ?? '');
    setCapacity(v.capacity != null ? String(v.capacity) : '');
    setChassisNumber(v.chassis_number ?? '');
  }, [vehicleQuery.data]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const submit = async () => {
    if (!vehicleNumber.trim()) {
      Alert.alert('Validation', 'Vehicle number is required.');
      return;
    }
    const payload = {
      vehicle_number: vehicleNumber.trim(),
      driver_name: driverName.trim() || undefined,
      make: make.trim() || undefined,
      model: model.trim() || undefined,
      type: type.trim() || undefined,
      capacity: capacity.trim() ? Number(capacity) : undefined,
      chassis_number: chassisNumber.trim() || undefined,
    };
    try {
      if (isEdit) {
        await update.mutateAsync({ id: vehicleId!, ...payload });
      } else {
        await create.mutateAsync(payload);
      }
      Alert.alert('Saved', isEdit ? 'Vehicle updated.' : 'Vehicle created.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (err) {
      Alert.alert('Failed', (err as Error).message);
    }
  };

  if (isEdit && vehicleQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <ActivityIndicator />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title={isEdit ? 'Edit vehicle' : 'Add vehicle'}
        onBack={() => navigation.goBack()}
      />
      <TextField label="Vehicle number *" value={vehicleNumber} onChangeText={setVehicleNumber} />
      <TextField label="Driver name" value={driverName} onChangeText={setDriverName} />
      <TextField label="Make" value={make} onChangeText={setMake} />
      <TextField label="Model" value={model} onChangeText={setModel} />
      <TextField label="Type" value={type} onChangeText={setType} />
      <TextField label="Capacity" value={capacity} onChangeText={setCapacity} keyboardType="numeric" />
      <TextField label="Chassis number" value={chassisNumber} onChangeText={setChassisNumber} />
      <Button
        label={isEdit ? 'Save changes' : 'Create vehicle'}
        onPress={() => void submit()}
        loading={create.isPending || update.isPending}
        style={{ marginTop: spacing.lg }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
