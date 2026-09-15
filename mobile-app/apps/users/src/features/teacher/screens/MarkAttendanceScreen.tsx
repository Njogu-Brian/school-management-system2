import {
  attendanceApi,
  attendanceDraftKey,
  clearDraft as deleteDraftKey,
  queueOrExecute,
  studentsApi,
  SYNC_KINDS,
  useAttendanceReasonCodes,
  useClassrooms,
  useCurrentUser,
  useMarkAttendance,
  useNetworkStatus,
  useOfflineDraft,
  UserRole,
  type AttendanceMarkStatus,
  type AttendanceReasonFields,
} from '@erp/core';
import {
  AcademicScreenHeader,
  AttendanceReasonSheet,
  AttendanceSubmitDialog,
  attendanceReasonKey,
  attendanceReasonLabel,
  Button,
  DatePickerField,
  DockedActionLayout,
  EmptyState,
  FilterChip,
  FilterChipRow,
  FooterDock,
  ScreenContainer,
  SkeletonListRows,
  summarizeAttendanceMarks,
  useAdaptiveLayout,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

type StudentRow = { id: number; name: string; admission: string };

type AttendanceDraft = {
  statusById: Record<number, AttendanceMarkStatus>;
  reasonById?: Record<number, AttendanceReasonFields>;
  serverSnapshot: Record<number, string>;
  serverReasonById?: Record<number, AttendanceReasonFields>;
};

const STATUS_OPTIONS: AttendanceMarkStatus[] = ['present', 'absent', 'late'];

function formatDateYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function StatusButton({
  status,
  active,
  onPress,
  colors,
  palette,
  typography,
}: {
  status: AttendanceMarkStatus;
  active: boolean;
  onPress: () => void;
  colors: { primary: string; success: string; error: string; warning: string };
  palette: { surfaceMuted: string; textPrimary: string; textOnPrimary: string };
  typography: { caption: { fontSize: number } };
}) {
  const label = status === 'present' ? 'P' : status === 'absent' ? 'A' : 'L';
  const bg =
    status === 'present' ? colors.success : status === 'absent' ? colors.error : colors.warning;
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      style={[
        styles.statusBtn,
        {
          backgroundColor: active ? bg : palette.surfaceMuted,
          borderColor: active ? bg : 'transparent',
        },
      ]}
    >
      <Text
        style={{
          color: active ? palette.textOnPrimary : palette.textPrimary,
          fontWeight: '800',
          fontSize: typography.caption.fontSize,
        }}
      >
        {label}
      </Text>
    </Pressable>
  );
}

export const MarkAttendanceScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute();
  /**
   * Attendance tab root is `AttendanceMain` (nested under the Attendance tab).
   * `MarkAttendance` is the same screen pushed from Home — both sit above the
   * floating tab bar.
   */
  const isTabRoot = route.name === 'AttendanceMain' || route.name === 'Attendance';
  const { colors, palette, spacing, typography } = useTheme();
  const { listColumns } = useAdaptiveLayout();
  const networkStatus = useNetworkStatus();
  const [selectedDate, setSelectedDate] = useState(() => new Date());
  const dateStr = formatDateYmd(selectedDate);
  const classroomsQuery = useClassrooms();
  const user = useCurrentUser();
  const homeroomIds = user?.classTeacherClassroomIds ?? [];
  const isSenior =
    user?.role === UserRole.SENIOR_TEACHER || user?.role === UserRole.SUPERVISOR;
  const markMutation = useMarkAttendance();
  const reasonCodesQuery = useAttendanceReasonCodes();
  const reasonCodes = reasonCodesQuery.data ?? [];

  const [classId, setClassId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);
  const [streams, setStreams] = useState<Array<{ id: number; name: string }>>([]);
  const [students, setStudents] = useState<StudentRow[]>([]);
  const [statusById, setStatusById] = useState<Record<number, AttendanceMarkStatus>>({});
  const [reasonById, setReasonById] = useState<Record<number, AttendanceReasonFields>>({});
  const [serverSnapshot, setServerSnapshot] = useState<Record<number, string>>({});
  const [serverReasonById, setServerReasonById] = useState<Record<number, AttendanceReasonFields>>({});
  const [reasonStudentId, setReasonStudentId] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [schoolDayOk, setSchoolDayOk] = useState<boolean | null>(null);
  const [schoolDayMessage, setSchoolDayMessage] = useState<string | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const viewRef = useRef({ classId, streamId, dateStr });
  viewRef.current = { classId, streamId, dateStr };

  const draftKey = classId ? attendanceDraftKey(dateStr, classId, streamId) : null;
  const { draft, setDraft, loaded: draftLoaded, clearDraft } = useOfflineDraft<AttendanceDraft>(draftKey);
  const draftRef = useRef(draft);
  draftRef.current = draft;
  const draftLoadedRef = useRef(draftLoaded);
  draftLoadedRef.current = draftLoaded;

  useEffect(() => {
    void attendanceApi.getSchoolDay(dateStr).then((res) => {
      if (res.success && res.data) {
        if (res.data.is_future) {
          setSchoolDayOk(false);
          setSchoolDayMessage('Cannot mark attendance for a future date.');
        } else if (!res.data.is_school_day) {
          setSchoolDayOk(false);
          setSchoolDayMessage('This date is not a school day (weekend, holiday, or break).');
        } else {
          setSchoolDayOk(true);
          setSchoolDayMessage(null);
        }
      } else {
        setSchoolDayOk(null);
        setSchoolDayMessage(null);
      }
    });
  }, [dateStr]);

  useEffect(() => {
    if (!classId) {
      setStreams([]);
      setStreamId(null);
      return;
    }
    void studentsApi.listStreams(classId).then((res) => {
      setStreams(res.data ?? []);
    });
  }, [classId]);

  const loadStudents = useCallback(async () => {
    if (!classId) return;
    setLoading(true);
    try {
      const [listRes, attRes] = await Promise.all([
        studentsApi.list({ class_id: classId, stream_id: streamId ?? undefined, per_page: 200 }),
        attendanceApi.getClassAttendance({ date: dateStr, class_id: classId, stream_id: streamId }),
      ]);
      const rows: StudentRow[] = (listRes.data?.data ?? []).map((s) => ({
        id: s.id,
        name: s.full_name,
        admission: s.admission_number,
      }));
      setStudents(rows);
      const byId: Record<number, AttendanceMarkStatus> = {};
      const snapshot: Record<number, string> = {};
      const reasons: Record<number, AttendanceReasonFields> = {};
      const existing = new Map((attRes.data ?? []).map((r) => [r.student_id, r]));
      for (const s of rows) {
        const row = existing.get(s.id);
        const status = (row?.status as AttendanceMarkStatus | undefined) ?? 'unmarked';
        byId[s.id] = status;
        snapshot[s.id] = status;
        if (row && (row.reason_code_id || row.reason || row.excuse_notes)) {
          reasons[s.id] = {
            reason_code_id: row.reason_code_id ?? null,
            reason: row.reason ?? null,
            excuse_notes: row.excuse_notes ?? null,
          };
        }
      }

      const savedDraft = draftRef.current;
      if (draftLoadedRef.current && savedDraft?.statusById) {
        setStatusById({ ...byId, ...savedDraft.statusById });
        setReasonById({ ...reasons, ...(savedDraft.reasonById ?? {}) });
        setServerSnapshot(savedDraft.serverSnapshot ?? snapshot);
        setServerReasonById(savedDraft.serverReasonById ?? reasons);
      } else {
        setStatusById(byId);
        setReasonById(reasons);
        setServerSnapshot(snapshot);
        setServerReasonById(reasons);
      }
    } catch (err) {
      const savedDraft = draftRef.current;
      if (draftLoadedRef.current && savedDraft?.statusById) {
        setStatusById(savedDraft.statusById);
        setReasonById(savedDraft.reasonById ?? {});
        setServerSnapshot(savedDraft.serverSnapshot ?? {});
        setServerReasonById(savedDraft.serverReasonById ?? {});
        showSuccess('Offline', 'Showing your saved draft. Server data unavailable.');
      } else {
        showError('Error', err instanceof Error ? err.message : 'Failed to load class.');
      }
    } finally {
      setLoading(false);
    }
  }, [classId, streamId, dateStr]);

  useEffect(() => {
    if (classId && draftLoaded) void loadStudents();
  }, [classId, streamId, dateStr, draftLoaded, loadStudents]);

  useEffect(() => {
    if (!classId) {
      setStudents([]);
      setStatusById({});
      setReasonById({});
      setServerSnapshot({});
      setServerReasonById({});
    }
  }, [classId]);

  const setStatus = (studentId: number, status: AttendanceMarkStatus) => {
    setStatusById((prev) => ({ ...prev, [studentId]: status }));
    if (status === 'present' || status === 'unmarked') {
      setReasonById((prev) => {
        const next = { ...prev };
        delete next[studentId];
        return next;
      });
      return;
    }
    setReasonStudentId(studentId);
  };

  const isDirty = useMemo(() => {
    if (students.length === 0) return false;
    return students.some((s) => {
      const status = statusById[s.id] ?? 'unmarked';
      if (status !== (serverSnapshot[s.id] ?? 'unmarked')) return true;
      return attendanceReasonKey(reasonById[s.id]) !== attendanceReasonKey(serverReasonById[s.id]);
    });
  }, [students, statusById, serverSnapshot, reasonById, serverReasonById]);

  useEffect(() => {
    if (!draftKey || students.length === 0) return;
    if (!isDirty) {
      void clearDraft();
      return;
    }
    setDraft({
      statusById,
      reasonById,
      serverSnapshot,
      serverReasonById,
    });
  }, [statusById, reasonById, draftKey, students.length, isDirty, setDraft, clearDraft, serverSnapshot, serverReasonById]);

  const changedRecords = useMemo(
    () =>
      students
        .map((s) => ({
          student_id: s.id,
          status: (statusById[s.id] ?? 'unmarked') as AttendanceMarkStatus,
          student_name: s.name,
          ...(statusById[s.id] === 'absent' || statusById[s.id] === 'late' ? reasonById[s.id] ?? {} : {}),
        }))
        .filter((r) => {
          const prev = serverSnapshot[r.student_id] ?? 'unmarked';
          if (r.status !== prev) return true;
          return attendanceReasonKey(reasonById[r.student_id]) !== attendanceReasonKey(serverReasonById[r.student_id]);
        }),
    [students, statusById, serverSnapshot, reasonById, serverReasonById],
  );

  const onlyUnmarksPending = useMemo(() => {
    if (!isDirty) return false;
    return changedRecords.every((r) => r.status === 'unmarked');
  }, [isDirty, changedRecords]);

  const canSubmit =
    classId != null && students.length > 0 && (schoolDayOk !== false || onlyUnmarksPending);

  const summary = useMemo(() => {
    const base = summarizeAttendanceMarks(students, statusById);
    return {
      ...base,
      absentEntries: students
        .filter((s) => (statusById[s.id] ?? 'unmarked') === 'absent')
        .map((s) => ({ name: s.name, reason: attendanceReasonLabel(reasonById[s.id], reasonCodes) })),
      lateEntries: students
        .filter((s) => (statusById[s.id] ?? 'unmarked') === 'late')
        .map((s) => ({ name: s.name, reason: attendanceReasonLabel(reasonById[s.id], reasonCodes) })),
    };
  }, [students, statusById, reasonById, reasonCodes]);

  const openConfirm = () => {
    if (!classId) return;
    if (changedRecords.length === 0) {
      showError('Nothing to submit', 'Change at least one student before submitting.');
      return;
    }
    const hasNonUnmark = changedRecords.some((r) => r.status !== 'unmarked');
    if (hasNonUnmark && schoolDayOk === false) {
      showError('Not a school day', schoolDayMessage ?? 'Pick a valid school day.');
      return;
    }
    setConfirmOpen(true);
  };

  const submit = async () => {
    if (!classId) return;
    const submittedClassId = classId;
    const submittedStreamId = streamId;
    const submittedDate = dateStr;
    const submittedStudents = students;
    const submittedStatus = { ...statusById };
    const submittedSnapshot = { ...serverSnapshot };
    const records = submittedStudents
      .map((s) => ({
        student_id: s.id,
        status: (submittedStatus[s.id] ?? 'unmarked') as AttendanceMarkStatus,
        student_name: s.name,
        ...(submittedStatus[s.id] === 'absent' || submittedStatus[s.id] === 'late'
          ? reasonById[s.id] ?? {}
          : {}),
      }))
      .filter((r) => {
        const prev = submittedSnapshot[r.student_id] ?? 'unmarked';
        if (r.status !== prev) return true;
        return attendanceReasonKey(reasonById[r.student_id]) !== attendanceReasonKey(serverReasonById[r.student_id]);
      });
    if (records.length === 0) {
      setConfirmOpen(false);
      return;
    }
    const hasNonUnmark = records.some((r) => r.status !== 'unmarked');
    if (hasNonUnmark && schoolDayOk === false) {
      showError('Not a school day', schoolDayMessage ?? 'Pick a valid school day.');
      return;
    }

    const classLabel =
      classroomsQuery.data?.find((c) => c.id === submittedClassId)?.name ?? `Class #${submittedClassId}`;
    const payload = {
      date: submittedDate,
      class_id: submittedClassId,
      stream_id: submittedStreamId,
      class_label: classLabel,
      records,
      baseSnapshot: submittedSnapshot,
    };

    setSubmitting(true);
    try {
      const result = await queueOrExecute(
        SYNC_KINDS.ATTENDANCE_MARK,
        payload,
        async () => {
          await markMutation.mutateAsync({
            date: submittedDate,
            class_id: submittedClassId,
            stream_id: submittedStreamId,
            records: records.map((r) => ({
              student_id: r.student_id,
              status: r.status,
              reason_code_id: r.reason_code_id,
              reason: r.reason,
              excuse_notes: r.excuse_notes,
            })),
          });
        },
        networkStatus,
        { label: `Attendance · ${classLabel} · ${submittedDate}` },
      );

      const view = viewRef.current;
      const stillOnSameClass =
        view.classId === submittedClassId &&
        view.streamId === submittedStreamId &&
        view.dateStr === submittedDate;

      if (stillOnSameClass) {
        const snap: Record<number, string> = {};
        for (const s of submittedStudents) {
          snap[s.id] = submittedStatus[s.id] ?? 'unmarked';
        }
        setServerSnapshot(snap);
        setServerReasonById({ ...reasonById });
        draftRef.current = null;
        await clearDraft();
      } else {
        await deleteDraftKey(attendanceDraftKey(submittedDate, submittedClassId, submittedStreamId));
      }

      setConfirmOpen(false);
      if (result === 'queued') {
        showSuccess('Queued for sync', 'Attendance will push to the server when you reconnect.');
      } else {
        showSuccess('Submitted', 'Attendance saved on the server.');
      }
    } catch (err) {
      showError('Could not submit', (err as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  const classrooms = (classroomsQuery.data ?? []).filter((c) =>
    isSenior || homeroomIds.length === 0 ? isSenior : homeroomIds.includes(c.id),
  );
  const markedCount = summary.total;

  return (
    <ScreenContainer
      scroll={false}
      style={{ flex: 1 }}
      clearFloatingTabBar={false}
      edges={isTabRoot ? ['bottom'] : undefined}
    >
      <DockedActionLayout
        header={
          <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
            {isTabRoot ? null : (
              <AcademicScreenHeader
                title="Mark attendance"
                subtitle="School-day calendar applies (same as web)"
                onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
              />
            )}

            {isDirty ? (
              <View style={[styles.warnBanner, { backgroundColor: `${colors.primary}14`, borderColor: colors.primary }]}>
                <Text style={{ color: colors.primary, fontSize: typography.body.fontSize }}>
                  Unsubmitted changes — saved as a draft on this device until you submit.
                </Text>
              </View>
            ) : null}

            <DatePickerField
              value={selectedDate}
              onChange={setSelectedDate}
              maximumDate={new Date()}
            />

            {schoolDayMessage ? (
              <View style={[styles.warnBanner, { backgroundColor: `${colors.warning}18`, borderColor: colors.warning }]}>
                <Text style={{ color: colors.warning, fontSize: typography.body.fontSize }}>{schoolDayMessage}</Text>
              </View>
            ) : null}

            <FilterChipRow label="Class">
              {classrooms.map((c) => (
                <FilterChip key={c.id} label={c.name} active={classId === c.id} onPress={() => setClassId(c.id)} />
              ))}
            </FilterChipRow>

            {streams.length > 0 ? (
              <FilterChipRow label="Stream">
                <FilterChip label="All" active={streamId == null} onPress={() => setStreamId(null)} />
                {streams.map((s) => (
                  <FilterChip
                    key={s.id}
                    label={s.name}
                    active={streamId === s.id}
                    onPress={() => setStreamId(s.id)}
                  />
                ))}
              </FilterChipRow>
            ) : null}

            {classId && students.length > 0 ? (
              <Text
                style={{
                  color: palette.textMuted,
                  fontSize: typography.caption.fontSize,
                  marginBottom: spacing.sm,
                }}
              >
                {markedCount}/{students.length} marked
              </Text>
            ) : null}
          </View>
        }
        footer={
          <FooterDock>
            <Button
              label={networkStatus === 'offline' ? 'Submit (queue offline)' : 'Submit attendance'}
              onPress={openConfirm}
              disabled={!canSubmit || !isDirty || submitting}
              loading={submitting}
            />
          </FooterDock>
        }
      >
        {loading ? (
          <View style={{ paddingHorizontal: spacing.md }}>
            <SkeletonListRows variant="avatar" count={6} />
          </View>
        ) : (
          <FlatList
            data={students}
            key={listColumns}
            numColumns={listColumns}
            keyExtractor={(item) => String(item.id)}
            style={styles.list}
            keyboardShouldPersistTaps="handled"
            columnWrapperStyle={listColumns > 1 ? styles.columnWrap : undefined}
            contentContainerStyle={{
              paddingHorizontal: spacing.md,
              paddingBottom: spacing.sm,
              flexGrow: 1,
            }}
            renderItem={({ item }) => {
              const status = statusById[item.id] ?? 'unmarked';
              const needsReason = status === 'absent' || status === 'late';
              const reasonText = attendanceReasonLabel(reasonById[item.id], reasonCodes);
              return (
                <View
                  style={[
                    styles.row,
                    listColumns > 1 ? styles.rowMulti : null,
                    { borderColor: palette.border, backgroundColor: palette.surfaceRaised },
                  ]}
                >
                  <View style={{ flex: 1, minWidth: 0, marginRight: spacing.sm }}>
                    <Text
                      style={{ color: palette.textPrimary, fontWeight: '600' }}
                      numberOfLines={2}
                    >
                      {item.name}
                    </Text>
                    <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                      {item.admission}
                    </Text>
                    {needsReason ? (
                      <Pressable onPress={() => setReasonStudentId(item.id)} hitSlop={8}>
                        <Text
                          style={{
                            color: colors.primary,
                            fontSize: typography.caption.fontSize,
                            marginTop: 4,
                            fontWeight: '600',
                          }}
                          numberOfLines={2}
                        >
                          {reasonText || 'Add reason'}
                        </Text>
                      </Pressable>
                    ) : null}
                  </View>
                  <View style={styles.statusRow}>
                    {STATUS_OPTIONS.map((opt) => (
                      <StatusButton
                        key={opt}
                        status={opt}
                        active={status === opt}
                        onPress={() => setStatus(item.id, status === opt ? 'unmarked' : opt)}
                        colors={colors}
                        palette={palette}
                        typography={typography}
                      />
                    ))}
                  </View>
                </View>
              );
            }}
            ListEmptyComponent={
              classId ? (
                <EmptyState
                  title="No students"
                  message="No students in this class."
                  icon="people-outline"
                />
              ) : (
                <EmptyState
                  title="Select a class"
                  message="Choose a class to begin marking attendance."
                  icon="school-outline"
                />
              )
            }
          />
        )}
      </DockedActionLayout>

      <AttendanceSubmitDialog
        visible={confirmOpen}
        date={dateStr}
        summary={summary}
        loading={submitting}
        onConfirm={() => void submit()}
        onCancel={() => {
          if (!submitting) setConfirmOpen(false);
        }}
      />
      <AttendanceReasonSheet
        visible={reasonStudentId != null}
        studentName={students.find((s) => s.id === reasonStudentId)?.name}
        statusLabel={
          reasonStudentId != null && statusById[reasonStudentId] === 'late' ? 'late' : 'absent'
        }
        codes={reasonCodes}
        value={reasonStudentId != null ? reasonById[reasonStudentId] : null}
        onSave={(next) => {
          if (reasonStudentId != null) {
            setReasonById((prev) => ({ ...prev, [reasonStudentId]: next }));
          }
          setReasonStudentId(null);
        }}
        onSkip={() => setReasonStudentId(null)}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  list: { flex: 1, minHeight: 0 },
  columnWrap: { gap: 8 },
  warnBanner: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 10,
    marginBottom: 8,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
  rowMulti: { flex: 1, minWidth: 0 },
  statusRow: { flexDirection: 'row', gap: 6, flexShrink: 0 },
  statusBtn: {
    width: 44,
    height: 44,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
  },
});
