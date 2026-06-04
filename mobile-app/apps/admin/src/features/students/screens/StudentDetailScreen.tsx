import {
  useCan,
  useStudentAttendanceTrend,
  useStudentDetail,
  useStudentStatement,
  useStudentStats,
  type StudentDetail,
  type StudentSummary,
} from '@erp/core';
import { ScreenContainer, Student360Layout, type Student360TabId } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '@erp/ui';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { AttendanceTab } from '../student360/tabs/AttendanceTab';
import { FamilyTab } from '../student360/tabs/FamilyTab';
import { FeesTab } from '../student360/tabs/FeesTab';
import { OverviewTab } from '../student360/tabs/OverviewTab';

type Props = StackScreenProps<StudentsStackParamList, 'StudentDetail'>;

const TABS: Array<{ id: Student360TabId; label: string }> = [
  { id: 'overview', label: 'Overview' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'fees', label: 'Fees' },
  { id: 'family', label: 'Family' },
];

function summaryAsDetail(summary: StudentSummary): StudentDetail {
  return {
    ...summary,
    dateOfBirth: null,
    phone: null,
    email: null,
    admissionDate: null,
    enrollmentYear: null,
    address: null,
    category: null,
    nemisNumber: null,
    outstandingBalance: null,
    parent: null,
    guardians: [],
    emergencyContact: { name: null, phone: null },
  };
}

export const StudentDetailScreen: React.FC<Props> = ({ route }) => {
  const { studentId, summary } = route.params;
  const canViewFees = useCan('finance.view');
  const { colors, spacing } = useTheme();
  const [activeTab, setActiveTab] = useState<Student360TabId>('overview');

  const detailQuery = useStudentDetail(studentId);
  const statsQuery = useStudentStats(studentId);
  const statementQuery = useStudentStatement(studentId, undefined, { enabled: canViewFees });
  const attendanceQuery = useStudentAttendanceTrend(studentId, {
    enabled: activeTab === 'attendance' || activeTab === 'overview',
  });

  const student = detailQuery.data ?? (summary ? summaryAsDetail(summary) : undefined);

  const header = useMemo(() => {
    if (!student) return null;
    const classLabel = [student.className, student.streamName].filter(Boolean).join(' · ') || '—';
    return {
      fullName: student.fullName,
      admissionNumber: student.admissionNumber,
      classLabel,
      avatarUrl: student.avatarUrl,
      enrollmentStatus: student.enrollmentStatus,
      feeStatus: student.feeStatus,
    };
  }, [student]);

  const statement = statementQuery.data;
  const invoices = useMemo(
    () =>
      (statement?.transactions ?? [])
        .filter((t) => t.type === 'invoice')
        .map((t) => ({
          id: t.id,
          date: t.date,
          reference: t.reference,
          amount: t.debit,
        })),
    [statement],
  );
  const payments = useMemo(
    () =>
      (statement?.transactions ?? [])
        .filter((t) => t.type === 'payment')
        .map((t) => ({
          id: t.id,
          date: t.date,
          reference: t.reference,
          amount: t.credit,
        })),
    [statement],
  );

  if (detailQuery.isLoading && !student) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (!student || !header) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error }}>Student not found.</Text>
        {detailQuery.isError ? (
          <Pressable onPress={() => void detailQuery.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
          </Pressable>
        ) : null}
      </ScreenContainer>
    );
  }

  const tabContent = (() => {
    switch (activeTab) {
      case 'overview':
        return (
          <OverviewTab
            student={student}
            attendancePct={statsQuery.data?.attendance_percentage}
            feeBalance={statsQuery.data?.fees_balance}
            canViewFees={canViewFees}
            statementLoading={statementQuery.isLoading}
            statement={statement ?? null}
          />
        );
      case 'attendance':
        return (
          <AttendanceTab
            isLoading={attendanceQuery.isLoading}
            isError={attendanceQuery.isError}
            onRetry={attendanceQuery.refetch}
            present={attendanceQuery.summary.present}
            absent={attendanceQuery.summary.absent}
            late={attendanceQuery.summary.late}
            percentage={
              statsQuery.data?.attendance_percentage ?? attendanceQuery.summary.percentage
            }
            trend={attendanceQuery.trend}
          />
        );
      case 'fees':
        return (
          <FeesTab
            canViewFees={canViewFees}
            isLoading={statementQuery.isLoading}
            isError={statementQuery.isError}
            onRetry={() => void statementQuery.refetch()}
            closingBalance={statement?.closing_balance}
            totalInvoiced={statement?.total_invoiced}
            totalPaid={statement?.total_paid}
            invoices={invoices}
            payments={payments}
          />
        );
      case 'family':
        return <FamilyTab student={student} />;
      default:
        return null;
    }
  })();

  return (
    <ScreenContainer style={styles.flex}>
      <Student360Layout
        header={header}
        tabs={TABS}
        activeTab={activeTab}
        onTabChange={setActiveTab}
      >
        {tabContent}
      </Student360Layout>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
