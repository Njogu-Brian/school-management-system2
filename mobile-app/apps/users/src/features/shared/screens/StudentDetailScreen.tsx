import {
  buildPerformanceTrend,
  buildSubjectProgress,
  computeTrendDelta,
  downloadAuthenticatedFile,
  progressDirection,
  useAppMode,
  useCurrentUser,
  useMedicalRecords,
  useStudentAcademicSummary,
  useStudentAssessmentHistory,
  useStudentAttendanceCalendar,
  useStudentAttendanceTrend,
  useStudentDetail,
  useStudentDocuments,
  useStudentRequirements,
  useStudentStatement,
  useStudentStats,
  useTeacherTransportStudents,
  UserRole,
  type StudentDetail,
  type StudentTransportLeg,
  type TeacherTransportLeg,
} from '@erp/core';
import {
  AttendanceMonthCalendar,
  Button,
  EmptyState,
  FinanceFieldSection,
  formatLearnerInterests,
  formatOrphanStatus,
  ProgressTrendPanel,
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
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Image, Pressable, StyleSheet, Text, View } from 'react-native';

type DetailParams = { studentId: number; tab?: Student360TabId };
type LooseNav = {
  navigate: (name: string, params?: object) => void;
  goBack: () => void;
  canGoBack: () => boolean;
  setParams: (params: object) => void;
};

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

const OverviewTab: React.FC<{
  student: StudentDetail;
  attendancePct: number | null | undefined;
  onWidgetPress?: (widgetId: string) => void;
  /** Teachers must not see fee widgets on pastoral profiles. */
  showFees?: boolean;
}> = ({ student, attendancePct, onWidgetPress, showFees = false }) => {
  const { spacing, palette } = useTheme();
  const classLabel = [student.className, student.streamName].filter(Boolean).join(' · ') || 'Unassigned';
  const primary = student.guardians.find((g) => g.isPrimary) ?? student.guardians[0];

  const widgets = useMemo((): StudentSummaryWidgetData[] => {
    const list: StudentSummaryWidgetData[] = [
      {
        id: 'attendance',
        label: 'Attendance',
        value: fmtPercent(attendancePct),
        delta: 'Tap for calendar',
        icon: 'checkmark-circle-outline',
      },
      {
        id: 'enrollment',
        label: 'Enrollment',
        value: student.enrollmentStatus
          ? student.enrollmentStatus.charAt(0).toUpperCase() + student.enrollmentStatus.slice(1)
          : '—',
        delta: student.category ?? 'Student',
        icon: 'school-outline',
      },
    ];
    if (showFees) {
      list.push({
        id: 'fees',
        label: 'Fees',
        value: student.feeStatus === 'pending' ? 'Pending' : 'Cleared',
        delta: 'Tap for fees',
        icon: 'shield-checkmark-outline',
      });
    }
    list.push({
      id: 'contact',
      label: 'Primary contact',
      value: primary?.name ?? student.parent?.fatherName ?? student.parent?.motherName ?? '—',
      delta: primary?.phone ?? student.parent?.fatherPhone ?? student.parent?.motherPhone ?? undefined,
      icon: 'people-outline',
    });
    return list;
  }, [student, attendancePct, primary, showFees]);

  return (
    <View>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md }}>
        <StudentStatusBadge kind="enrollment" enrollmentStatus={student.enrollmentStatus} />
        {showFees ? <StudentStatusBadge kind="fee" feeStatus={student.feeStatus} /> : null}
      </View>
      <StudentSummaryWidgets widgets={widgets} onWidgetPress={onWidgetPress} />
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
            { label: 'Religion', value: student.religion },
            { label: 'Nationality', value: student.nationality },
            { label: 'County of birth', value: student.countyOfBirth },
            { label: 'Birth certificate entry no.', value: student.birthCertificateEntryNo },
            { label: 'Orphan status', value: formatOrphanStatus(student.orphanStatus) },
            { label: 'Learner interests', value: formatLearnerInterests(student.learnerInterests) },
            { label: 'Phone', value: student.phone },
            { label: 'Email', value: student.email },
            { label: 'Address', value: student.address },
          ]}
        />
      </View>
      <Text style={{ color: palette.textMuted, marginTop: spacing.md, fontSize: 12 }}>
        Tap Attendance to open this child’s calendar. Back returns you to the tab you were on.
      </Text>
    </View>
  );
};

const AttendanceTab: React.FC<{
  studentId: number;
  statsPct?: number | null;
  isParent?: boolean;
}> = ({ studentId, statsPct, isParent }) => {
  const { colors, spacing, palette, typography, radius } = useTheme();
  const navigation = useNavigation<LooseNav>();
  const trend = useStudentAttendanceTrend(studentId);
  const now = useMemo(() => new Date(), []);
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const calendar = useStudentAttendanceCalendar(studentId, year, month, {
    enabled: studentId > 0,
  });

  const widgets = useMemo(
    (): StudentSummaryWidgetData[] => [
      { id: 'p', label: 'Present', value: String(trend.summary.present), icon: 'checkmark-circle-outline' },
      { id: 'a', label: 'Absent', value: String(trend.summary.absent), icon: 'close-circle-outline' },
      { id: 'l', label: 'Late', value: String(trend.summary.late), icon: 'time-outline' },
      { id: 'pct', label: 'Rate (month)', value: fmtPercent(statsPct ?? trend.summary.percentage), icon: 'stats-chart-outline' },
    ],
    [trend.summary, statsPct],
  );

  const shiftMonth = (delta: number) => {
    const d = new Date(year, month - 1 + delta, 1);
    setYear(d.getFullYear());
    setMonth(d.getMonth() + 1);
    setSelectedDate(null);
  };

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

  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />
      {isParent ? (
        <Button
          label="Report absence"
          variant="secondary"
          style={{ marginTop: spacing.md, marginBottom: spacing.sm }}
          onPress={() => navigation.navigate('ReportAbsence', { studentId })}
        />
      ) : null}
      <Text
        style={{
          color: palette.textSub,
          fontSize: typography.overline.fontSize,
          letterSpacing: typography.overline.letterSpacing,
          fontWeight: '700',
          textTransform: 'uppercase',
          marginTop: spacing.lg,
          marginBottom: spacing.sm,
        }}
      >
        Attendance calendar
      </Text>
      {calendar.isLoading ? (
        <ActivityIndicator color={colors.primary} style={{ marginVertical: spacing.md }} />
      ) : calendar.isError ? (
        <EmptyState
          title="Could not load calendar"
          message={calendar.error instanceof Error ? calendar.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void calendar.refetch()}
        />
      ) : (
        <AttendanceMonthCalendar
          year={year}
          month={month}
          days={calendar.data ?? []}
          selectedDate={selectedDate}
          onSelectDate={setSelectedDate}
          onShiftMonth={shiftMonth}
        />
      )}
      {trend.trend.length > 0 ? (
        <Text
          style={{
            color: palette.textSub,
            fontSize: typography.overline.fontSize,
            fontWeight: '700',
            textTransform: 'uppercase',
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          }}
        >
          Weekly trend
        </Text>
      ) : null}
      {trend.trend.map((point) => (
        <View
          key={point.label}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            marginBottom: spacing.sm,
            gap: spacing.sm,
          }}
        >
          <Text style={{ width: 56, color: palette.textSub, fontSize: typography.caption.fontSize }}>
            {point.label}
          </Text>
          <View
            style={{
              flex: 1,
              height: 8,
              borderRadius: radius.full,
              backgroundColor: palette.surfaceMuted,
              overflow: 'hidden',
            }}
          >
            <View
              style={{
                width: `${Math.min(100, point.present + point.absent + point.late > 0 ? (point.present / (point.present + point.absent + point.late)) * 100 : 0)}%`,
                height: '100%',
                backgroundColor: colors.primary,
              }}
            />
          </View>
          <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize }}>
            P{point.present} A{point.absent} L{point.late}
          </Text>
        </View>
      ))}
    </View>
  );
};

const FeesTab: React.FC<{ studentId: number; isParent: boolean; feeStatus?: string | null }> = ({
  studentId,
  isParent,
  feeStatus,
}) => {
  const { colors, spacing } = useTheme();
  const navigation = useNavigation<LooseNav>();
  const statement = useStudentStatement(studentId, { detailed: true }, { enabled: isParent && studentId > 0 });

  if (!isParent) {
    return (
      <View style={{ gap: spacing.md }}>
        <FinanceFieldSection
          title="Fee status"
          rows={[
            {
              label: 'Status',
              value: feeStatus === 'pending' ? 'Pending' : feeStatus === 'cleared' ? 'Cleared' : '—',
            },
          ]}
        />
        <EmptyState
          title="Balances hidden"
          message="Teachers see fee clearance status only. Open the admin app for full fee details."
          icon="lock-closed-outline"
        />
      </View>
    );
  }

  if (statement.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  const s = statement.data;
  return (
    <View style={{ gap: spacing.md }}>
      <FinanceFieldSection
        title="Fees summary"
        rows={[
          {
            label: 'Balance',
            value: s?.closing_balance != null ? `KES ${Number(s.closing_balance).toLocaleString()}` : '—',
          },
          {
            label: 'Invoiced',
            value: s?.total_invoiced != null ? `KES ${Number(s.total_invoiced).toLocaleString()}` : '—',
          },
          {
            label: 'Paid',
            value: s?.total_paid != null ? `KES ${Number(s.total_paid).toLocaleString()}` : '—',
          },
        ]}
      />
      <Button
        label="Full fee statement"
        onPress={() => navigation.navigate('StudentStatement', { studentId })}
      />
    </View>
  );
};

const DocumentsTab: React.FC<{ studentId: number }> = ({ studentId }) => {
  const { colors, spacing, palette, typography, radius } = useTheme();
  const query = useStudentDocuments(studentId);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

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
        title="Could not load documents"
        message={query.error instanceof Error ? query.error.message : 'Try again.'}
        icon="alert-circle-outline"
        actionLabel="Retry"
        onAction={() => void query.refetch()}
      />
    );
  }
  const docs = query.data ?? [];
  if (docs.length === 0) {
    return (
      <EmptyState
        title="No documents"
        message="No documents are attached to this student profile yet."
        icon="document-text-outline"
      />
    );
  }

  return (
    <View style={{ gap: spacing.sm }}>
      {docs.map((doc) => (
        <Pressable
          key={doc.id}
          onPress={() => {
            if (!doc.download_path) return;
            setDownloadingId(doc.id);
            void downloadAuthenticatedFile(doc.download_path, doc.title)
              .catch((err) => Alert.alert('Download failed', (err as Error).message))
              .finally(() => setDownloadingId(null));
          }}
          style={{
            padding: spacing.md,
            borderRadius: radius.md,
            borderWidth: StyleSheet.hairlineWidth,
            borderColor: palette.borderSubtle,
            backgroundColor: palette.surfaceRaised,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
            {doc.title}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: 4, fontSize: typography.caption.fontSize }}>
            {downloadingId === doc.id ? 'Downloading…' : doc.download_path ? 'Tap to download' : 'No file'}
          </Text>
        </Pressable>
      ))}
    </View>
  );
};

const AcademicsTab: React.FC<{ studentId: number; studentName?: string; isStaff?: boolean }> = ({
  studentId,
  studentName,
  isStaff,
}) => {
  const { colors, spacing, palette, typography } = useTheme();
  const navigation = useNavigation<LooseNav>();
  const summaryQuery = useStudentAcademicSummary(studentId);
  const historyQuery = useStudentAssessmentHistory(studentId, { category: 'all' });
  useEffect(() => {
    if (historyQuery.hasNextPage && !historyQuery.isFetchingNextPage) {
      void historyQuery.fetchNextPage();
    }
  }, [historyQuery.hasNextPage, historyQuery.isFetchingNextPage, historyQuery.fetchNextPage]);
  const historyItems = useMemo(
    () => historyQuery.data?.pages.flatMap((p) => p.rows) ?? [],
    [historyQuery.data],
  );
  const overallPoints = useMemo(() => buildPerformanceTrend(historyItems), [historyItems]);
  const overallDelta = useMemo(() => computeTrendDelta(overallPoints), [overallPoints]);
  const overallDirection = progressDirection(overallDelta);
  const subjectSeries = useMemo(() => buildSubjectProgress(historyItems), [historyItems]);

  const reportFormsButton =
    isStaff ? (
      <Button
        label="View report forms"
        variant="secondary"
        style={{ marginTop: spacing.md }}
        onPress={() =>
          navigation.navigate('StudentReportCards', {
            studentId,
            studentName,
          })
        }
      />
    ) : null;

  if (summaryQuery.isLoading) {
    return (
      <View style={{ paddingVertical: spacing.xl, alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }
  if (summaryQuery.isError || !summaryQuery.data) {
    return (
      <View>
        <EmptyState
          title="No academic summary"
          message="Assessment results will appear here once marks are recorded."
          icon="school-outline"
        />
        {reportFormsButton}
      </View>
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
  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />
      <ProgressTrendPanel
        title="Overall progress"
        subtitle="Recent assessments / report cards"
        points={overallPoints.map((p) => ({ label: p.label, percentage: p.percentage }))}
        direction={overallDirection}
        delta={overallDelta}
      />
      {subjectSeries.length > 0 ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontWeight: '700',
            fontSize: typography.caption.fontSize,
            marginBottom: spacing.xs,
            marginTop: spacing.sm,
            textTransform: 'uppercase',
            letterSpacing: 0.4,
          }}
        >
          Per subject
        </Text>
      ) : null}
      {subjectSeries.map((row) => (
        <ProgressTrendPanel
          key={row.subjectId}
          title={row.subjectName}
          subtitle={row.latestPercent != null ? `Latest ${row.latestPercent.toFixed(0)}%` : undefined}
          points={row.points.map((p) => ({ label: p.label, percentage: p.percentage }))}
          direction={row.direction}
          delta={row.delta}
        />
      ))}
      {reportFormsButton}
    </View>
  );
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

  const morning = student.transportMorning;
  const evening = student.transportEvening;
  const hasAssignment = Boolean(
    morning?.tripName ||
      evening?.tripName ||
      student.tripName ||
      student.dropOffPointName ||
      student.dropOffPointOther ||
      (student.transportSummary && student.transportSummary !== 'No transport assigned'),
  );

  const assignmentRows: Array<{ label: string; value: string }> = [];
  const pushLeg = (label: string, leg: StudentTransportLeg | null) => {
    if (!leg?.tripName && !leg?.dropOffPoint && !leg?.vehicle) return;
    assignmentRows.push({ label: `${label} trip`, value: leg.tripName ?? '—' });
    assignmentRows.push({ label: `${label} vehicle`, value: leg.vehicle ?? '—' });
    assignmentRows.push({ label: `${label} drop-off`, value: leg.dropOffPoint ?? '—' });
    if (leg.driverName) assignmentRows.push({ label: `${label} driver`, value: leg.driverName });
  };
  pushLeg('Morning', morning);
  pushLeg('Evening', evening);
  if (assignmentRows.length === 0 && student.tripName) {
    assignmentRows.push({ label: 'Trip', value: student.tripName });
    assignmentRows.push({ label: 'Drop-off', value: student.dropOffPointName ?? student.dropOffPointOther ?? '—' });
  } else if (student.dropOffPointOther) {
    assignmentRows.push({ label: 'Notes', value: student.dropOffPointOther });
  }

  if (!hasAssignment) {
    return (
      <EmptyState
        title="No transport assignment"
        message="This student is not linked to a school transport trip."
        icon="bus-outline"
      />
    );
  }

  const photoUrl = morning?.vehiclePhotoUrl || evening?.vehiclePhotoUrl;
  return (
    <View>
      {photoUrl ? (
        <Image
          source={{ uri: photoUrl }}
          style={{ width: '100%', height: 140, borderRadius: 12, marginBottom: 12 }}
        />
      ) : null}
      <FinanceFieldSection title="Transport assignment" rows={assignmentRows} />
    </View>
  );
};

const RequirementsTab: React.FC<{ studentId: number; canCollect?: boolean }> = ({ studentId, canCollect }) => {
  const { colors, spacing } = useTheme();
  const navigation = useNavigation<LooseNav>();
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
  return (
    <View style={{ gap: spacing.md }}>
      <FinanceFieldSection title="Requirements checklist" rows={rows} />
      {canCollect ? (
        <Button
          label="Collect requirements"
          onPress={() => navigation.navigate('RequirementDetail', { studentId })}
        />
      ) : null}
    </View>
  );
};

const FamilyTab: React.FC<{ student: StudentDetail; onOpenSibling?: (id: number) => void }> = ({
  student,
  onOpenSibling,
}) => {
  const { spacing, palette, typography, colors } = useTheme();
  const { parent, guardians, emergencyContact, siblings } = student;

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
  const siblingRows = (siblings ?? []).map((s) => ({
    label: s.fullName,
    value: [s.admissionNumber, s.className, s.streamName].filter(Boolean).join(' · ') || 'Enrolled',
    id: s.id,
  }));

  return (
    <View style={{ gap: spacing.md }}>
      <FinanceFieldSection title="Parents" rows={parentRows} />
      {siblingRows.length > 0 ? (
        <View>
          <Text
            style={{
              color: palette.textSub,
              fontSize: typography.overline?.fontSize ?? 11,
              fontWeight: '700',
              textTransform: 'uppercase',
              marginBottom: spacing.sm,
            }}
          >
            Siblings
          </Text>
          {siblingRows.map((row) => (
            <Pressable
              key={row.id}
              onPress={() => onOpenSibling?.(row.id)}
              disabled={!onOpenSibling}
              style={{ paddingVertical: spacing.sm }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{row.label}</Text>
              <Text style={{ color: colors.primary, marginTop: 2 }}>{row.value}</Text>
            </Pressable>
          ))}
        </View>
      ) : (
        <FinanceFieldSection title="Siblings" rows={[{ label: 'Linked siblings', value: 'None on file' }]} />
      )}
      {guardianRows.length > 0 ? <FinanceFieldSection title="Contacts" rows={guardianRows} /> : null}
    </View>
  );
};

const BASE_TABS: Array<{ id: Student360TabId; label: string }> = [
  { id: 'overview', label: 'Overview' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'fees', label: 'Fees' },
  { id: 'academics', label: 'Academic' },
  { id: 'family', label: 'Family' },
  { id: 'transport', label: 'Transport' },
  { id: 'requirements', label: 'Requirements' },
  { id: 'documents', label: 'Documents' },
  { id: 'health', label: 'Health' },
];

/**
 * Shared student profile — Teacher / Parent / Edulynk.
 * Tabs stay in route params so back from nested screens restores the same section.
 */
export const StudentDetailScreen: React.FC = () => {
  const navigation = useNavigation() as unknown as LooseNav;
  const route = useRoute();
  const user = useCurrentUser();
  const { mode } = useAppMode();
  const { colors, spacing } = useTheme();
  const params = (route.params as DetailParams | undefined) ?? { studentId: 0 };
  const studentId = params.studentId ?? 0;

  const [activeTab, setActiveTab] = useState<Student360TabId>(params.tab ?? 'overview');

  useEffect(() => {
    if (params.tab) {
      setActiveTab(params.tab);
    }
  }, [params.tab]);

  const handleTabChange = useCallback(
    (tab: Student360TabId) => {
      setActiveTab(tab);
      navigation.setParams({ tab });
    },
    [navigation],
  );

  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const stats = useStudentStats(studentId, { enabled: studentId > 0 });

  const isStaff = user?.role != null && STAFF_ROLES.includes(user.role as UserRole);
  // Fees only in parent/Home context — never for teachers in Work mode.
  const showFeesTab = mode === 'home' || (!isStaff && (user?.role === UserRole.PARENT || user?.role === UserRole.GUARDIAN));
  const showParentFeatures = showFeesTab;

  const handleOverviewWidgetPress = useCallback(
    (widgetId: string) => {
      const map: Record<string, Student360TabId> = {
        attendance: 'attendance',
        contact: 'family',
      };
      if (showFeesTab) {
        map.fees = 'fees';
      }
      const next = map[widgetId];
      if (next) handleTabChange(next);
    },
    [handleTabChange, showFeesTab],
  );

  const tabs = useMemo(
    () => (showFeesTab ? BASE_TABS : BASE_TABS.filter((t) => t.id !== 'fees')),
    [showFeesTab],
  );

  useEffect(() => {
    if (!showFeesTab && activeTab === 'fees') {
      setActiveTab('overview');
      navigation.setParams({ tab: 'overview' });
    }
  }, [showFeesTab, activeTab, navigation]);

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
        return (
          <OverviewTab
            student={student}
            attendancePct={stats.data?.attendance_percentage}
            onWidgetPress={handleOverviewWidgetPress}
            showFees={showFeesTab}
          />
        );
      case 'attendance':
        return (
          <AttendanceTab
            studentId={studentId}
            statsPct={stats.data?.attendance_percentage}
            isParent={showParentFeatures}
          />
        );
      case 'fees':
        return (
          <FeesTab
            studentId={studentId}
            isParent={showParentFeatures}
            feeStatus={student.feeStatus}
          />
        );
      case 'academics':
        return (
          <AcademicsTab studentId={studentId} studentName={student.fullName} isStaff={isStaff} />
        );
      case 'family':
        return (
          <FamilyTab
            student={student}
            onOpenSibling={(id) => navigation.navigate('StudentDetail', { studentId: id })}
          />
        );
      case 'transport':
        return <TransportTab student={student} isStaff={isStaff} />;
      case 'requirements':
        return <RequirementsTab studentId={studentId} canCollect={isStaff} />;
      case 'documents':
        return <DocumentsTab studentId={studentId} />;
      case 'health':
        return <HealthTab student={student} />;
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
        onTabChange={handleTabChange}
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
