import { staffApi, useUpdateStaff } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ScrollView } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'StaffEdit'>;

export const StaffEditScreen: React.FC<Props> = ({ route, navigation }) => {
  const { staffId } = route.params;
  const { spacing } = useTheme();
  const updateMutation = useUpdateStaff(staffId);
  const [idNumber, setIdNumber] = useState('');

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [workEmail, setWorkEmail] = useState('');
  const [personalEmail, setPersonalEmail] = useState('');
  const [address, setAddress] = useState('');

  useEffect(() => {
    void staffApi.getById(staffId).then((res) => {
      const staff = res.data;
      if (!staff) return;
      setFirstName(staff.first_name ?? '');
      setLastName(staff.last_name ?? '');
      setPhone(staff.phone_number ?? staff.phone ?? '');
      setWorkEmail(staff.work_email ?? '');
      setPersonalEmail(staff.personal_email ?? '');
      setAddress(staff.residential_address ?? '');
      setIdNumber(staff.id_number ?? '');
    });
  }, [staffId]);

  const save = async () => {
    try {
      await updateMutation.mutateAsync({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        phone_number: phone.trim(),
        work_email: workEmail.trim(),
        personal_email: personalEmail.trim() || null,
        residential_address: address.trim() || null,
        id_number: idNumber.trim() || 'N/A',
      });
      showSuccess('Saved', 'Staff profile updated.');
      navigation.goBack();
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Update failed.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Edit staff" onBack={() => navigation.goBack()} />
        <TextField label="First name" value={firstName} onChangeText={setFirstName} />
        <TextField label="Last name" value={lastName} onChangeText={setLastName} />
        <TextField label="Phone" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
        <TextField label="Work email" value={workEmail} onChangeText={setWorkEmail} autoCapitalize="none" />
        <TextField label="Personal email" value={personalEmail} onChangeText={setPersonalEmail} autoCapitalize="none" />
        <TextField label="Residential address" value={address} onChangeText={setAddress} />
        <Button label="Save changes" onPress={() => void save()} loading={updateMutation.isPending} style={{ marginTop: spacing.md }} />
      </ScrollView>
    </ScreenContainer>
  );
};
