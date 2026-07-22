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
              rows={[
                { label: 'Basic salary', value: money(record.basic_salary) },
                { label: 'Housing', value: money(record.housing_allowance) },
                { label: 'Transport', value: money(record.transport_allowance) },
                { label: 'Medical', value: money(record.medical_allowance) },
                { label: 'Other allowances', value: money(record.other_allowances) },
                { label: 'Bonus', value: money(record.bonus) },
                { label: 'Gross salary', value: money(record.gross_salary) },
              ]}
            />

            <FinanceFieldSection
              title="Deductions"
              rows={[
                { label: 'NSSF', value: money(record.nssf_deduction) },
                { label: 'NHIF', value: money(record.nhif_deduction) },
                { label: 'SHIF', value: money(record.shif_deduction) },
                { label: 'PAYE', value: money(record.paye_deduction) },
                { label: 'Housing levy', value: money(record.housing_levy_deduction) },
                { label: 'Advance', value: money(record.advance_deduction) },
                { label: 'Custom deductions', value: money(record.custom_deductions_total) },
                { label: 'Other deductions', value: money(record.other_deductions) },
                { label: 'Total deductions', value: money(record.deductions) },
              ]}
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
