import {
  useAssignLeaveType,
  useCreateLeaveType,
  useInfiniteStaffList,
  useLeaveTypesAdmin,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ScrollView, Switch, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'LeaveTypes'>;

export const LeaveTypesScreen: React.FC<Props> = ({ navigation }) => {
  const { spacing, palette, typography } = useTheme();
  const typesQuery = useLeaveTypesAdmin({ includeInactive: true });
  const createMutation = useCreateLeaveType();
  const assignMutation = useAssignLeaveType();
  const staffQuery = useInfiniteStaffList({
    departmentId: null,
    staffCategoryId: null,
    employmentStatus: 'all',
    gender: 'all',
    role: null,
    perPage: 25,
  });
  const staffItems = staffQuery.data?.pages.flatMap((p) => p.items) ?? [];

  const [name, setName] = useState('');
  const [code, setCode] = useState('');
  const [maxDays, setMaxDays] = useState('21');
  const [isPaid, setIsPaid] = useState(true);
  const [assignStaffId, setAssignStaffId] = useState<number | null>(null);
  const [assignTypeId, setAssignTypeId] = useState<number | null>(null);
  const [entitlement, setEntitlement] = useState('21');

  const createType = async () => {
    if (!name.trim() || !code.trim()) {
      showError('Missing fields', 'Name and code are required.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        name: name.trim(),
        code: code.trim().toUpperCase(),
        max_days: Number(maxDays) || 0,
        is_paid: isPaid,
        requires_approval: true,
      });
      showSuccess('Created', 'Leave type created (paid/unpaid linked to HR balances).');
      setName('');
      setCode('');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Could not create leave type.');
    }
  };

  const assignType = async () => {
    if (!assignStaffId || !assignTypeId) {
      showError('Missing fields', 'Select staff and leave type.');
      return;
    }
    try {
      await assignMutation.mutateAsync({
        staff_id: assignStaffId,
        leave_type_id: assignTypeId,
        entitlement_days: Number(entitlement) || 0,
      });
      showSuccess('Assigned', 'Leave type assigned to staff.');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Could not assign leave type.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing['3xl'] }}>
        <AcademicScreenHeader
          title="Leave types"
          subtitle="Create paid/unpaid types and assign to staff"
          onBack={() => navigation.goBack()}
        />

        <Text style={{ fontWeight: '700', color: palette.textMain, marginBottom: spacing.sm }}>
          Existing types
        </Text>
        {(typesQuery.data ?? []).map((t) => (
          <Text
            key={t.id}
            style={{ color: palette.textSecondary, marginBottom: 4, fontSize: typography.caption.fontSize }}
          >
            {t.name} ({t.code}) · {t.is_paid ? 'Paid' : 'Unpaid'} · {t.max_days ?? 0} days
            {!t.is_active ? ' · inactive' : ''}
          </Text>
        ))}

        <Text
          style={{
            fontWeight: '700',
            color: palette.textMain,
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          }}
        >
          Create leave type
        </Text>
        <TextField label="Name" value={name} onChangeText={setName} />
        <TextField label="Code" value={code} onChangeText={setCode} />
        <TextField label="Max days" value={maxDays} onChangeText={setMaxDays} keyboardType="number-pad" />
        <View style={{ flexDirection: 'row', alignItems: 'center', marginVertical: spacing.sm }}>
          <Text style={{ flex: 1, color: palette.textMain, fontWeight: '600' }}>Paid leave</Text>
          <Switch value={isPaid} onValueChange={setIsPaid} />
        </View>
        <Button label="Create leave type" onPress={() => void createType()} loading={createMutation.isPending} />

        <Text
          style={{
            fontWeight: '700',
            color: palette.textMain,
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          }}
        >
          Assign type to staff
        </Text>
        <FilterChipRow label="Staff">
          {staffItems.slice(0, 20).map((s) => (
            <FilterChip
              key={s.id}
              label={s.fullName}
              active={assignStaffId === s.id}
              onPress={() => setAssignStaffId(s.id)}
            />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Leave type">
          {(typesQuery.data ?? []).filter((t) => t.is_active !== false).map((t) => (
            <FilterChip
              key={t.id}
              label={`${t.name}${t.is_paid ? '' : ' (unpaid)'}`}
              active={assignTypeId === t.id}
              onPress={() => setAssignTypeId(t.id)}
            />
          ))}
        </FilterChipRow>
        <TextField
          label="Entitlement days"
          value={entitlement}
          onChangeText={setEntitlement}
          keyboardType="number-pad"
        />
        <Button label="Assign to staff" onPress={() => void assignType()} loading={assignMutation.isPending} />
      </ScrollView>
    </ScreenContainer>
  );
};
