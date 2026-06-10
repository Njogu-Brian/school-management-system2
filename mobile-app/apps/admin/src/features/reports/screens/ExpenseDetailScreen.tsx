import { useCan, useExpense } from '@erp/core';
import {
  AcademicScreenHeader,
  FinanceFieldSection,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
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
