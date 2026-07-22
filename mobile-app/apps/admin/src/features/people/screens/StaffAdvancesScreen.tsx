import {
  useApproveStaffAdvance,
  useCreateStaffAdvance,
  useInfiniteStaffList,
  useRejectStaffAdvance,
  useStaffAdvancesList,
  type StaffAdvanceRecord,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<PeopleStackParamList, 'StaffAdvances'>;

export const StaffAdvancesScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const listQuery = useStaffAdvancesList({ status: 'pending' });
  const createMutation = useCreateStaffAdvance();
  const approveMutation = useApproveStaffAdvance();
  const rejectMutation = useRejectStaffAdvance();
  const staffQuery = useInfiniteStaffList({
    departmentId: null,
    staffCategoryId: null,
    employmentStatus: 'all',
    gender: 'all',
    role: null,
    perPage: 25,
  });
  const staffItems = staffQuery.data?.pages.flatMap((p) => p.items) ?? [];

  const [mode, setMode] = useState<'list' | 'create'>('list');
  const [staffId, setStaffId] = useState<number | null>(null);
  const [amount, setAmount] = useState('');
  const [advanceDate, setAdvanceDate] = useState(new Date().toISOString().slice(0, 10));
  const [purpose, setPurpose] = useState('');
  const [repayment, setRepayment] = useState<'lump_sum' | 'installments' | 'monthly_deduction'>(
    'lump_sum',
  );
  const [installments, setInstallments] = useState('3');
  const [monthlyAmount, setMonthlyAmount] = useState('');

  const [approveItem, setApproveItem] = useState<StaffAdvanceRecord | null>(null);
  const [issuedAmount, setIssuedAmount] = useState('');
  const [rejectItem, setRejectItem] = useState<StaffAdvanceRecord | null>(null);
  const [rejectReason, setRejectReason] = useState('');

  const createAdvance = async () => {
    if (!staffId || !amount || !advanceDate) {
      showError('Missing fields', 'Staff, amount, and date are required.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        staff_id: staffId,
        amount: Number(amount),
        requested_amount: Number(amount),
        advance_date: advanceDate,
        purpose: purpose.trim() || undefined,
        repayment_method: repayment,
        installment_count: repayment === 'installments' ? Number(installments) || 1 : undefined,
        monthly_deduction_amount:
          repayment === 'monthly_deduction' ? Number(monthlyAmount) || undefined : undefined,
      });
      showSuccess('Created', 'Advance request created for staff.');
      setMode('list');
      setAmount('');
      setPurpose('');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Could not create advance.');
    }
  };

  const submitApprove = async () => {
    if (!approveItem) return;
    const issued = Number(issuedAmount);
    if (!issued || issued <= 0) {
      showError('Invalid amount', 'Enter the amount to issue.');
      return;
    }
    try {
      await approveMutation.mutateAsync({ id: approveItem.id, amount: issued });
      showSuccess('Approved', `Issued KES ${issued}.`);
      setApproveItem(null);
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Approve failed.');
    }
  };

  const submitReject = async () => {
    if (!rejectItem || rejectReason.trim().length < 3) {
      showError('Reason required', 'Enter a rejection reason.');
      return;
    }
    try {
      await rejectMutation.mutateAsync({ id: rejectItem.id, reason: rejectReason.trim() });
      showSuccess('Rejected', 'Advance cancelled.');
      setRejectItem(null);
      setRejectReason('');
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Reject failed.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Staff advances"
          subtitle="Create, approve, and issue payroll advances"
          onBack={() => navigation.goBack()}
        />
        <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
          <Button
            label="Pending"
            variant={mode === 'list' ? 'primary' : 'secondary'}
            onPress={() => setMode('list')}
          />
          <Button
            label="Create"
            variant={mode === 'create' ? 'primary' : 'secondary'}
            onPress={() => setMode('create')}
          />
        </View>

        {mode === 'create' ? (
          <View>
            <FilterChipRow label="Staff member">
              {staffItems.slice(0, 20).map((s) => (
                <FilterChip
                  key={s.id}
                  label={s.fullName}
                  active={staffId === s.id}
                  onPress={() => setStaffId(s.id)}
                />
              ))}
            </FilterChipRow>
            <TextField label="Amount (KES)" value={amount} onChangeText={setAmount} keyboardType="decimal-pad" />
            <TextField label="Advance date (YYYY-MM-DD)" value={advanceDate} onChangeText={setAdvanceDate} />
            <TextField label="Purpose" value={purpose} onChangeText={setPurpose} />
            <FilterChipRow label="Repayment (admin plans)">
              {(
                [
                  ['lump_sum', 'Lump sum'],
                  ['installments', 'Installments'],
                  ['monthly_deduction', 'Monthly'],
                ] as const
              ).map(([key, label]) => (
                <FilterChip
                  key={key}
                  label={label}
                  active={repayment === key}
                  onPress={() => setRepayment(key)}
                />
              ))}
            </FilterChipRow>
            {repayment === 'installments' ? (
              <TextField
                label="Installment count"
                value={installments}
                onChangeText={setInstallments}
                keyboardType="number-pad"
              />
            ) : null}
            {repayment === 'monthly_deduction' ? (
              <TextField
                label="Monthly deduction (KES)"
                value={monthlyAmount}
                onChangeText={setMonthlyAmount}
                keyboardType="decimal-pad"
              />
            ) : null}
            <Button
              label="Create advance"
              onPress={() => void createAdvance()}
              loading={createMutation.isPending}
              style={{ marginTop: spacing.md }}
            />
          </View>
        ) : (
          <>
            {listQuery.isLoading ? <ActivityIndicator color={colors.primary} /> : null}
            <FlatList
              data={listQuery.data ?? []}
              keyExtractor={(item) => String(item.id)}
              contentContainerStyle={{ paddingBottom: spacing.xl }}
              renderItem={({ item }) => {
                const approving = approveItem?.id === item.id;
                const rejecting = rejectItem?.id === item.id;
                return (
                  <View
                    style={[
                      styles.row,
                      {
                        borderColor: palette.borderSubtle,
                        backgroundColor: palette.surfaceRaised,
                        borderRadius: radius.card,
                        padding: spacing.md,
                        marginBottom: spacing.sm,
                      },
                    ]}
                  >
                    <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                      {item.staff_name ?? `Staff #${item.staff_id}`}
                    </Text>
                    <Text style={{ color: palette.textSecondary, marginTop: 2 }}>
                      Requested KES {item.requested_amount} · Status {item.status}
                    </Text>
                    {approving ? (
                      <View style={{ marginTop: spacing.sm }}>
                        <TextField
                          label="Issued amount (KES)"
                          value={issuedAmount}
                          onChangeText={setIssuedAmount}
                          keyboardType="decimal-pad"
                        />
                        <Text
                          style={{
                            color: palette.textMuted,
                            fontSize: typography.caption.fontSize,
                            marginBottom: spacing.sm,
                          }}
                        >
                          You can approve less than requested (e.g. issue 3000 of 5000).
                        </Text>
                        <View style={{ flexDirection: 'row', gap: spacing.sm }}>
                          <View style={{ flex: 1 }}>
                            <Button
                              label="Confirm approve"
                              onPress={() => void submitApprove()}
                              loading={approveMutation.isPending}
                            />
                          </View>
                          <Pressable onPress={() => setApproveItem(null)} style={{ justifyContent: 'center' }}>
                            <Text style={{ color: palette.textSecondary }}>Cancel</Text>
                          </Pressable>
                        </View>
                      </View>
                    ) : rejecting ? (
                      <View style={{ marginTop: spacing.sm }}>
                        <TextField
                          label="Rejection reason"
                          value={rejectReason}
                          onChangeText={setRejectReason}
                          multiline
                        />
                        <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm }}>
                          <View style={{ flex: 1 }}>
                            <Button
                              label="Confirm reject"
                              variant="destructive"
                              onPress={() => void submitReject()}
                              loading={rejectMutation.isPending}
                            />
                          </View>
                          <Pressable
                            onPress={() => {
                              setRejectItem(null);
                              setRejectReason('');
                            }}
                            style={{ justifyContent: 'center' }}
                          >
                            <Text style={{ color: palette.textSecondary }}>Cancel</Text>
                          </Pressable>
                        </View>
                      </View>
                    ) : (
                      <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm }}>
                        <Pressable
                          onPress={() => {
                            setIssuedAmount(String(item.requested_amount ?? item.amount));
                            setApproveItem(item);
                          }}
                          style={[
                            styles.btn,
                            { backgroundColor: colors.success, borderRadius: radius.sm, flex: 1 },
                          ]}
                        >
                          <Text style={{ color: '#fff', fontWeight: '700', textAlign: 'center' }}>
                            Approve
                          </Text>
                        </Pressable>
                        <Pressable
                          onPress={() => setRejectItem(item)}
                          style={[
                            styles.btn,
                            { backgroundColor: colors.error, borderRadius: radius.sm, flex: 1 },
                          ]}
                        >
                          <Text style={{ color: '#fff', fontWeight: '700', textAlign: 'center' }}>
                            Reject
                          </Text>
                        </Pressable>
                      </View>
                    )}
                  </View>
                );
              }}
              ListEmptyComponent={
                !listQuery.isLoading ? (
                  <ListEmptyState entityName="pending advances" icon="cash-outline" />
                ) : null
              }
            />
          </>
        )}
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  btn: { paddingVertical: 10, paddingHorizontal: 12 },
});
