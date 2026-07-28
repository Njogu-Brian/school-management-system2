import { usePaymentDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FinanceFieldSection,
  ScreenContainer,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Linking, Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatKes, formatShortDate } from '../utils/format';

export const ParentPaymentDetailScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<RouteProp<ParentStackParamList, 'PaymentDetail'>>();
  const { paymentId, studentId } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const detailQuery = usePaymentDetail(paymentId);

  const payment = detailQuery.data;

  const summaryRows = useMemo(() => {
    if (!payment) return [];
    return [
      { label: 'Student', value: payment.student_name ?? '—' },
      { label: 'Receipt', value: payment.receipt_number || '—' },
      { label: 'Amount', value: formatKes(payment.amount) },
      { label: 'Method', value: String(payment.payment_method) },
      { label: 'Date', value: payment.payment_date ? formatShortDate(payment.payment_date) : '—' },
      { label: 'Reference', value: payment.reference_number ?? payment.mpesa_receipt_number ?? '—' },
      { label: 'Status', value: String(payment.status) },
      { label: 'Allocated', value: formatKes(payment.allocated_amount) },
      { label: 'Unallocated', value: formatKes(payment.unallocated_amount) },
    ];
  }, [payment]);

  const allocationRows = useMemo(
    () =>
      (payment?.allocations ?? []).map((a) => ({
        label: a.invoice_number ?? (a.invoice_id ? `Invoice #${a.invoice_id}` : 'Allocation'),
        value: formatKes(a.amount),
      })),
    [payment],
  );

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={payment?.receipt_number ?? `Payment #${paymentId}`}
        subtitle="Payment breakdown"
        onBack={() => navigation.goBack()}
      />
      {detailQuery.isError ? (
        <Pressable onPress={() => void detailQuery.refetch()}>
          <Text style={{ color: colors.error }}>{(detailQuery.error as Error).message}</Text>
        </Pressable>
      ) : payment ? (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: spacing.md, gap: spacing.sm }}>
            <StatusBadge
              label={String(payment.status)}
              tone={payment.reversed ? 'danger' : 'success'}
            />
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {formatKes(payment.amount)}
            </Text>
          </View>
          <FinanceFieldSection title="Summary" rows={summaryRows} />
          {allocationRows.length > 0 ? (
            <View style={{ marginTop: spacing.md }}>
              <FinanceFieldSection title="Applied to invoices" rows={allocationRows} />
              {(payment.allocations ?? []).map((a) =>
                a.invoice_id ? (
                  <Button
                    key={a.id}
                    label={`Open ${a.invoice_number ?? `invoice #${a.invoice_id}`}`}
                    variant="secondary"
                    style={{ marginTop: spacing.sm }}
                    onPress={() =>
                      navigation.navigate('InvoiceDetail', {
                        studentId: payment.student_id || studentId,
                        invoiceId: a.invoice_id as number,
                      })
                    }
                  />
                ) : null,
              )}
            </View>
          ) : null}
          {payment.receipt_public_url ? (
            <Button
              label="Open receipt"
              variant="ghost"
              style={{ marginTop: spacing.md }}
              onPress={() => void Linking.openURL(payment.receipt_public_url as string)}
            />
          ) : null}
        </>
      ) : (
        <Text style={{ color: palette.textSecondary }}>Payment not found.</Text>
      )}
    </ScreenContainer>
  );
};
