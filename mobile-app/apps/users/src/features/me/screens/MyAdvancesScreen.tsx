import { useCreateStaffAdvance, useCurrentUser, useStaffAdvancesList } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useState } from 'react';
import { FlatList, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

export const MyAdvancesScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const listQuery = useStaffAdvancesList();
  const createMutation = useCreateStaffAdvance();

  const [amount, setAmount] = useState('');
  const [purpose, setPurpose] = useState('');
  const [advanceDate, setAdvanceDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [showForm, setShowForm] = useState(false);

  const submit = async () => {
    const value = Number(amount);
    if (!Number.isFinite(value) || value <= 0) {
      showError('Invalid amount', 'Enter a positive amount.');
      return;
    }
    if (!purpose.trim()) {
      showError('Purpose required', 'Tell payroll why you need this advance.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        staff_id: user?.staffId ?? undefined,
        amount: value,
        requested_amount: value,
        purpose: purpose.trim(),
        advance_date: advanceDate,
        repayment_method: 'payroll',
      });
      showSuccess('Submitted', 'Advance request sent for approval.');
      setAmount('');
      setPurpose('');
      setShowForm(false);
    } catch (err) {
      showError('Request failed', err instanceof Error ? err.message : 'Could not submit advance.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="Salary advances" onBack={() => navigation.goBack()} />
        <Button
          label={showForm ? 'Hide form' : 'Request advance'}
          variant={showForm ? 'secondary' : 'primary'}
          onPress={() => setShowForm((v) => !v)}
          style={{ marginBottom: spacing.sm }}
        />
        {showForm ? (
          <View
            style={{
              backgroundColor: palette.surface,
              borderWidth: 1,
              borderColor: palette.border,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.md,
              gap: spacing.sm,
            }}
          >
            <TextField label="Amount (KES)" value={amount} onChangeText={setAmount} keyboardType="decimal-pad" />
            <TextField label="Advance date (YYYY-MM-DD)" value={advanceDate} onChangeText={setAdvanceDate} />
            <TextField label="Purpose" value={purpose} onChangeText={setPurpose} multiline />
            <Button label="Submit" onPress={() => void submit()} loading={createMutation.isPending} />
          </View>
        ) : null}
      </View>

      {listQuery.isLoading ? (
        <SkeletonListRows count={5} />
      ) : (listQuery.data ?? []).length === 0 ? (
        <EmptyState
          title="No advances"
          message="Your salary advance requests will appear here."
          icon="cash-outline"
        />
      ) : (
        <FlatList
          data={listQuery.data ?? []}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          renderItem={({ item }) => (
            <View
              style={{
                backgroundColor: palette.surface,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  KES {Number(item.requested_amount || item.amount).toLocaleString()}
                </Text>
                <StatusBadge label={item.status} tone={item.status === 'approved' ? 'success' : 'info'} />
              </View>
              <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
                {item.purpose || item.description || 'Advance'}
              </Text>
              <Text style={{ color: palette.textMuted, marginTop: 4, fontSize: typography.caption.fontSize }}>
                {[item.advance_date, item.balance != null ? `Balance ${item.balance}` : null]
                  .filter(Boolean)
                  .join(' · ')}
              </Text>
            </View>
          )}
        />
      )}
    </ScreenContainer>
  );
};
