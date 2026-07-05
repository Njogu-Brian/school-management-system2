import {
  invoiceStatusLabel,
  useCan,
  useInvoiceDetail,
  useStudentPaymentLink,
  type InvoiceSummary,
} from '@erp/core';
import {
  Button,
  FinanceFieldSection,
  FinanceScreenHeader,
  InvoiceStatusBadge,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, Share, Text, View } from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { formatKes } from '../utils/formatters';

type Props = StackScreenProps<FinanceStackParamList, 'InvoiceDetail'>;

export const InvoiceDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { invoiceId, summary } = route.params;
  const canView = useCan('finance.view');
  const { colors, palette, spacing } = useTheme();
  const detailQuery = useInvoiceDetail(invoiceId, { enabled: canView });

  const invoice = detailQuery.data;
  const seed = summary as InvoiceSummary | undefined;
  const studentId = invoice?.student_id ?? 0;

  const paymentLinkQuery = useStudentPaymentLink(studentId, {
    enabled: canView && studentId > 0 && (invoice?.balance ?? 0) > 0,
  });

  const headerTitle = invoice?.invoice_number ?? seed?.invoiceNumber ?? `Invoice #${invoiceId}`;

  const voteheadRows = useMemo(
    () =>
      (invoice?.items ?? []).map((item) => ({
        label: item.votehead_name,
        value: formatKes(item.total),
      })),
    [invoice?.items],
  );

  const sharePaymentLink = async () => {
    const link = paymentLinkQuery.data;
    if (!link?.url) {
      Alert.alert('Payment link', 'No payment link is available for this student.');
      return;
    }
    const studentName = invoice?.student_name ?? 'Student';
    const balance = formatKes(invoice?.balance ?? link.amount);
    const message = `School fees payment for ${studentName}\nBalance: ${balance}\nPay here: ${link.short_url ?? link.url}`;
    try {
      await Share.share({ message, title: 'M-Pesa payment link' });
    } catch (err) {
      Alert.alert('Share failed', (err as Error).message);
    }
  };

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

  if (detailQuery.isError || !invoice) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <FinanceScreenHeader title={headerTitle} onBack={() => navigation.goBack()} />
        <Text style={{ color: colors.error, textAlign: 'center' }}>
          {(detailQuery.error as Error)?.message ?? 'Could not load invoice.'}
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
        <FinanceScreenHeader title={headerTitle} onBack={() => navigation.goBack()} />
        <View style={{ marginBottom: spacing.md }}>
          <InvoiceStatusBadge status={invoice.status} />
        </View>

        <FinanceFieldSection
          title="Student"
          rows={[
            { label: 'Name', value: invoice.student_name },
            { label: 'Admission #', value: invoice.student_admission_number },
          ]}
        />

        <FinanceFieldSection
          title="Amounts"
          rows={[
            { label: 'Total', value: formatKes(invoice.total_amount) },
            { label: 'Paid', value: formatKes(invoice.paid_amount) },
            { label: 'Balance', value: formatKes(invoice.balance) },
            { label: 'Status', value: invoiceStatusLabel(invoice.status) },
          ]}
        />

        <FinanceFieldSection
          title="Term"
          rows={[
            { label: 'Academic year', value: invoice.academic_year_name },
            { label: 'Term', value: invoice.term_name },
            { label: 'Issue date', value: invoice.issue_date },
            { label: 'Due date', value: invoice.due_date },
          ]}
        />

        <FinanceFieldSection title="Voteheads" rows={voteheadRows.length ? voteheadRows : [{ label: 'Items', value: 'None' }]} />

        {invoice.balance > 0 ? (
          <View style={{ marginTop: spacing.sm }}>
            <Button
              label="Share payment link with parent"
              variant="secondary"
              onPress={() => void sharePaymentLink()}
              loading={paymentLinkQuery.isFetching}
            />
            {paymentLinkQuery.isError ? (
              <Text style={{ color: colors.error, fontSize: 12, marginTop: spacing.xs }}>
                {(paymentLinkQuery.error as Error).message}
              </Text>
            ) : null}
          </View>
        ) : null}

        {invoice.notes ? (
          <FinanceFieldSection title="Notes" rows={[{ label: 'Notes', value: invoice.notes }]} />
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};
