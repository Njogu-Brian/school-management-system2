import {
  useCan,
  useStaffAttendanceHistory,
  useStaffDetail,
  useStaffLatestPayroll,
  useStaffLeaveBalances,
  useStaffLeaveRequests,
  type StaffDetail,
  type StaffSummary,
} from '@erp/core';
import type { StackScreenProps } from '@react-navigation/stack';
import { EmptyState, ScreenContainer, Staff360Layout, useTheme, type Staff360TabId } from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';
import { AttendanceTab } from '../staff360/tabs/AttendanceTab';
import { DocumentsTab } from '../staff360/tabs/DocumentsTab';
import { EmploymentTab } from '../staff360/tabs/EmploymentTab';
import { LeaveTab } from '../staff360/tabs/LeaveTab';
import { OverviewTab } from '../staff360/tabs/OverviewTab';
import { PayrollTab } from '../staff360/tabs/PayrollTab';
import { PerformanceTab } from '../staff360/tabs/PerformanceTab';
import { TrainingTab } from '../staff360/tabs/TrainingTab';
import { TeachingTab } from '../staff360/tabs/TeachingTab';

type Props = StackScreenProps<PeopleStackParamList, 'StaffDetail'>;

const TABS: Array<{ id: Staff360TabId; label: string }> = [
  { id: 'overview', label: 'Overview' },
  { id: 'employment', label: 'Employment' },
  { id: 'teaching', label: 'Teaching' },
  { id: 'leave', label: 'Leave' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'payroll', label: 'Payroll' },
  { id: 'performance', label: 'Performance' },
  { id: 'documents', label: 'Documents' },
  { id: 'training', label: 'Training' },
];

function summaryAsDetail(summary: StaffSummary): StaffDetail {
  return {
    ...summary,
    idNumber: null,
    personalEmail: null,
    maritalStatus: null,
    residentialAddress: null,
    emergencyContact: { name: null, relationship: null, phone: null },
    hireDate: null,
    terminationDate: null,
    employmentType: null,
    contractStartDate: null,
    contractEndDate: null,
    dateOfBirth: null,
    departmentId: null,
    staffCategoryId: null,
    jobTitleId: null,
    supervisorId: null,
    supervisorName: null,
    maxLessonsPerWeek: null,
    basicSalary: null,
    bankName: null,
    bankBranch: null,
    bankAccount: null,
    kraPin: null,
    nssf: null,
    nhif: null,
    statutoryExemptions: [],
  };
}

export const StaffDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { staffId, summary } = route.params;
  const canView = useCan(['people.view', 'staff.view']);
  const canViewFinance = useCan('finance.view');
  const { colors, spacing } = useTheme();
  const [activeTab, setActiveTab] = useState<Staff360TabId>('overview');

  const detailQuery = useStaffDetail(staffId, { enabled: canView });
  const staff = detailQuery.data ?? (summary ? summaryAsDetail(summary) : undefined);

  const loadLeave = activeTab === 'overview' || activeTab === 'leave';
  const loadAttendance = activeTab === 'overview' || activeTab === 'attendance';
  const loadPayroll = canViewFinance && activeTab === 'overview';

  const leaveBalancesQuery = useStaffLeaveBalances(staffId, { enabled: canView && loadLeave });
  const leaveRequestsQuery = useStaffLeaveRequests(staffId, { enabled: canView && loadLeave });
  const pendingLeaveQuery = useStaffLeaveRequests(staffId, {
    enabled: canView && activeTab === 'overview',
    status: 'pending',
    perPage: 1,
  });
  const attendanceQuery = useStaffAttendanceHistory(staffId, { enabled: canView && loadAttendance });
  const payrollQuery = useStaffLatestPayroll(staffId, { enabled: loadPayroll });

  const header = useMemo(() => {
    if (!staff) return null;
    const orgLabel = [staff.departmentName, staff.jobTitle].filter(Boolean).join(' · ') || '—';
    return {
      fullName: staff.fullName,
      employeeNumber: staff.employeeNumber,
      orgLabel,
      avatarUrl: staff.avatarUrl,
      employmentStatus: staff.employmentStatus,
      systemRole: staff.systemRole,
    };
  }, [staff]);

  const pendingLeaveCount = pendingLeaveQuery.data?.total ?? 0;

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { paddingHorizontal: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need people.view permission to view staff profiles."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  if (detailQuery.isLoading && !staff) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (!staff || !header) {
    return (
      <ScreenContainer contentContainerStyle={[styles.centered, { paddingHorizontal: spacing.lg }]}>
        <EmptyState
          title="Staff not found"
          message="This staff member could not be loaded."
          icon="person-outline"
          actionLabel={detailQuery.isError ? 'Retry' : undefined}
          onAction={detailQuery.isError ? () => void detailQuery.refetch() : undefined}
        />
      </ScreenContainer>
    );
  }

  const tabContent = (() => {
    switch (activeTab) {
      case 'overview':
        return (
          <OverviewTab
            staff={staff}
            leaveBalances={leaveBalancesQuery.data ?? []}
            leaveBalancesLoading={leaveBalancesQuery.isLoading}
            attendancePct={attendanceQuery.summary.percentage}
            attendanceLoading={attendanceQuery.isLoading}
            canViewFinance={canViewFinance}
            latestPayroll={payrollQuery.latest}
            payrollLoading={payrollQuery.isLoading}
            pendingLeaveCount={pendingLeaveCount}
          />
        );
      case 'employment':
        return <EmploymentTab staff={staff} canViewFinance={canViewFinance} />;
      case 'teaching':
        return <TeachingTab staffId={staffId} systemRole={staff.systemRole} />;
      case 'leave':
        return (
          <LeaveTab
            balances={leaveBalancesQuery.data ?? []}
            balancesLoading={leaveBalancesQuery.isLoading}
            balancesError={leaveBalancesQuery.isError}
            onRetryBalances={() => void leaveBalancesQuery.refetch()}
            requests={leaveRequestsQuery.data?.data ?? []}
            requestsLoading={leaveRequestsQuery.isLoading}
            requestsError={leaveRequestsQuery.isError}
            onRetryRequests={() => void leaveRequestsQuery.refetch()}
          />
        );
      case 'attendance':
        return (
          <AttendanceTab
            isLoading={attendanceQuery.isLoading}
            isError={attendanceQuery.isError}
            onRetry={() => void attendanceQuery.refetch()}
            present={attendanceQuery.summary.present}
            absent={attendanceQuery.summary.absent}
            late={attendanceQuery.summary.late}
            halfDay={attendanceQuery.summary.halfDay}
            percentage={attendanceQuery.summary.percentage}
            rangeLabel={`${attendanceQuery.range.startDate} → ${attendanceQuery.range.endDate}`}
            days={attendanceQuery.days}
          />
        );
      case 'payroll':
        return <PayrollTab staffId={staffId} canViewFinance={canViewFinance} />;
      case 'performance':
        return (
          <PerformanceTab
            staffId={staffId}
            onOpenReview={(reviewId) =>
              navigation.navigate('PerformanceReviewDetail', { staffId, reviewId })
            }
          />
        );
      case 'documents':
        return <DocumentsTab staffId={staffId} />;
      case 'training':
        return (
          <TrainingTab
            staffId={staffId}
            onOpenRecord={(recordId) =>
              navigation.navigate('TrainingRecordDetail', { staffId, recordId })
            }
          />
        );
      default:
        return null;
    }
  })();

  return (
    <ScreenContainer scroll={false} style={styles.flex}>
      <Pressable
        onPress={() => navigation.navigate('StaffEdit', { staffId })}
        style={{ alignSelf: 'flex-end', marginRight: spacing.md, marginTop: spacing.xs }}
      >
        <Text style={{ color: colors.primary, fontWeight: '700' }}>Edit profile</Text>
      </Pressable>
      <Staff360Layout
        header={header}
        tabs={TABS}
        activeTab={activeTab}
        onTabChange={setActiveTab}
      >
        {tabContent}
      </Staff360Layout>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  denied: { flex: 1, justifyContent: 'center' },
});
