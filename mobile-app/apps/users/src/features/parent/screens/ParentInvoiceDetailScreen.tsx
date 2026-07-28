import { useInvoiceDetail } from '@erp/core';
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
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatKes, formatShortDate } from '../utils/format';

export const ParentInvoiceDetailScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<RouteProp<ParentStackParamList, 'InvoiceDetail'>>();
  const { invoiceId, studentId } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const detailQuery = useInvoiceDetail(invoiceId);

  const invoice = detailQuery.data;

  const summaryRows = useMemo(() => {
    if (!invoice) return [];
    return [
      { label: 'Student', value: invoice.student_name ?? '—' },
      { label: 'Number', value: invoice.invoice_number },
      { label: 'Status', value: String(invoice.status) },
      { label: 'Total', value: formatKes(invoice.total_amount) },
      { label: 'Paid', value: formatKes(invoice.paid_amount) },
      { label: 'Balance', value: formatKes(invoice.balance) },
      { label: 'Due date', value: invoice.due_date ? formatShortDate(invoice.due_date) : '—' },
      { label: 'Issued', value: invoice.issue_date ? formatShortDate(invoice.issue_date) : '—' },
      { label: 'Term', value: invoice.term_name ?? '—' },
      { label: 'Year', value: invoice.academic_year_name ?? '—' },
    ];
  }, [invoice]);

  const itemRows = useMemo(
    () =>
      (invoice?.items ?? []).map((item) => {
        const discount = Number(item.discount_amount ?? 0);
        const gross = formatKes(item.amount);
        const net = formatKes(item.total ?? item.amount);
        const value =
          discount > 0 ? `${gross} − discount ${formatKes(discount)} = ${net}` : net;
        return {
          label: item.votehead_name,
          value,
        };
      }),
    [invoice],
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
        title={invoice?.invoice_number ?? `Invoice #${invoiceId}`}
        subtitle="Invoice details"
        onBack={() => navigation.goBack()}
      />
      {detailQuery.isError ? (
        <Pressable onPress={() => void detailQuery.refetch()}>
          <Text style={{ color: colors.error }}>{(detailQuery.error as Error).message}</Text>
        </Pressable>
      ) : invoice ? (
        <>
          <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: spacing.md, gap: spacing.sm }}>
            <StatusBadge label={String(invoice.status)} tone={invoice.balance > 0 ? 'warning' : 'success'} />
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              Balance {formatKes(invoice.balance)}
            </Text>
          </View>
          <FinanceFieldSection title="Summary" rows={summaryRows} />
          {itemRows.length > 0 ? (
            <View style={{ marginTop: spacing.md }}>
              <FinanceFieldSection title="Line items" rows={itemRows} />
            </View>
          ) : null}
          {invoice.balance > 0 ? (
            <View style={{ marginTop: spacing.lg, gap: spacing.sm }}>
              <Button
                label="Pay with M-Pesa"
                onPress={() =>
                  navigation.navigate('MpesaPrompt', {
                    studentId: invoice.student_id || studentId,
                    amount: invoice.balance,
                  })
                }
              />
              <Button
                label="Pay from wallet"
                variant="secondary"
                onPress={() =>
                  navigation.navigate('WalletHome', {
                    payInvoiceId: invoice.id,
                    studentId: invoice.student_id || studentId,
                  })
                }
              />
            </View>
          ) : null}
        </>
      ) : (
        <Text style={{ color: palette.textSecondary }}>Invoice not found.</Text>
      )}
    </ScreenContainer>
  );
};
