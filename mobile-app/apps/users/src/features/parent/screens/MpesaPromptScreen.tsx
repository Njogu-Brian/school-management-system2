import { studentsApi, useStudentDetail, useStudentStats } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useEffect, useState } from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { formatKes } from '../utils/format';

export const MpesaPromptScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'MpesaPrompt'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const stats = useStudentStats(studentId, { enabled: studentId > 0 });

  const [phone, setPhone] = useState('');
  const [amount, setAmount] = useState(
    route.params.amount != null ? String(route.params.amount) : '',
  );
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (amount) return;
    const bal = stats.data?.fees_balance;
    if (typeof bal === 'number' && bal > 0) setAmount(String(bal));
  }, [stats.data?.fees_balance, amount]);

  useEffect(() => {
    const parent = detail.data?.parent;
    const fallback =
      parent?.fatherPhone || parent?.motherPhone || parent?.guardianPhone || detail.data?.phone || '';
    if (fallback && !phone) setPhone(fallback);
  }, [detail.data, phone]);

  const submit = async () => {
    const amt = Number(amount);
    if (!phone.trim()) {
      showError('Phone required', 'Enter the M-Pesa phone number.');
      return;
    }
    if (!Number.isFinite(amt) || amt <= 0) {
      showError('Invalid amount', 'Enter an amount greater than zero.');
      return;
    }
    setSubmitting(true);
    try {
      const res = await studentsApi.promptMpesa(studentId, {
        phone_number: phone.trim(),
        amount: amt,
      });
      if (!res.success) throw new Error(res.message || 'Failed to send M-Pesa prompt.');
      showSuccess('STK sent', res.data?.message ?? res.message ?? 'Check the phone for the M-Pesa prompt.');
      navigation.goBack();
    } catch (err) {
      showError('Prompt failed', err instanceof Error ? err.message : 'Could not send STK push.');
    } finally {
      setSubmitting(false);
    }
  };

  if (studentId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="M-Pesa" onBack={() => navigation.goBack()} />
        <EmptyState title="Missing student" message="Select a child first." icon="alert-circle-outline" />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="M-Pesa prompt"
        subtitle={detail.data?.fullName ?? `Student #${studentId}`}
        onBack={() => navigation.goBack()}
      />

      <View
        style={{
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderWidth: 1,
          borderRadius: radius.lg,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Balance</Text>
        <Text style={{ color: palette.textPrimary, fontSize: 22, fontWeight: '700', marginTop: 4 }}>
          {formatKes(stats.data?.fees_balance)}
        </Text>
      </View>

      <TextField
        label="M-Pesa phone"
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
        placeholder="0712345678"
      />
      <TextField
        label="Amount (KES)"
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
        placeholder="0"
      />

      <Button label="Send STK push" onPress={() => void submit()} loading={submitting} style={{ marginTop: spacing.md }} />
      <Button label="Cancel" variant="ghost" onPress={() => navigation.goBack()} style={{ marginTop: spacing.sm }} />
    </ScreenContainer>
  );
};
