import { useCreateLeaveRequest, useInfiniteStaffList, useLeaveTypes } from '@erp/core';
import { AcademicScreenHeader, Button, FilterChip, FilterChipRow, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Alert, ScrollView } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'LeaveApply'>;

export const LeaveApplyScreen: React.FC<Props> = ({ navigation, route }) => {
  const { spacing } = useTheme();
  const preselectedStaffId = route.params?.staffId;
  const typesQuery = useLeaveTypes();
  const staffQuery = useInfiniteStaffList({
    departmentId: null,
    staffCategoryId: null,
    employmentStatus: 'all',
    gender: 'all',
    role: null,
    perPage: 25,
  });
  const staffItems = staffQuery.data?.pages.flatMap((p) => p.items) ?? [];
  const createMutation = useCreateLeaveRequest();

  const [staffId, setStaffId] = useState<number | null>(preselectedStaffId ?? null);
  const [leaveTypeId, setLeaveTypeId] = useState<number | null>(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');

  const submit = async () => {
    if (!leaveTypeId || !startDate || !endDate) {
      Alert.alert('Missing fields', 'Leave type and dates are required.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        staff_id: staffId ?? undefined,
        leave_type_id: leaveTypeId,
        start_date: startDate,
        end_date: endDate,
        reason: reason.trim() || undefined,
      });
      Alert.alert('Submitted', 'Leave request sent for approval.');
      navigation.goBack();
    } catch (err) {
      Alert.alert('Error', err instanceof Error ? err.message : 'Could not submit leave.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Apply for leave" onBack={() => navigation.goBack()} />
        <FilterChipRow label="Staff member">
          {(staffItems).slice(0, 20).map((s) => (
            <FilterChip
              key={s.id}
              label={s.fullName}
              active={staffId === s.id}
              onPress={() => setStaffId(s.id)}
            />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Leave type">
          {(typesQuery.data ?? []).map((t) => (
            <FilterChip
              key={t.id}
              label={t.name}
              active={leaveTypeId === t.id}
              onPress={() => setLeaveTypeId(t.id)}
            />
          ))}
        </FilterChipRow>
        <TextField label="Start date (YYYY-MM-DD)" value={startDate} onChangeText={setStartDate} />
        <TextField label="End date (YYYY-MM-DD)" value={endDate} onChangeText={setEndDate} />
        <TextField label="Reason" value={reason} onChangeText={setReason} />
        <Button label="Submit request" onPress={() => void submit()} loading={createMutation.isPending} style={{ marginTop: spacing.md }} />
      </ScrollView>
    </ScreenContainer>
  );
};
