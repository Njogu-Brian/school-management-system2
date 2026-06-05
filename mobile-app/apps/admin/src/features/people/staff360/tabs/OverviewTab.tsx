import {
  totalLeaveRemaining,
  type StaffDetail,
  type StaffLeaveBalanceItem,
  type StaffPayrollSummary,
} from '@erp/core';
import { StaffFieldSection, StudentSummaryWidgets, type StudentSummaryWidgetData } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { capitalizeStatus, formatKes, formatPercent } from '../utils/formatters';

export interface OverviewTabProps {
  staff: StaffDetail;
  leaveBalances: StaffLeaveBalanceItem[];
  leaveBalancesLoading: boolean;
  attendancePct: number | null;
  attendanceLoading: boolean;
  canViewFinance: boolean;
  latestPayroll: StaffPayrollSummary | null;
  payrollLoading: boolean;
  pendingLeaveCount: number;
}

export const OverviewTab: React.FC<OverviewTabProps> = ({
  staff,
  leaveBalances,
  leaveBalancesLoading,
  attendancePct,
  attendanceLoading,
  canViewFinance,
  latestPayroll,
  payrollLoading,
  pendingLeaveCount,
}) => {
  const { palette, colors, spacing, fontSizes } = useTheme();

  const widgets = useMemo((): StudentSummaryWidgetData[] => {
    const list: StudentSummaryWidgetData[] = [
      {
        id: 'employment',
        label: 'Employment',
        value: capitalizeStatus(staff.employmentStatus ?? 'active'),
        icon: 'briefcase-outline',
      },
      {
        id: 'leave',
        label: 'Leave remaining',
        value: leaveBalancesLoading ? '…' : `${totalLeaveRemaining(leaveBalances)}d`,
        delta: pendingLeaveCount > 0 ? `${pendingLeaveCount} pending` : 'Active year',
        icon: 'calendar-outline',
      },
      {
        id: 'attendance',
        label: 'Attendance (month)',
        value: attendanceLoading ? '…' : formatPercent(attendancePct),
        delta: 'Present rate',
        icon: 'checkmark-circle-outline',
      },
    ];

    if (canViewFinance) {
      list.push({
        id: 'payroll',
        label: 'Latest net pay',
        value: payrollLoading ? '…' : formatKes(latestPayroll?.netSalary),
        delta: latestPayroll?.periodLabel ?? 'No payslips yet',
        icon: 'wallet-outline',
      });
    }

    return list;
  }, [
    staff,
    leaveBalances,
    leaveBalancesLoading,
    attendancePct,
    attendanceLoading,
    canViewFinance,
    latestPayroll,
    payrollLoading,
    pendingLeaveCount,
  ]);

  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />

      {(leaveBalancesLoading || payrollLoading) && canViewFinance ? (
        <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} />
      ) : null}

      <StaffFieldSection
        title="Quick profile"
        rows={[
          { label: 'System role', value: staff.systemRole },
          { label: 'Category', value: staff.staffCategory },
          { label: 'Work email', value: staff.email },
          { label: 'Phone', value: staff.phone },
          { label: 'Supervisor', value: staff.supervisorName },
          { label: 'Hire date', value: staff.hireDate },
        ]}
      />

      <Text
        style={{
          color: palette.textSecondary,
          fontSize: fontSizes.xs,
          textAlign: 'center',
        }}
      >
        Read-only Staff 360 — no edits in this release.
      </Text>
    </View>
  );
};
