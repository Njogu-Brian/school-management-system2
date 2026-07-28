import { useStudentDetail, useStudentStatement } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatementLedger,
  useTheme,
  type StatementLedgerRow,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatKes } from '../utils/format';

type Nav = StackNavigationProp<ParentStackParamList>;

export const StudentStatementScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteProp<ParentStackParamList, 'StudentStatement'>>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const statement = useStudentStatement(studentId);

  const data = statement.data;
  const balance = data?.closing_balance ?? 0;

  const ledgerRows: StatementLedgerRow[] = useMemo(
    () =>
      (data?.transactions ?? []).map((t) => ({
        id: t.id,
        date: t.date,
        type: t.type,
        reference: t.reference,
        description: t.description,
        votehead: t.votehead,
        debit: t.debit,
        credit: t.credit,
        balance: t.balance,
        invoice_id: t.invoice_id ?? (t.entity_type === 'invoice' ? t.entity_id : null),
        payment_id: t.payment_id ?? (t.entity_type === 'payment' ? t.entity_id : null),
        entity_type: t.entity_type,
      })),
    [data?.transactions],
  );

  const openBreakdown = (row: StatementLedgerRow) => {
    if (row.payment_id) {
      navigation.navigate('PaymentDetail', { studentId, paymentId: row.payment_id });
      return;
    }
    if (row.invoice_id) {
      navigation.navigate('InvoiceDetail', { studentId, invoiceId: row.invoice_id });
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Fee statement"
        subtitle={detail.data?.fullName ?? data?.student?.full_name ?? undefined}
        onBack={() => navigation.goBack()}
      />
      {statement.isLoading ? (
        <SkeletonListRows count={5} />
      ) : statement.isError ? (
        <EmptyState
          title="Could not load statement"
          message={statement.error instanceof Error ? statement.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void statement.refetch()}
        />
      ) : data ? (
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
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.headline.fontSize }}>
              Balance
            </Text>
            <Text style={{ color: colors.primary, fontSize: 28, fontWeight: '700', marginTop: spacing.sm }}>
              {formatKes(balance)}
            </Text>
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md }}>
              <SummaryChip label="Invoiced" value={formatKes(data.total_invoiced)} />
              <SummaryChip label="Paid" value={formatKes(data.total_paid)} />
            </View>
            <Button
              label="Pay with M-Pesa"
              style={{ marginTop: spacing.md }}
              onPress={() =>
                navigation.navigate('MpesaPrompt', {
                  studentId,
                  amount: balance > 0 ? balance : undefined,
                })
              }
            />
          </View>

          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
            Transactions
          </Text>
          <Text style={{ color: palette.textMuted, marginBottom: spacing.sm, fontSize: typography.caption.fontSize }}>
            Tap a line to open the invoice or payment breakdown.
          </Text>
          <StatementLedger rows={ledgerRows} formatAmount={formatKes} onRowPress={openBreakdown} />
        </>
      ) : null}
    </ScreenContainer>
  );
};

function SummaryChip({ label, value }: { label: string; value: string }) {
  const { palette, spacing, typography, radius } = useTheme();
  return (
    <View
      style={{
        backgroundColor: palette.surfaceRaised ?? palette.surface,
        borderRadius: radius.md,
        borderWidth: 1,
        borderColor: palette.border,
        paddingHorizontal: spacing.md,
        paddingVertical: spacing.sm,
        minWidth: 120,
      }}
    >
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{label}</Text>
      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: 2 }}>{value}</Text>
    </View>
  );
}
