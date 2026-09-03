import { useCurrentUser, useParentWalletTopUp } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ScreenContainer,
  Soft3DIcon,
  SurfaceCard,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useState } from 'react';
import { Text } from 'react-native';
import { showError, showSuccess } from '../../../shared/utils/feedback';

export const ParentWalletTopUpScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography } = useTheme();
  const user = useCurrentUser();
  const topUp = useParentWalletTopUp();
  const [phone, setPhone] = useState(user?.phone ?? '');
  const [amount, setAmount] = useState('');

  const submit = async () => {
    const value = Number(amount);
    if (!phone.trim()) {
      showError('Phone required', 'Enter the M-Pesa number to prompt.');
      return;
    }
    if (!value || value < 1) {
      showError('Amount', 'Enter an amount of at least KES 1.');
      return;
    }
    try {
      await topUp.mutateAsync({ phone_number: phone.trim(), amount: value });
      showSuccess('STK sent', 'Complete the M-Pesa prompt on your phone.');
      navigation.goBack();
    } catch (err) {
      showError('Top-up failed', err instanceof Error ? err.message : 'Could not start STK.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Top up wallet" onBack={() => navigation.goBack()} />
      <SurfaceCard accent="success">
        <Soft3DIcon name="wallet-outline" glyph="wallet" size={48} />
        <Text style={{ color: palette.textSecondary, marginTop: spacing.sm, marginBottom: spacing.md, fontSize: typography.body.fontSize }}>
          Deposit via the school paybill STK. Due fees are paid first; any remainder stays in your wallet.
        </Text>
      <TextField
        label="M-Pesa phone"
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
        placeholder="07XX XXX XXX"
      />
      <TextField
        label="Amount (KES)"
        value={amount}
        onChangeText={setAmount}
        keyboardType="decimal-pad"
        placeholder="1000"
      />
      <Button label="Send STK prompt" loading={topUp.isPending} onPress={() => void submit()} />
      </SurfaceCard>
    </ScreenContainer>
  );
};
