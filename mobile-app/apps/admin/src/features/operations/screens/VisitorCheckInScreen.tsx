import { useCan, useCheckInVisitor } from '@erp/core';
import { AcademicScreenHeader, Button, EmptyState, ScreenContainer, TextField, useTheme, useToast } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { StyleSheet } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'VisitorCheckIn'>;

export const VisitorCheckInScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { spacing } = useTheme();
  const { showToast } = useToast();
  const checkIn = useCheckInVisitor();

  const [visitorName, setVisitorName] = useState('');
  const [phone, setPhone] = useState('');
  const [idNumber, setIdNumber] = useState('');
  const [organization, setOrganization] = useState('');
  const [purpose, setPurpose] = useState('');
  const [hostName, setHostName] = useState('');
  const [notes, setNotes] = useState('');

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState title="Access denied" message="You need operations.view permission." icon="lock-closed-outline" />
      </ScreenContainer>
    );
  }

  const payload = {
    visitor_name: visitorName.trim(),
    phone: phone.trim() || undefined,
    id_number: idNumber.trim() || undefined,
    organization: organization.trim() || undefined,
    purpose: purpose.trim() || undefined,
    host_name: hostName.trim() || undefined,
    notes: notes.trim() || undefined,
  };

  const submit = async () => {
    if (!visitorName.trim()) {
      showError('Validation', 'Visitor name is required.');
      return;
    }
    try {
      const res = await checkIn.mutateAsync(payload);
      const id = res.data?.id;
      showToast({ message: 'Visitor checked in successfully', tone: 'success' });
      if (id) navigation.replace('VisitorDetail', { visitorId: id });
      else navigation.goBack();
    } catch (err) {
      showError('Check-in failed', (err as Error).message);
    }
  };

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Visitor check-in" onBack={() => navigation.goBack()} />
      <TextField label="Full name *" value={visitorName} onChangeText={setVisitorName} />
      <TextField label="Phone" value={phone} onChangeText={setPhone} />
      <TextField label="National ID" value={idNumber} onChangeText={setIdNumber} />
      <TextField label="Organization" value={organization} onChangeText={setOrganization} />
      <TextField label="Purpose" value={purpose} onChangeText={setPurpose} />
      <TextField label="Host staff member" value={hostName} onChangeText={setHostName} />
      <TextField label="Notes" value={notes} onChangeText={setNotes} multiline />
      <Button
        label="Save & check in"
        onPress={() => void submit()}
        loading={checkIn.isPending}
        disabled={!visitorName.trim() || checkIn.isPending}
        style={{ marginTop: spacing.lg }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
