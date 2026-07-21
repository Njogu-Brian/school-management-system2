import { downloadAuthenticatedFile, payrollApi, toPayrollSummary, useStaffPayrollRecords } from '@erp/core';
import { EmptyState, useTheme } from '@erp/ui';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { showError } from '../../../shared/utils/feedback';
import { capitalizeStatus, formatKes } from '../utils/formatters';

export interface PayrollTabProps {
  staffId: number;
  canViewFinance: boolean;
}

export const PayrollTab: React.FC<PayrollTabProps> = ({ staffId, canViewFinance }) => {
  const { colors, palette, typography, spacing, radius } = useTheme();
  const query = useStaffPayrollRecords(staffId, { enabled: canViewFinance, perPage: 12 });
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  const handlePayslip = useCallback(async (recordId: number, label: string) => {
    setDownloadingId(recordId);
    try {
      await downloadAuthenticatedFile(payrollApi.payslipDownloadPath(recordId), `payslip-${label}`);
    } catch (err) {
      showError('Download failed', (err as Error).message);
    } finally {
      setDownloadingId(null);
    }
  }, []);

  if (!canViewFinance) {
    return (
      <EmptyState
        title="Finance access required"
        message="You need finance.view to see payroll records."
        icon="lock-closed-outline"
      />
    );
  }

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Could not load payroll"
        message={(query.error as Error).message}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }

  const records = query.data?.data ?? [];
  if (records.length === 0) {
    return (
      <EmptyState
        title="No payroll records"
        message="No payslips have been generated for this staff member yet."
        icon="cash-outline"
      />
    );
  }

  return (
    <>
      {records.map((raw) => {
        const row = toPayrollSummary(raw);
        return (
          <View
            key={raw.id}
            style={{
              borderWidth: 1,
              borderColor: palette.borderSubtle,
              borderRadius: radius.card,
              padding: spacing.md,
              marginBottom: spacing.sm,
              backgroundColor: palette.surfaceRaised,
            }}
          >
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '600',
                fontSize: typography.body.fontSize,
              }}
            >
              {row.periodLabel}
            </Text>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.overline.fontSize,
                marginTop: 4,
              }}
            >
              {formatKes(row.netSalary)} · {capitalizeStatus(row.status)}
            </Text>
            <Pressable onPress={() => void handlePayslip(raw.id, row.periodLabel)} style={{ marginTop: 8 }}>
              <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
                {downloadingId === raw.id ? 'Downloading…' : 'Download payslip PDF'}
              </Text>
            </Pressable>
          </View>
        );
      })}
    </>
  );
};
