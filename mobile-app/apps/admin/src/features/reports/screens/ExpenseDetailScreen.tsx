import {
  useApproveExpense,
  useCan,
  useExpense,
  usePayExpense,
  useRejectExpense,
  useSubmitExpense,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FinanceFieldSection,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';
import { capitalizeStatus, formatDateLabel, formatDateTimeLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'ExpenseDetail'>;

const STATUS_TONES: Record<string, 'brand' | 'success' | 'warning' | 'danger' | 'info'> = {
  draft: 'info',
  submitted: 'warning',
  approved: 'success',
  rejected: 'danger',
  paid: 'brand',
};

const formatAmount = (value?: number) =>
  `KES ${Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export const ExpenseDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { expenseId } = route.params;
  const canView = useCan('reports.view');
  const { palette, spacing, radius, typography } = useTheme();
  const query = useExpense(expenseId, { enabled: canView });
  const expense = query.data;

  const submitMutation = useSubmitExpense();
  const approveMutation = useApproveExpense();
  const rejectMutation = useRejectExpense();
  const payMutation = usePayExpense();

  const [remarks, setRemarks] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [referenceNo, setReferenceNo] = useState('');

  const workflowPending =
    submitMutation.isPending || approveMutation.isPending || rejectMutation.isPending || payMutation.isPending;

  const onSubmitExpense = () => {
    confirmAction('Submit expense', 'Send this expense for approval?', 'Submit', async () => {
      try {
        await submitMutation.mutateAsync({ id: expenseId });
        showSuccess('Submitted', 'Expense sent for approval.');
      } catch (err) {
        showError('Submit failed', (err as Error).message);
      }
    });
  };

  const onApprove = () => {
    confirmAction('Approve expense', 'Approve this expense for payment?', 'Approve', async () => {
      try {
        await approveMutation.mutateAsync({ id: expenseId, remarks: remarks.trim() || undefined });
        setRemarks('');
        showSuccess('Approved', 'Expense approved.');
      } catch (err) {
        showError('Approve failed', (err as Error).message);
      }
    });
  };

  const onReject = () => {
    if (!remarks.trim()) {
      showError('Remarks required', 'Add remarks explaining why this expense is rejected.');
      return;
    }
    confirmAction('Reject expense', 'Reject this expense? The requester will see your remarks.', 'Reject', async () => {
      try {
        await rejectMutation.mutateAsync({ id: expenseId, remarks: remarks.trim() });
        setRemarks('');
        showSuccess('Rejected', 'Expense rejected.');
      } catch (err) {
        showError('Reject failed', (err as Error).message);
      }
    });
  };

  const onPay = () => {
    confirmAction(
      'Mark as paid',
      'This creates a payment voucher, records the payment and posts ledger entries.',
      'Pay',
      async () => {
        try {
          await payMutation.mutateAsync({
            id: expenseId,
            payment_method: paymentMethod.trim() || undefined,
            reference_no: referenceNo.trim() || undefined,
          });
          setPaymentMethod('');
          setReferenceNo('');
          showSuccess('Paid', 'Expense paid and posted to ledger.');
        } catch (err) {
          showError('Payment failed', (err as Error).message);
        }
      },
    );
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (query.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Expense" onBack={() => navigation.goBack()} />
        <SkeletonListRows count={6} variant="compact" />
      </ScreenContainer>
    );
  }

  if (!expense) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Expense" onBack={() => navigation.goBack()} />
        <ListEmptyState
          icon="wallet-outline"
          title="Expense not found"
          message="This expense may have been removed."
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={expense.expense_no ?? `Expense #${expense.id}`}
        subtitle={expense.vendor ?? undefined}
        onBack={() => navigation.goBack()}
      />

      <View style={styles.badgeRow}>
        <StatusBadge
          label={capitalizeStatus(expense.status ?? 'draft')}
          tone={STATUS_TONES[expense.status ?? ''] ?? 'brand'}
        />
        <Text style={{ color: palette.textPrimary, fontWeight: '800', fontSize: typography.title.fontSize }}>
          {formatAmount(expense.total)}
        </Text>
      </View>

      <FinanceFieldSection
        title="Overview"
        rows={[
          { label: 'Vendor', value: expense.vendor ?? '—' },
          { label: 'Expense date', value: formatDateLabel(expense.expense_date) },
          { label: 'Due date', value: formatDateLabel(expense.due_date) },
          { label: 'Subtotal', value: formatAmount(expense.subtotal) },
          { label: 'Tax', value: formatAmount(expense.tax_total) },
          { label: 'Total', value: formatAmount(expense.total) },
          { label: 'Requested by', value: expense.requested_by ?? '—' },
          { label: 'Approved by', value: expense.approved_by ?? '—' },
          { label: 'Approved at', value: formatDateTimeLabel(expense.approved_at) },
        ]}
      />

      {expense.lines.length > 0 ? (
        <View style={{ marginTop: spacing.md }}>
          <Text style={[styles.sectionLabel, { color: palette.textSecondary }]}>LINE ITEMS</Text>
          {expense.lines.map((line) => (
            <View
              key={line.id}
              style={[
                styles.lineCard,
                { backgroundColor: palette.surfaceRaised, borderColor: palette.borderSubtle, borderRadius: radius.md },
              ]}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }} numberOfLines={2}>
                {line.description ?? line.category ?? 'Line item'}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: 12, marginTop: 2 }}>
                {[line.category, line.department].filter(Boolean).join(' · ') || '—'}
              </Text>
              <View style={styles.lineFooter}>
                <Text style={{ color: palette.textSecondary, fontSize: 12 }}>
                  {line.qty} × {formatAmount(line.unit_cost)}
                  {line.tax_rate ? ` (+${line.tax_rate}% tax)` : ''}
                </Text>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {formatAmount(line.line_total)}
                </Text>
              </View>
            </View>
          ))}
        </View>
      ) : null}

      {expense.vouchers.length > 0 ? (
        <View style={{ marginTop: spacing.md }}>
          <FinanceFieldSection
            title="Payment vouchers"
            rows={expense.vouchers.map((v) => ({
              label: v.voucher_no ?? `Voucher #${v.id}`,
              value: `${formatAmount(v.amount)} · ${capitalizeStatus(v.status ?? '—')}`,
            }))}
          />
        </View>
      ) : null}

      {expense.notes ? (
        <View style={[styles.notes, { borderColor: palette.border, marginTop: spacing.md }]}>
          <Text style={{ color: palette.textSecondary, fontSize: 12, fontWeight: '600', marginBottom: 4 }}>NOTES</Text>
          <Text style={{ color: palette.textPrimary, lineHeight: 20 }}>{expense.notes}</Text>
        </View>
      ) : null}

      {expense.can_submit || expense.can_approve || expense.can_pay ? (
        <View style={{ marginTop: spacing.lg }}>
          <Text style={[styles.sectionLabel, { color: palette.textSecondary }]}>WORKFLOW</Text>

          {expense.can_submit ? (
            <Button
              label={submitMutation.isPending ? 'Submitting…' : 'Submit for approval'}
              onPress={onSubmitExpense}
              disabled={workflowPending}
              loading={submitMutation.isPending}
            />
          ) : null}

          {expense.can_approve ? (
            <View style={{ gap: spacing.sm }}>
              <TextField
                label="Remarks (required to reject)"
                value={remarks}
                onChangeText={setRemarks}
                placeholder="Review notes…"
              />
              <Button
                label={approveMutation.isPending ? 'Approving…' : 'Approve expense'}
                onPress={onApprove}
                disabled={workflowPending}
                loading={approveMutation.isPending}
              />
              <Button
                label={rejectMutation.isPending ? 'Rejecting…' : 'Reject expense'}
                variant="ghost"
                onPress={onReject}
                disabled={workflowPending}
              />
            </View>
          ) : null}

          {expense.can_pay ? (
            <View style={{ gap: spacing.sm }}>
              <TextField
                label="Payment method (optional)"
                value={paymentMethod}
                onChangeText={setPaymentMethod}
                placeholder="e.g. M-Pesa, bank transfer, cash"
              />
              <TextField
                label="Reference no (optional)"
                value={referenceNo}
                onChangeText={setReferenceNo}
                placeholder="Transaction reference"
              />
              <Button
                label={payMutation.isPending ? 'Recording payment…' : 'Mark as paid'}
                onPress={onPay}
                disabled={workflowPending}
                loading={payMutation.isPending}
              />
            </View>
          ) : null}
        </View>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  badgeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionLabel: { fontSize: 12, fontWeight: '700', letterSpacing: 0.4, marginBottom: 8 },
  lineCard: { borderWidth: StyleSheet.hairlineWidth, padding: 12, marginBottom: 8 },
  lineFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
  },
  notes: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, padding: 14 },
});
