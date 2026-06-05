import { toPayrollSummary, useStaffPayrollRecords } from '@erp/core';
import { EmptyState, FinanceFieldSection } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { capitalizeStatus, formatKes } from '../utils/formatters';

export interface PayrollTabProps {
  staffId: number;
  canViewFinance: boolean;
}

/** Payroll history from `GET /payroll-records?staff_id=`. */
export const PayrollTab: React.FC<PayrollTabProps> = ({ staffId, canViewFinance }) => {
  const { colors, palette, fontSizes } = useTheme();
  const query = useStaffPayrollRecords(staffId, { enabled: canViewFinance, perPage: 12 });

  const rows = useMemo(() => {
    const records = query.data?.data ?? [];
    return records.map((raw) => {
      const row = toPayrollSummary(raw);
      return {
        label: row.periodLabel,
        value: `${formatKes(row.netSalary)} · ${capitalizeStatus(row.status)}`,
      };
    });
  }, [query.data]);

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

  if (rows.length === 0) {
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
      <FinanceFieldSection title="Payroll history" rows={rows} />
    </>
  );
};
