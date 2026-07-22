import { useCreateLeaveRequest, useCurrentUser, useLeaveTypes } from '@erp/core';
import { AcademicScreenHeader, Button, FilterChip, FilterChipRow, ScreenContainer, TextField, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useState } from 'react';
import { ScrollView } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

export const LeaveApplyScreen: React.FC = () => {
  const navigation = useNavigation();
  const { spacing } = useTheme();
  const user = useCurrentUser();
  const typesQuery = useLeaveTypes();
  const createMutation = useCreateLeaveRequest();

  const [leaveTypeId, setLeaveTypeId] = useState<number | null>(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');

  const submit = async () => {
    if (!leaveTypeId || !startDate || !endDate) {
      showError('Missing fields', 'Leave type and dates are required.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        staff_id: user?.staffId ?? undefined,
        leave_type_id: leaveTypeId,
        start_date: startDate,
        end_date: endDate,
        reason: reason.trim() || undefined,
      });
      showSuccess('Submitted', 'Leave request sent for approval.');
      navigation.goBack();
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not submit leave.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Apply for leave" onBack={() => navigation.goBack()} />
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
        <Button
          label="Submit request"
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.md }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
