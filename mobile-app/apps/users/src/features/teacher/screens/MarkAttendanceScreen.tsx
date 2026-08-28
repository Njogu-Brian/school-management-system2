import {
  attendanceApi,
  attendanceDraftKey,
  clearDraft as deleteDraftKey,
  queueOrExecute,
  studentsApi,
  SYNC_KINDS,
  useClassrooms,
  useMarkAttendance,
  useNetworkStatus,
  useOfflineDraft,
  type AttendanceMarkStatus,
} from '@erp/core';
import {
  AcademicScreenHeader,
  AttendanceSubmitDialog,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  FooterDock,
  ScreenContainer,
  SkeletonListRows,
  summarizeAttendanceMarks,
  useTheme,
} from '@erp/ui';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  FlatList,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

type StudentRow = { id: number; name: string; admission: string };

type AttendanceDraft = {
  statusById: Record<number, AttendanceMarkStatus>;
  serverSnapshot: Record<number, string>;
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
  const networkStatus = useNetworkStatus();
  const [selectedDate, setSelectedDate] = useState(() => new Date());
  const dateStr = formatDateYmd(selectedDate);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const classroomsQuery = useClassrooms();
  const markMutation = useMarkAttendance();

  const [classId, setClassId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);
  const [streams, setStreams] = useState<Array<{ id: number; name: string }>>([]);
  const [students, setStudents] = useState<StudentRow[]>([]);
  const [statusById, setStatusById] = useState<Record<number, AttendanceMarkStatus>>({});
  const [serverSnapshot, setServerSnapshot] = useState<Record<number, string>>({});
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
      const existing = new Map(
        (attRes.data ?? []).map((r) => [r.student_id, r.status as AttendanceMarkStatus]),
      );
      for (const s of rows) {
        const status = existing.get(s.id) ?? 'unmarked';
        byId[s.id] = status;
        snapshot[s.id] = status;
      }

      const savedDraft = draftRef.current;
      if (draftLoadedRef.current && savedDraft?.statusById) {
        setStatusById({ ...byId, ...savedDraft.statusById });
        setServerSnapshot(savedDraft.serverSnapshot ?? snapshot);
      } else {
        setStatusById(byId);
        setServerSnapshot(snapshot);
      }
    } catch (err) {
      const savedDraft = draftRef.current;
      if (draftLoadedRef.current && savedDraft?.statusById) {
        setStatusById(savedDraft.statusById);
        setServerSnapshot(savedDraft.serverSnapshot ?? {});
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
      setServerSnapshot({});
    }
  }, [classId]);

  const setStatus = (studentId: number, status: AttendanceMarkStatus) => {
    setStatusById((prev) => ({ ...prev, [studentId]: status }));
  };

  const isDirty = useMemo(() => {
    if (students.length === 0) return false;
    return students.some((s) => (statusById[s.id] ?? 'unmarked') !== (serverSnapshot[s.id] ?? 'unmarked'));
  }, [students, statusById, serverSnapshot]);

  useEffect(() => {
    if (!draftKey || students.length === 0) return;
    if (!isDirty) {
      void clearDraft();
      return;
    }
    setDraft({
      statusById,
      serverSnapshot,
    });
  }, [statusById, draftKey, students.length, isDirty, setDraft, clearDraft, serverSnapshot]);

  const changedRecords = useMemo(
    () =>
      students
        .map((s) => ({
          student_id: s.id,
          status: (statusById[s.id] ?? 'unmarked') as AttendanceMarkStatus,
          student_name: s.name,
        }))
        .filter((r) => r.status !== (serverSnapshot[r.student_id] ?? 'unmarked')),
    [students, statusById, serverSnapshot],
  );

  const onlyUnmarksPending = useMemo(() => {
    if (!isDirty) return false;
    return changedRecords.every((r) => r.status === 'unmarked');
  }, [isDirty, changedRecords]);

  const canSubmit =
    classId != null && students.length > 0 && (schoolDayOk !== false || onlyUnmarksPending);

  const summary = useMemo(
    () => summarizeAttendanceMarks(students, statusById),
    [students, statusById],
  );

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
      }))
      .filter((r) => r.status !== (submittedSnapshot[r.student_id] ?? 'unmarked'));
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
            records: records.map((r) => ({ student_id: r.student_id, status: r.status })),
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

  const classrooms = classroomsQuery.data ?? [];
  const markedCount = summary.total;

  return (
    <ScreenContainer
      scroll={false}
      style={{ flex: 1 }}
      clearFloatingTabBar={false}
      edges={isTabRoot ? ['bottom'] : undefined}
    >
      <View style={styles.body}>
        <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md, flexShrink: 0 }}>
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

          <Pressable
            onPress={() => setShowDatePicker(true)}
            style={[styles.dateRow, { borderColor: palette.border, backgroundColor: palette.surfaceRaised }]}
          >
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Date</Text>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.titleSmall.fontSize }}>
              {dateStr}
            </Text>
            <Text style={{ color: colors.primary, fontSize: typography.caption.fontSize, fontWeight: '600' }}>
              Change
            </Text>
          </Pressable>

          {showDatePicker ? (
            <DateTimePicker
              value={selectedDate}
              mode="date"
              maximumDate={new Date()}
              onChange={(_, date) => {
                setShowDatePicker(Platform.OS === 'ios');
                if (date) setSelectedDate(date);
              }}
            />
          ) : null}

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

        <View style={styles.listWrap}>
          {loading ? (
            <View style={{ paddingHorizontal: spacing.md }}>
              <SkeletonListRows variant="avatar" count={6} />
            </View>
          ) : (
            <FlatList
              data={students}
              keyExtractor={(item) => String(item.id)}
              style={styles.list}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={{
                paddingHorizontal: spacing.md,
                paddingBottom: spacing.sm,
              }}
              renderItem={({ item }) => {
                const status = statusById[item.id] ?? 'unmarked';
                return (
                  <View
                    style={[
                      styles.row,
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
        </View>
      </View>

      <FooterDock>
        <Button
          label={networkStatus === 'offline' ? 'Submit (queue offline)' : 'Submit attendance'}
          onPress={openConfirm}
          disabled={!canSubmit || !isDirty || submitting}
          loading={submitting}
        />
      </FooterDock>

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
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  body: { flex: 1 },
  listWrap: { flex: 1, minHeight: 0 },
  list: { flex: 1 },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
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
