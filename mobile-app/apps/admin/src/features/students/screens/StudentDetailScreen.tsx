import {
  useCan,
  useInfiniteInvoiceList,
  useInfinitePaymentList,
  useStudentAttendanceTrend,
  useStudentDetail,
  useStudentStatement,
  useStudentStats,
  type StudentDetail,
  type StudentSummary,
} from '@erp/core';
import type { StackScreenProps } from '@react-navigation/stack';
import {
  EmptyState,
  ScreenContainer,
  Student360Layout,
  type Student360TabId,
  useTheme,
} from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { navigateToTab } from '../../../navigation/navigateWorkspace';
import { AttendanceTab } from '../student360/tabs/AttendanceTab';
import { DocumentsTab } from '../student360/tabs/DocumentsTab';
import { FamilyTab } from '../student360/tabs/FamilyTab';
import { FeesTab } from '../student360/tabs/FeesTab';
import { HealthTab } from '../student360/tabs/HealthTab';
import { RequirementsTab } from '../student360/tabs/RequirementsTab';
import { TransportTab } from '../student360/tabs/TransportTab';
import { AcademicsTab } from '../student360/tabs/AcademicsTab';
import { OverviewTab } from '../student360/tabs/OverviewTab';

type Props = StackScreenProps<StudentsStackParamList, 'StudentDetail'>;

const BASE_TABS: Array<{ id: Student360TabId; label: string }> = [
  { id: 'overview', label: 'Overview' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'health', label: 'Health' },
  { id: 'transport', label: 'Transport' },
  { id: 'requirements', label: 'Requirements' },
  { id: 'documents', label: 'Documents' },
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
    tripId: null,
    dropOffPointId: null,
    dropOffPointOther: null,
    preferredHospital: null,
    hasAllergies: false,
    allergiesNotes: null,
    isFullyImmunized: null,
    bloodGroup: null,
  };
}

export const StudentDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { studentId, summary, tab: initialTab } = route.params;
  const canViewFees = useCan('finance.view');
  const canViewAcademics = useCan('academics.view');
  const { colors, spacing } = useTheme();
  const [activeTab, setActiveTab] = useState<Student360TabId>(initialTab ?? 'overview');

  const detailQuery = useStudentDetail(studentId);
  const statsQuery = useStudentStats(studentId);
  const statementQuery = useStudentStatement(
    studentId,
    { detailed: true },
    { enabled: canViewFees },
  );
  const invoicesQuery = useInfiniteInvoiceList(
    { student_id: studentId, per_page: 25 },
    { enabled: canViewFees && activeTab === 'fees' },
  );
  const paymentsQuery = useInfinitePaymentList(
    { student_id: studentId, per_page: 25, active_only: true },
    { enabled: canViewFees && activeTab === 'fees' },
  );
  const attendanceQuery = useStudentAttendanceTrend(studentId, {
    enabled: activeTab === 'attendance' || activeTab === 'overview',
  });

  const tabs = useMemo(() => {
    const list = [...BASE_TABS];
    if (canViewAcademics) {
      const healthIndex = list.findIndex((t) => t.id === 'health');
      list.splice(healthIndex >= 0 ? healthIndex : 2, 0, { id: 'academics', label: 'Academics' });
    }
    if (!canViewFees) {
      return list.filter((t) => t.id !== 'fees');
    }
    return list;
  }, [canViewAcademics, canViewFees]);

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
      (invoicesQuery.data?.pages.flatMap((p) => p.items) ?? []).map((inv) => ({
        id: inv.id,
        date: inv.issueDate,
        reference: inv.invoiceNumber,
        amount: inv.totalAmount,
      })),
    [invoicesQuery.data],
  );
  const payments = useMemo(
    () =>
      (paymentsQuery.data?.pages.flatMap((p) => p.items) ?? []).map((p) => ({
        id: p.id,
        date: p.paymentDate,
        reference: p.receiptNumber,
        amount: p.amount,
      })),
    [paymentsQuery.data],
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
        <EmptyState
          title="Student not found"
          message={
            detailQuery.isError
              ? (detailQuery.error as Error).message
              : 'This student may have been removed or you no longer have access.'
          }
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
      case 'academics':
        return (
          <AcademicsTab
            studentId={studentId}
            onOpenReportCard={(reportCardId) =>
              navigation.navigate('ReportCardDetail', {
                reportCardId,
                studentName: student.fullName,
              })
            }
          />
        );
      case 'fees':
        return (
          <FeesTab
            canViewFees={canViewFees}
            isLoading={invoicesQuery.isLoading || paymentsQuery.isLoading || statementQuery.isLoading}
            isError={invoicesQuery.isError || paymentsQuery.isError || statementQuery.isError}
            onRetry={() => {
              void invoicesQuery.refetch();
              void paymentsQuery.refetch();
              void statementQuery.refetch();
            }}
            closingBalance={statement?.closing_balance}
            totalInvoiced={statement?.total_invoiced}
            totalPaid={statement?.total_paid}
            invoices={invoices}
            payments={payments}
            onInvoicePress={(invoiceId) =>
              navigateToTab(navigation, 'Finance', 'InvoiceDetail', { invoiceId })
            }
            onPaymentPress={(paymentId) =>
              navigateToTab(navigation, 'Finance', 'PaymentDetail', { paymentId })
            }
          />
        );
      case 'family':
        return <FamilyTab student={student} />;
      case 'health':
        return <HealthTab student={student} />;
      case 'transport':
        return <TransportTab student={student} />;
      case 'requirements':
        return <RequirementsTab studentId={studentId} />;
      case 'documents':
        return <DocumentsTab studentId={studentId} />;
      default:
        return null;
    }
  })();

  return (
    <ScreenContainer scroll={false} style={styles.flex}>
      <Student360Layout
        header={header}
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        onBack={() => navigation.goBack()}
      >
        <Pressable
          onPress={() => navigation.navigate('StudentEdit', { studentId })}
          style={{ alignSelf: 'flex-end', marginBottom: spacing.sm }}
          accessibilityRole="button"
          accessibilityLabel="Edit profile"
        >
          <Text style={{ color: colors.primary, fontWeight: '700' }}>Edit profile</Text>
        </Pressable>
        {tabContent}
      </Student360Layout>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
