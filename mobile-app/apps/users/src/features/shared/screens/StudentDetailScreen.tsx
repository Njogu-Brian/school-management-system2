import {
  useCurrentUser,
  useMedicalRecords,
  useStudentAcademicSummary,
  useStudentAttendanceTrend,
  useStudentDetail,
  useStudentRequirements,
  useStudentStats,
  useTeacherTransportStudents,
  UserRole,
  type StudentDetail,
  type TeacherTransportLeg,
} from '@erp/core';
import {
  Button,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  Soft3DIcon,
  StaffFieldSection,
  Student360Layout,
  StudentStatusBadge,
  StudentSummaryWidgets,
  useTheme,
  type Student360TabId,
  type StudentSummaryWidgetData,
} from '@erp/ui';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

type DetailParams = { studentId: number };
type LooseNav = { navigate: (name: string, params?: object) => void; goBack: () => void; canGoBack: () => boolean };

const STAFF_ROLES = [UserRole.TEACHER, UserRole.SENIOR_TEACHER, UserRole.SUPERVISOR, UserRole.ADMIN];

function fmtDate(value?: string | null): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function fmtPercent(value?: number | null): string {
  return value != null ? `${Number(value).toFixed(1)}%` : '—';
}

/** One transport leg row (morning / evening) — trip, vehicle, own means. */
const TransportLegLine: React.FC<{ label: 'Morning' | 'Evening'; leg?: TeacherTransportLeg | null }> = ({
  label,
  leg,
}) => {
  const { palette, typography, spacing } = useTheme();

  let detail: string;
  let iconName: React.ComponentProps<typeof Soft3DIcon>['name'] = 'bus-outline';
  let tone: React.ComponentProps<typeof Soft3DIcon>['tone'] = 'cyan';

  if (!leg) {
    detail = 'No assignment';
    iconName = 'help-circle-outline';
    tone = 'muted';
  } else if (leg.type === 'own_means') {
    detail = `Own means${leg.reason ? ` · ${leg.reason}` : ''}`;
    iconName = 'walk-outline';
    tone = 'amber';
  } else {
    detail =
      [
        leg.trip_name,
        leg.vehicle_registration,
        leg.departure_time ? `Dep ${leg.departure_time}` : null,
        leg.drop_off_point ? `Drop: ${leg.drop_off_point}` : null,
      ]
        .filter(Boolean)
        .join(' · ') || 'Assigned';
  }

  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.sm }}>
      <Soft3DIcon name={iconName} tone={tone} size={30} />
      <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize, flex: 1 }}>
        <Text style={{ fontWeight: '700', color: palette.textPrimary }}>{label}: </Text>
        {detail}
      </Text>
    </View>
  );
};

const OverviewTab: React.FC<{ student: StudentDetail; attendancePct: number | null | undefined }> = ({
  student,
  attendancePct,
}) => {
  const { spacing, palette } = useTheme();
  const classLabel = [student.className, student.streamName].filter(Boolean).join(' · ') || 'Unassigned';
  const primary = student.guardians.find((g) => g.isPrimary) ?? student.guardians[0];

  const widgets = useMemo(
    (): StudentSummaryWidgetData[] => [
      { id: 'attendance', label: 'Attendance', value: fmtPercent(attendancePct), delta: 'Last 90 days', icon: 'checkmark-circle-outline' },
      {
        id: 'enrollment',
        label: 'Enrollment',
        value: student.enrollmentStatus
          ? student.enrollmentStatus.charAt(0).toUpperCase() + student.enrollmentStatus.slice(1)
          : '—',
        delta: student.category ?? 'Student',
        icon: 'school-outline',
      },
      {
        id: 'fees',
        label: 'Fees',
        value: student.feeStatus === 'pending' ? 'Pending' : 'Cleared',
        icon: 'shield-checkmark-outline',
      },
      {
        id: 'contact',
        label: 'Primary contact',
        value: primary?.name ?? student.parent?.fatherName ?? student.parent?.motherName ?? '—',
        delta: primary?.phone ?? student.parent?.fatherPhone ?? student.parent?.motherPhone ?? undefined,
        icon: 'people-outline',
      },
    ],
    [student, attendancePct, primary],
  );

  return (
    <View>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md }}>
        <StudentStatusBadge kind="enrollment" enrollmentStatus={student.enrollmentStatus} />
        <StudentStatusBadge kind="fee" feeStatus={student.feeStatus} />
      </View>
      <StudentSummaryWidgets widgets={widgets} />
      <View style={{ marginTop: spacing.lg }}>
        <StaffFieldSection
          title="Quick profile"
          rows={[
            { label: 'Class / stream', value: classLabel },
            { label: 'Admission #', value: student.admissionNumber },
            { label: 'Category', value: student.category },
            { label: 'Date of birth', value: fmtDate(student.dateOfBirth) },
            { label: 'Admission date', value: fmtDate(student.admissionDate) },
            { label: 'NEMIS', value: student.nemisNumber },
            { label: 'Phone', value: student.phone },
            { label: 'Email', value: student.email },
            { label: 'Address', value: student.address },
          ]}
        />
      </View>
      <Text style={{ color: palette.textMuted, marginTop: spacing.md, fontSize: 12 }}>
        Fee status is shown as a badge only — balances are not visible here.
      </Text>
    </View>
  );
};

const AttendanceTab: React.FC<{ studentId: number; statsPct?: number | null }> = ({ studentId, statsPct }) => {
  const { colors, spacing } = useTheme();
  const trend = useStudentAttendanceTrend(studentId);

  const widgets = useMemo(
    (): StudentSummaryWidgetData[] => [
      { id: 'p', label: 'Present', value: String(trend.summary.present), icon: 'checkmark-circle-outline' },
      { id: 'a', label: 'Absent', value: String(trend.summary.absent), icon: 'close-circle-outline' },
      { id: 'l', label: 'Late', value: String(trend.summary.late), icon: 'time-outline' },
      { id: 'pct', label: 'Rate (month)', value: fmtPercent(statsPct ?? trend.summary.percentage), icon: 'stats-chart-outline' },
    ],
    [trend.summary, statsPct],
  );

  if (trend.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }
  if (trend.isError) {
    return (
      <EmptyState
        title="Could not load attendance"
        message="Pull to refresh or retry."
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => trend.refetch()}
      />
    );
  }
  return <StudentSummaryWidgets widgets={widgets} />;
};

const AcademicsTab: React.FC<{ studentId: number }> = ({ studentId }) => {
  const { colors, spacing } = useTheme();
  const summaryQuery = useStudentAcademicSummary(studentId);

  if (summaryQuery.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }
  if (summaryQuery.isError || !summaryQuery.data) {
    return (
      <EmptyState
        title="No academic summary"
        message="Assessment results will appear here once marks are recorded."
        icon="school-outline"
      />
    );
  }

  const s = summaryQuery.data;
  const widgets: StudentSummaryWidgetData[] = [
    { id: 'avg', label: 'Exam average', value: fmtPercent(s.examAverage ?? s.latestOverallPercentage), icon: 'stats-chart-outline' },
    { id: 'grade', label: 'Latest grade', value: s.latestOverallGrade ?? '—', icon: 'ribbon-outline' },
    {
      id: 'count',
      label: 'Assessments',
      value: String(s.totalAssessmentCount ?? s.marksRecordedCount ?? 0),
      icon: 'document-text-outline',
    },
  ];
  return <StudentSummaryWidgets widgets={widgets} />;
};

const HealthTab: React.FC<{ student: StudentDetail }> = ({ student }) => {
  const { spacing } = useTheme();
  const recordsQuery = useMedicalRecords(student.id);

  const profileRows = [
    { label: 'Blood group', value: student.bloodGroup ?? '—' },
    { label: 'Preferred hospital', value: student.preferredHospital ?? '—' },
    {
      label: 'Allergies',
      value: student.hasAllergies ? student.allergiesNotes?.trim() || 'Yes (no notes)' : 'None reported',
    },
    {
      label: 'Immunization',
      value:
        student.isFullyImmunized == null
          ? '—'
          : student.isFullyImmunized
            ? 'Fully immunized'
            : 'Not fully immunized',
    },
    { label: 'Emergency contact', value: student.emergencyContact.name ?? '—' },
    { label: 'Emergency phone', value: student.emergencyContact.phone ?? '—' },
  ];

  const records = recordsQuery.data ?? [];
  const recordRows = records.map((r, i) => ({
    label: r.title ?? r.record_type ?? `Record ${i + 1}`,
    value: [r.record_date, r.doctor_name].filter(Boolean).join(' · ') || '—',
  }));

  return (
    <View style={{ gap: spacing.md }}>
      <FinanceFieldSection title="Health profile" rows={profileRows} />
      {recordRows.length > 0 ? <FinanceFieldSection title="Clinic records" rows={recordRows} /> : null}
    </View>
  );
};

const TransportTab: React.FC<{ student: StudentDetail; isStaff: boolean }> = ({ student, isStaff }) => {
  const date = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const rosterQuery = useTeacherTransportStudents({
    date,
    search: student.admissionNumber,
    enabled: isStaff,
  });

  const row = (rosterQuery.data?.students ?? []).find((s) => s.id === student.id);

  if (isStaff && row) {
    return (
      <View>
        <TransportLegLine label="Morning" leg={row.morning} />
        <TransportLegLine label="Evening" leg={row.evening} />
      </View>
    );
  }

  const assignmentRows = [
    { label: 'Trip / route ID', value: student.tripId != null ? String(student.tripId) : '—' },
    { label: 'Drop-off point ID', value: student.dropOffPointId != null ? String(student.dropOffPointId) : '—' },
    { label: 'Drop-off (other)', value: student.dropOffPointOther ?? '—' },
  ];

  if (student.tripId == null && !student.dropOffPointOther) {
    return (
      <EmptyState
        title="No transport assignment"
        message="This student is not linked to a school transport trip."
        icon="bus-outline"
      />
    );
  }
  return <FinanceFieldSection title="Transport assignment" rows={assignmentRows} />;
};

const RequirementsTab: React.FC<{ studentId: number }> = ({ studentId }) => {
  const { colors, spacing } = useTheme();
  const query = useStudentRequirements(studentId);

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
        title="Could not load requirements"
        message={query.error instanceof Error ? query.error.message : 'Try again.'}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }
  const items = query.data?.items ?? [];
  if (items.length === 0) {
    return (
      <EmptyState
        title="No requirements"
        message="No term requirement templates are assigned to this student."
        icon="clipboard-outline"
      />
    );
  }
  const rows = items.map((item) => ({
    label: item.name,
    value: `${item.quantity_collected}/${item.quantity_required} ${item.unit ?? ''} · ${item.status}`.trim(),
  }));
  return <FinanceFieldSection title="Requirements checklist" rows={rows} />;
};

const FamilyTab: React.FC<{ student: StudentDetail }> = ({ student }) => {
  const { spacing } = useTheme();
  const { parent, guardians, emergencyContact } = student;

  const parentRows = [
    { label: 'Father', value: [parent?.fatherName, parent?.fatherPhone].filter(Boolean).join(' · ') || '—' },
    { label: 'Mother', value: [parent?.motherName, parent?.motherPhone].filter(Boolean).join(' · ') || '—' },
    { label: 'Guardian', value: [parent?.guardianName, parent?.guardianPhone].filter(Boolean).join(' · ') || '—' },
    { label: 'Emergency', value: [emergencyContact.name, emergencyContact.phone].filter(Boolean).join(' · ') || '—' },
  ];
  const guardianRows = guardians.map((g) => ({
    label: `${g.relationship}${g.isPrimary ? ' · primary' : ''}`,
    value: [g.name, g.phone].filter(Boolean).join(' · ') || '—',
  }));

  return (
    <View style={{ gap: spacing.md }}>
      <FinanceFieldSection title="Parents" rows={parentRows} />
      {guardianRows.length > 0 ? <FinanceFieldSection title="Contacts" rows={guardianRows} /> : null}
    </View>
  );
};

const BASE_TABS: Array<{ id: Student360TabId; label: string }> = [
  { id: 'overview', label: 'Overview' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'academics', label: 'Academics' },
  { id: 'health', label: 'Health' },
  { id: 'transport', label: 'Transport' },
  { id: 'requirements', label: 'Requirements' },
  { id: 'family', label: 'Family' },
];

/**
 * Shared student profile — used by Teacher (class / subject-teacher scope) and
 * reachable from Parent stacks. Pastoral 360 view: never renders fee balances or
 * amounts, only the cleared/pending badge.
 */
export const StudentDetailScreen: React.FC = () => {
  const navigation = useNavigation() as unknown as LooseNav;
  const route = useRoute();
  const user = useCurrentUser();
  const { colors, spacing } = useTheme();
  const studentId = (route.params as DetailParams | undefined)?.studentId ?? 0;

  const [activeTab, setActiveTab] = useState<Student360TabId>('overview');

  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const stats = useStudentStats(studentId, { enabled: studentId > 0 });

  const isStaff = user?.role != null && STAFF_ROLES.includes(user.role as UserRole);
  const student = detail.data;

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

  if (studentId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <EmptyState title="Missing student" message="No student was selected." icon="alert-circle-outline" />
      </ScreenContainer>
    );
  }

  if (detail.isLoading && !student) {
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
          title="Could not load"
          message={detail.error instanceof Error ? detail.error.message : 'This student may no longer be available.'}
          icon="person-outline"
          actionLabel={detail.isError ? 'Retry' : undefined}
          onAction={detail.isError ? () => void detail.refetch() : undefined}
        />
      </ScreenContainer>
    );
  }

  const tabContent = (() => {
    switch (activeTab) {
      case 'overview':
        return <OverviewTab student={student} attendancePct={stats.data?.attendance_percentage} />;
      case 'attendance':
        return <AttendanceTab studentId={studentId} statsPct={stats.data?.attendance_percentage} />;
      case 'academics':
        return <AcademicsTab studentId={studentId} />;
      case 'health':
        return <HealthTab student={student} />;
      case 'transport':
        return <TransportTab student={student} isStaff={isStaff} />;
      case 'requirements':
        return <RequirementsTab studentId={studentId} />;
      case 'family':
        return <FamilyTab student={student} />;
      default:
        return null;
    }
  })();

  return (
    <ScreenContainer scroll={false} style={styles.flex}>
      <Student360Layout
        header={header}
        tabs={BASE_TABS}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
      >
        <View style={{ marginBottom: spacing.md }}>
          <Button
            label="Open diary"
            variant="secondary"
            onPress={() => navigation.navigate('DiaryChat', { studentId, studentName: student.fullName })}
          />
        </View>
        {tabContent}
      </Student360Layout>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
