import { downloadAuthenticatedFile, payrollApi, toPayrollSummary, useStaffPayrollRecords } from '@erp/core';
import { EmptyState } from '@erp/ui';
import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { capitalizeStatus, formatKes } from '../utils/formatters';

export interface PayrollTabProps {
  staffId: number;
  canViewFinance: boolean;
}

export const PayrollTab: React.FC<PayrollTabProps> = ({ staffId, canViewFinance }) => {
  const { colors, palette, fontSizes, spacing } = useTheme();
  const query = useStaffPayrollRecords(staffId, { enabled: canViewFinance, perPage: 12 });
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  const handlePayslip = useCallback(async (recordId: number, label: string) => {
    setDownloadingId(recordId);
    try {
      await downloadAuthenticatedFile(payrollApi.payslipDownloadPath(recordId), `payslip-${label}`);
    } catch (err) {
      Alert.alert('Download failed', (err as Error).message);
    } finally {
      setDownloadingId(null);
    }
  }, []);

  if (!canViewFinance) {
    return (
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
        You need finance.view to see payroll records.
      </Text>
    );
  }

  if (query.isLoading) {
    return (
      <View style={{ paddingVertical: 24, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (query.isError) {
    return (
      <View style={{ alignItems: 'center', paddingVertical: 16 }}>
        <Text style={{ color: colors.error }}>{(query.error as Error).message}</Text>
        <Pressable onPress={() => void query.refetch()} style={{ marginTop: 8 }}>
          <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
        </Pressable>
      </View>
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
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 8 }}>
        API: GET /payroll-records?staff_id=
      </Text>
      {records.map((raw) => {
        const row = toPayrollSummary(raw);
        return (
          <View
            key={raw.id}
            style={{
              borderWidth: 1,
              borderColor: palette.border,
              borderRadius: 8,
              padding: spacing.sm,
              marginBottom: spacing.xs,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{row.periodLabel}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 4 }}>
              {formatKes(row.netSalary)} · {capitalizeStatus(row.status)}
            </Text>
            <Pressable onPress={() => void handlePayslip(raw.id, row.periodLabel)} style={{ marginTop: 8 }}>
              <Text style={{ color: colors.primary, fontWeight: '600' }}>
                {downloadingId === raw.id ? 'Downloading…' : 'Download payslip PDF'}
              </Text>
            </Pressable>
          </View>
        );
      })}
    </>
  );
};
