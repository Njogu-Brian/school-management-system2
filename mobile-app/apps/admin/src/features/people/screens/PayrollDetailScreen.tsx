import { usePayrollRecordDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, ScrollView, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'PayrollDetail'>;

function money(n: number | null | undefined): string {
  return `KES ${Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function activeMoneyRows(
  rows: Array<{ label: string; value: number | null | undefined; always?: boolean }>,
): Array<{ label: string; value: string }> {
  return rows
    .filter((r) => r.always || (typeof r.value === 'number' && Math.abs(r.value) > 0.0001))
    .map((r) => ({ label: r.label, value: money(r.value) }));
}

export const PayrollDetailScreen: React.FC<Props> = ({ navigation, route }) => {
  const { recordId } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
  const detailQuery = usePayrollRecordDetail(recordId);

  const record = detailQuery.data;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}>
        <AcademicScreenHeader
          title="Payslip detail"
          subtitle={record?.staff_name ?? `Record #${recordId}`}
          onBack={() => navigation.goBack()}
        />

        {detailQuery.isLoading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.xl }} />
        ) : detailQuery.isError || !record ? (
          <EmptyState
            title="Could not load payslip"
            message={(detailQuery.error as Error)?.message ?? 'Try again.'}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void detailQuery.refetch()}
          />
        ) : (
          <View style={{ gap: spacing.md }}>
            <Text
              style={{
                color: palette.primary,
                fontSize: typography.headline.fontSize,
                fontWeight: '700',
              }}
            >
              {money(record.net_salary)}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              Net pay · {record.period_name ?? record.month ?? '—'} · {record.status}
            </Text>

            <FinanceFieldSection
              title="Employee"
              rows={[
                { label: 'Name', value: record.staff_name ?? `Staff #${record.staff_id}` },
                { label: 'Employee no.', value: record.staff_employee_number ?? '—' },
                { label: 'Payslip no.', value: record.payslip_number ?? '—' },
                {
                  label: 'Days worked',
                  value:
                    record.days_worked != null
                      ? `${record.days_worked}${record.days_in_period != null ? ` / ${record.days_in_period}` : ''}`
                      : '—',
                },
              ]}
            />

            <FinanceFieldSection
              title="Earnings"
              rows={activeMoneyRows([
                { label: 'Basic salary', value: record.basic_salary, always: true },
                { label: 'Housing', value: record.housing_allowance },
                { label: 'Transport', value: record.transport_allowance },
                { label: 'Medical', value: record.medical_allowance },
                { label: 'Other allowances', value: record.other_allowances },
                { label: 'Bonus', value: record.bonus },
                { label: 'Gross salary', value: record.gross_salary, always: true },
              ])}
            />

            <FinanceFieldSection
              title="Deductions"
              rows={activeMoneyRows([
                { label: 'NSSF', value: record.nssf_deduction },
                { label: 'NHIF', value: record.nhif_deduction },
                { label: 'SHIF', value: record.shif_deduction },
                { label: 'PAYE', value: record.paye_deduction },
                { label: 'Housing levy', value: record.housing_levy_deduction },
                { label: 'Advance', value: record.advance_deduction },
                { label: 'Custom deductions', value: record.custom_deductions_total },
                { label: 'Other deductions', value: record.other_deductions },
                { label: 'Total deductions', value: record.deductions, always: true },
              ])}
            />

            {(record.notes || record.adjustments_notes) && (
              <FinanceFieldSection
                title="Notes"
                rows={[
                  { label: 'Notes', value: record.notes ?? '—' },
                  { label: 'Adjustments', value: record.adjustments_notes ?? '—' },
                ]}
              />
            )}
          </View>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
