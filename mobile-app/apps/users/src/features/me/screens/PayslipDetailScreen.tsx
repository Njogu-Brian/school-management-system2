import {
  downloadAuthenticatedFile,
  formatFinanceAmount,
  payrollApi,
  usePayrollRecordDetail,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, View } from 'react-native';
import { showError } from '../../shared/utils/feedback';

type PayslipDetailParams = { recordId: number };

function money(n: number | null | undefined): string {
  return formatFinanceAmount(n);
}

function activeMoneyRows(
  rows: Array<{ label: string; value: number | null | undefined; always?: boolean }>,
): Array<{ label: string; value: string }> {
  return rows
    .filter((r) => r.always || (typeof r.value === 'number' && Math.abs(r.value) > 0.0001))
    .map((r) => ({ label: r.label, value: money(r.value) }));
}

/**
 * Full payslip breakdown — mirrors Admin `PayrollDetailScreen` for staff self-service.
 */
export const PayslipDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<Record<string, PayslipDetailParams>, string>>();
  const recordId = route.params?.recordId ?? 0;
  const { colors, palette, spacing, typography } = useTheme();
  const detailQuery = usePayrollRecordDetail(recordId, { enabled: recordId > 0 });
  const [downloading, setDownloading] = useState(false);

  const record = detailQuery.data;

  const downloadPdf = useCallback(async () => {
    if (!record) return;
    const label = record.period_name ?? record.month ?? String(record.id);
    setDownloading(true);
    try {
      await downloadAuthenticatedFile(payrollApi.payslipDownloadPath(record.id), `payslip-${label}`);
    } catch (err) {
      showError('Download failed', err instanceof Error ? err.message : 'Could not download payslip.');
    } finally {
      setDownloading(false);
    }
  }, [record]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Payslip detail"
          subtitle={record?.period_name ?? record?.month ?? `Record #${recordId}`}
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

            <Button
              label={downloading ? 'Downloading…' : 'Download payslip PDF'}
              variant="secondary"
              loading={downloading}
              onPress={() => void downloadPdf()}
              style={{ alignSelf: 'flex-start' }}
            />

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
