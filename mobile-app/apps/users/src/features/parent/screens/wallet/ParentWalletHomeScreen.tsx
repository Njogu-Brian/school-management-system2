import {
  useParentWallet,
  useParentWalletPay,
  useCurrentUser,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../../shared/utils/feedback';
import { formatKes, formatShortDate } from '../../utils/format';

export const ParentWalletHomeScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<RouteProp<ParentStackParamList, 'WalletHome'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const walletQuery = useParentWallet();
  const pay = useParentWalletPay();
  const user = useCurrentUser();

  const [payAmount, setPayAmount] = useState('');
  const payInvoiceId = route.params?.payInvoiceId;
  const payStudentId = route.params?.studentId;

  useEffect(() => {
    if (payInvoiceId) {
      // Prefill handled when user taps pay
    }
  }, [payInvoiceId]);

  const wallet = walletQuery.data;

  const handlePayInvoice = async () => {
    if (!payInvoiceId) return;
    const amount = Number(payAmount);
    if (!amount || amount <= 0) {
      showError('Amount', 'Enter an amount to pay from your wallet.');
      return;
    }
    try {
      await pay.mutateAsync({ amount, invoice_id: payInvoiceId, student_id: payStudentId });
      showSuccess('Paid', 'Invoice payment applied from wallet.');
      setPayAmount('');
      void walletQuery.refetch();
    } catch (err) {
      showError('Payment failed', err instanceof Error ? err.message : 'Could not pay.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Family wallet"
        subtitle={user?.name ?? 'Shared balance for your children'}
        onBack={() => navigation.goBack()}
      />
      <View style={{ alignItems: 'center', marginBottom: spacing.md }}>
        <Soft3DIcon name="wallet-outline" glyph="wallet" tone="emerald" size={56} />
      </View>

      {walletQuery.isLoading ? (
        <SkeletonListRows count={4} />
      ) : walletQuery.isError ? (
        <EmptyState
          title="Could not load wallet"
          message={(walletQuery.error as Error)?.message}
          icon="wallet-outline"
          actionLabel="Retry"
          onAction={() => void walletQuery.refetch()}
        />
      ) : (
        <>
          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.lg,
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              Available balance
            </Text>
            <Text
              style={{
                color: palette.textPrimary,
                fontSize: typography.displayLarge.fontSize,
                fontWeight: '800',
                marginTop: spacing.xs,
              }}
            >
              {formatKes(wallet?.balance ?? 0)}
            </Text>
            <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
              Deposits go to due fees first; the rest stays for trips, swimming & activities.
            </Text>
          </View>

          <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md, flexWrap: 'wrap' }}>
            <Button label="Top up" onPress={() => navigation.navigate('WalletTopUp')} />
            <Button
              label="Saving plans"
              variant="secondary"
              onPress={() => navigation.navigate('WalletSavingPlans')}
            />
          </View>

          {payInvoiceId ? (
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
              <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
                Pay invoice #{payInvoiceId} from wallet
              </Text>
              <TextField
                label="Amount (KES)"
                value={payAmount}
                onChangeText={setPayAmount}
                keyboardType="decimal-pad"
                placeholder="0"
              />
              <Button
                label="Pay now"
                loading={pay.isPending}
                onPress={() => void handlePayInvoice()}
                style={{ marginTop: spacing.sm }}
              />
            </View>
          ) : null}

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
            Recent activity
          </Text>
          {(wallet?.ledger ?? []).length === 0 ? (
            <EmptyState title="No activity yet" message="Top up to get started." icon="receipt-outline" />
          ) : (
            (wallet?.ledger ?? []).map((row) => (
              <View
                key={row.id}
                style={{
                  paddingVertical: spacing.sm,
                  borderBottomWidth: 1,
                  borderBottomColor: palette.border,
                }}
              >
                <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '600', textTransform: 'capitalize' }}>
                    {row.type.replace('_', ' ')}
                  </Text>
                  <Text
                    style={{
                      color: row.amount >= 0 ? palette.textPrimary : palette.textSecondary,
                      fontWeight: '700',
                    }}
                  >
                    {row.amount >= 0 ? '+' : ''}
                    {formatKes(row.amount)}
                  </Text>
                </View>
                <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                  Balance {formatKes(row.balance_after)}
                  {row.created_at ? ` · ${formatShortDate(row.created_at)}` : ''}
                </Text>
              </View>
            ))
          )}
        </>
      )}
    </ScreenContainer>
  );
};
