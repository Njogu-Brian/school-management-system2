import { useCurrentUser, useParentFamilyFees, useParentFamilyFeesPrompt } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  SurfaceCard,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { formatKes, formatShortDate } from '../utils/format';

export const FamilyPaymentScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography, colors } = useTheme();
  const user = useCurrentUser();
  const fees = useParentFamilyFees();
  const prompt = useParentFamilyFeesPrompt();

  const family = fees.data?.family;
  const children = fees.data?.children ?? [];
  const [mode, setMode] = useState<'full' | 'custom'>('full');
  const [amount, setAmount] = useState('');
  const [phone, setPhone] = useState('');
  const [expandedChild, setExpandedChild] = useState<number | null>(null);
  const [expandedInvoice, setExpandedInvoice] = useState<number | null>(null);

  useEffect(() => {
    if (family && mode === 'full') {
      setAmount(family.outstanding > 0 ? String(family.outstanding) : '');
    }
  }, [family, mode]);

  useEffect(() => {
    const profile = user.data as { phone?: string; mpesa_phone?: string } | undefined;
    const fallback = profile?.mpesa_phone || profile?.phone || '';
    if (fallback && !phone) setPhone(fallback);
  }, [user.data, phone]);

  const payAmount = useMemo(() => {
    const n = Number(amount);
    return Number.isFinite(n) ? n : 0;
  }, [amount]);

  const submit = async () => {
    if (!phone.trim()) {
      showError('Phone required', 'Enter the M-Pesa phone number.');
      return;
    }
    if (payAmount <= 0) {
      showError('Invalid amount', 'Enter an amount greater than zero.');
      return;
    }
    if (family && payAmount > family.outstanding + 0.009) {
      showError('Too high', 'Amount cannot exceed the family outstanding balance.');
      return;
    }
    try {
      await prompt.mutateAsync({
        phone_number: phone.trim(),
        amount: payAmount,
        idempotency_key: `family-${Date.now()}`,
      });
      showSuccess('STK sent', 'Check the phone for the M-Pesa prompt.');
      navigation.goBack();
    } catch (err) {
      showError('Payment failed', err instanceof Error ? err.message : 'Could not send STK.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Pay school fees" subtitle="Family payment" onBack={() => navigation.goBack()} />

      {fees.isLoading ? (
        <SkeletonListRows count={4} />
      ) : fees.isError ? (
        <EmptyState
          title="Could not load family fees"
          message={(fees.error as Error)?.message}
          actionLabel="Retry"
          onAction={() => void fees.refetch()}
        />
      ) : !family || family.outstanding <= 0 ? (
        <EmptyState title="Nothing outstanding" message="All linked children are up to date." icon="checkmark-circle-outline" />
      ) : (
        <>
          <SurfaceCard accent="info" style={{ marginBottom: spacing.md }}>
            <Text style={{ color: palette.textSecondary }}>Family outstanding</Text>
            <Text style={{ color: palette.textPrimary, fontSize: 28, fontWeight: '800' }}>
              {formatKes(family.outstanding)}
            </Text>
            <Text style={{ color: colors.error, marginTop: 4 }}>Overdue {formatKes(family.overdue)}</Text>
            <Text style={{ color: palette.textSecondary }}>Upcoming {formatKes(family.upcoming)}</Text>
          </SurfaceCard>

          {children.map((child) => (
            <SurfaceCard key={child.student_id} style={{ marginBottom: spacing.sm }}>
              <Pressable onPress={() => setExpandedChild(expandedChild === child.student_id ? null : child.student_id)}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{child.full_name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[child.admission_number, child.class_name].filter(Boolean).join(' · ')}
                </Text>
                <Text style={{ color: palette.textPrimary, marginTop: 6, fontWeight: '600' }}>
                  {formatKes(child.outstanding)} · overdue {formatKes(child.overdue)} · upcoming{' '}
                  {formatKes(child.upcoming)}
                </Text>
              </Pressable>
              {expandedChild === child.student_id
                ? child.invoices.map((inv) => (
                    <Pressable
                      key={inv.id}
                      onPress={() => setExpandedInvoice(expandedInvoice === inv.id ? null : inv.id)}
                      style={{
                        marginTop: spacing.sm,
                        paddingTop: spacing.sm,
                        borderTopWidth: 1,
                        borderTopColor: palette.border,
                      }}
                    >
                      <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{inv.invoice_number}</Text>
                      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                        {[inv.term_name, inv.year, inv.due_date ? `Due ${formatShortDate(inv.due_date)}` : null]
                          .filter(Boolean)
                          .join(' · ')}
                      </Text>
                      <Text style={{ color: inv.status === 'overdue' ? colors.error : palette.textPrimary }}>
                        {formatKes(inv.balance)} · {inv.status}
                      </Text>
                      {expandedInvoice === inv.id
                        ? (inv.items ?? []).map((item) => (
                            <Text
                              key={item.id}
                              style={{ color: palette.textMuted, marginLeft: 8, fontSize: typography.caption.fontSize }}
                            >
                              {item.name}: {formatKes(item.amount)}
                            </Text>
                          ))
                        : null}
                    </Pressable>
                  ))
                : null}
            </SurfaceCard>
          ))}

          <View style={{ flexDirection: 'row', gap: spacing.sm, marginVertical: spacing.md }}>
            <Button
              label="Pay full balance"
              variant={mode === 'full' ? 'primary' : 'secondary'}
              onPress={() => setMode('full')}
              style={{ flex: 1 }}
            />
            <Button
              label="Custom amount"
              variant={mode === 'custom' ? 'primary' : 'secondary'}
              onPress={() => setMode('custom')}
              style={{ flex: 1 }}
            />
          </View>

          <TextField
            label="Amount (KES)"
            value={amount}
            onChangeText={setAmount}
            keyboardType="decimal-pad"
            editable={mode === 'custom'}
          />
          <TextField
            label="M-Pesa phone"
            value={phone}
            onChangeText={setPhone}
            keyboardType="phone-pad"
            autoCapitalize="none"
          />

          <Text style={{ color: palette.textMuted, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
            Payments allocate to the oldest outstanding invoice first across all children. Wallet is separate — use
            Family Wallet to top up.
          </Text>

          <Button
            label={prompt.isPending ? 'Sending…' : 'Continue to M-Pesa'}
            onPress={() => void submit()}
            loading={prompt.isPending}
            disabled={prompt.isPending || payAmount <= 0}
          />
        </>
      )}
    </ScreenContainer>
  );
};
