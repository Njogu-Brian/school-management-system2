import { paymentMethodLabel, useCan, usePaymentDetail, type PaymentSummary } from '@erp/core';
import {
  Button,
  FinanceFieldSection,
  FinanceScreenHeader,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Linking, Pressable, ScrollView, Text } from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'PaymentDetail'>;

export const PaymentDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { paymentId, summary } = route.params;
  const canView = useCan('finance.view');
  const { colors, palette, spacing } = useTheme();
  const detailQuery = usePaymentDetail(paymentId, { enabled: canView });

  const payment = detailQuery.data;
  const seed = summary as PaymentSummary | undefined;
  const title = payment?.receipt_number ?? seed?.receiptNumber ?? `Payment #${paymentId}`;

  const allocationRows = useMemo(
    () =>
      (payment?.allocations ?? []).map((a) => ({
        label: a.invoice_number ?? `Invoice #${a.invoice_id ?? '—'}`,
        value: formatKes(a.amount),
      })),
    [payment?.allocations],
  );

  if (!canView) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (detailQuery.isError || !payment) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <FinanceScreenHeader title={title} onBack={() => navigation.goBack()} />
        <Text style={{ color: colors.error, textAlign: 'center' }}>
          {(detailQuery.error as Error)?.message ?? 'Could not load payment.'}
        </Text>
        <Pressable onPress={() => void detailQuery.refetch()} style={{ marginTop: spacing.sm, alignSelf: 'center' }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <FinanceScreenHeader title={title} onBack={() => navigation.goBack()} />

        <FinanceFieldSection
          title="Student"
          rows={[
            { label: 'Name', value: payment.student_name },
            { label: 'Admission #', value: payment.student_admission_number },
            { label: 'Class', value: payment.class_name },
            { label: 'Stream', value: payment.stream_name },
          ]}
        />

        <FinanceFieldSection
          title="Payment"
          rows={[
            { label: 'Amount', value: formatKes(payment.amount) },
            { label: 'Method', value: paymentMethodLabel(payment.payment_method) },
            { label: 'Date', value: payment.payment_date },
            { label: 'Reference', value: payment.reference_number },
            { label: 'M-Pesa receipt', value: payment.mpesa_receipt_number },
            { label: 'Status', value: payment.reversed ? 'Reversed' : payment.status },
            { label: 'Notes', value: payment.notes },
          ]}
        />

        <FinanceFieldSection
          title="Allocation"
          rows={[
            { label: 'Allocated', value: formatKes(payment.allocated_amount) },
            { label: 'Unallocated', value: formatKes(payment.unallocated_amount) },
            ...allocationRows,
          ]}
        />

        {payment.receipt_public_url ? (
          <Button
            label="View receipt"
            variant="secondary"
            onPress={() => void Linking.openURL(payment.receipt_public_url as string)}
            style={{ marginBottom: spacing.md }}
          />
        ) : null}

        {payment.portal_note ? (
          <Text style={{ color: palette.textSecondary, fontSize: 12 }}>{payment.portal_note}</Text>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};
