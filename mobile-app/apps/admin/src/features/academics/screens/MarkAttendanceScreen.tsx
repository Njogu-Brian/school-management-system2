import {
  attendanceApi,
  attendanceDraftKey,
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
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import DateTimePicker from '@react-native-community/datetimepicker';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'MarkAttendance'>;

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
}: {
  status: AttendanceMarkStatus;
  active: boolean;
  onPress: () => void;
  colors: { primary: string; success: string; error: string; warning: string };
  palette: { surfaceMuted: string; textPrimary: string };
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
      <Text style={{ color: active ? '#fff' : palette.textPrimary, fontWeight: '800', fontSize: 12 }}>
        {label}
      </Text>
    </Pressable>
  );
}

export const MarkAttendanceScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
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
  const [loading, setLoading] = useState(false);
  const [schoolDayOk, setSchoolDayOk] = useState<boolean | null>(null);
  const [schoolDayMessage, setSchoolDayMessage] = useState<string | null>(null);
  const [hasLocalDraft, setHasLocalDraft] = useState(false);

  const serverSnapshotRef = useRef<Record<number, string>>({});
  const draftKey = classId ? attendanceDraftKey(dateStr, classId, streamId) : null;
  const { draft, setDraft, loaded: draftLoaded, clearDraft } = useOfflineDraft<AttendanceDraft>(draftKey);

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
      serverSnapshotRef.current = snapshot;

      if (draftLoaded && draft?.statusById) {
        setStatusById({ ...byId, ...draft.statusById });
        serverSnapshotRef.current = draft.serverSnapshot ?? snapshot;
        setHasLocalDraft(true);
      } else {
        setStatusById(byId);
        setHasLocalDraft(false);
      }
    } catch (err) {
      if (draftLoaded && draft?.statusById) {
        setStatusById(draft.statusById);
        serverSnapshotRef.current = draft.serverSnapshot ?? {};
        setHasLocalDraft(true);
        Alert.alert('Offline', 'Showing your saved draft. Server data unavailable.');
      } else {
        Alert.alert('Error', err instanceof Error ? err.message : 'Failed to load class.');
      }
    } finally {
      setLoading(false);
    }
  }, [classId, streamId, dateStr, draft, draftLoaded]);

  useEffect(() => {
    if (classId && draftLoaded) void loadStudents();
  }, [classId, streamId, draftLoaded, loadStudents]);

  useEffect(() => {
    if (!draftKey || students.length === 0) return;
    setDraft({
      statusById,
      serverSnapshot: serverSnapshotRef.current,
    });
  }, [statusById, draftKey, students.length, setDraft]);

  const setStatus = (studentId: number, status: AttendanceMarkStatus) => {
    setStatusById((prev) => ({ ...prev, [studentId]: status }));
    setHasLocalDraft(true);
  };

  const markAll = (status: AttendanceMarkStatus) => {
    setStatusById((prev) => {
      const next = { ...prev };
      for (const s of students) next[s.id] = status;
      return next;
    });
    setHasLocalDraft(true);
  };

  const save = async () => {
    if (!classId) return;
    if (schoolDayOk === false) {
      Alert.alert('Not a school day', schoolDayMessage ?? 'Pick a valid school day.');
      return;
    }
    const records = students
      .map((s) => ({
        student_id: s.id,
        status: statusById[s.id] ?? 'unmarked',
        student_name: s.name,
      }))
      .filter((r) => r.status !== 'unmarked');
    if (records.length === 0) {
      Alert.alert('Nothing to save', 'Mark at least one student as Present, Absent, or Late.');
      return;
    }

    const classLabel =
      classroomsQuery.data?.find((c) => c.id === classId)?.name ?? `Class #${classId}`;
    const payload = {
      date: dateStr,
      class_id: classId,
      stream_id: streamId,
      class_label: classLabel,
      records,
      baseSnapshot: serverSnapshotRef.current,
    };

    try {
      const result = await queueOrExecute(
        SYNC_KINDS.ATTENDANCE_MARK,
        payload,
        async () => {
          await markMutation.mutateAsync({
            date: dateStr,
            class_id: classId,
            stream_id: streamId,
            records: records.map((r) => ({ student_id: r.student_id, status: r.status })),
          });
        },
        networkStatus,
        { label: `Attendance · ${classLabel} · ${dateStr}` },
      );

      if (result === 'queued') {
        Alert.alert('Queued offline', 'Attendance will sync when you reconnect.');
      } else {
        Alert.alert('Saved', 'Attendance updated successfully.');
        await clearDraft();
        setHasLocalDraft(false);
        void loadStudents();
      }
    } catch (err) {
      Alert.alert('Could not save', (err as Error).message);
    }
  };

  const classrooms = classroomsQuery.data ?? [];
  const markedCount = useMemo(
    () => students.filter((s) => (statusById[s.id] ?? 'unmarked') !== 'unmarked').length,
    [students, statusById],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title="Mark attendance"
          subtitle="School-day calendar applies (same as web)"
          onBack={() => navigation.goBack()}
        />

        {hasLocalDraft ? (
          <View style={[styles.warnBanner, { backgroundColor: `${colors.primary}14`, borderColor: colors.primary }]}>
            <Text style={{ color: colors.primary, fontSize: fontSizes.sm }}>
              Local draft in progress — auto-saved on this device.
            </Text>
          </View>
        ) : null}

        <Pressable
          onPress={() => setShowDatePicker(true)}
          style={[styles.dateRow, { borderColor: palette.border, backgroundColor: palette.surfaceRaised }]}
        >
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>Date</Text>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.md }}>{dateStr}</Text>
          <Text style={{ color: colors.primary, fontSize: fontSizes.xs, fontWeight: '600' }}>Change</Text>
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
            <Text style={{ color: colors.warning, fontSize: fontSizes.sm }}>{schoolDayMessage}</Text>
          </View>
        ) : null}

        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs, marginTop: spacing.sm }}>
          Class
        </Text>
        <FilterChipRow>
          {classrooms.map((c) => (
            <FilterChip key={c.id} label={c.name} active={classId === c.id} onPress={() => setClassId(c.id)} />
          ))}
        </FilterChipRow>

        {streams.length > 0 ? (
          <>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginVertical: spacing.xs }}>
              Stream
            </Text>
            <FilterChipRow>
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
          </>
        ) : null}

        {classId ? (
          <View style={[styles.bulkRow, { marginVertical: spacing.sm }]}>
            <Pressable onPress={() => markAll('present')}>
              <Text style={{ color: colors.success, fontWeight: '700' }}>All Present</Text>
            </Pressable>
            <Pressable onPress={() => markAll('absent')}>
              <Text style={{ color: colors.error, fontWeight: '700' }}>All Absent</Text>
            </Pressable>
            <Pressable onPress={() => markAll('late')}>
              <Text style={{ color: colors.warning, fontWeight: '700' }}>All Late</Text>
            </Pressable>
            <Text style={{ color: palette.textMuted, fontSize: fontSizes.xs }}>
              {markedCount}/{students.length}
            </Text>
          </View>
        ) : null}

        {loading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : (
          <FlatList
            data={students}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{ paddingBottom: 100 }}
            renderItem={({ item }) => {
              const status = statusById[item.id] ?? 'unmarked';
              return (
                <View
                  style={[
                    styles.row,
                    { borderColor: palette.border, backgroundColor: palette.surfaceRaised },
                  ]}
                >
                  <View style={{ flex: 1, marginRight: spacing.sm }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.name}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>{item.admission}</Text>
                  </View>
                  <View style={styles.statusRow}>
                    {STATUS_OPTIONS.map((opt) => (
                      <StatusButton
                        key={opt}
                        status={opt}
                        active={status === opt}
                        onPress={() => setStatus(item.id, opt)}
                        colors={colors}
                        palette={palette}
                      />
                    ))}
                  </View>
                </View>
              );
            }}
            ListEmptyComponent={
              classId ? (
                <Text style={{ color: palette.textSecondary, textAlign: 'center', marginTop: 24 }}>
                  No students in this class.
                </Text>
              ) : (
                <Text style={{ color: palette.textSecondary, textAlign: 'center', marginTop: 24 }}>
                  Select a class to begin.
                </Text>
              )
            }
          />
        )}

        {classId && students.length > 0 && schoolDayOk !== false ? (
          <Button
            label={networkStatus === 'offline' ? 'Queue attendance' : 'Save attendance'}
            onPress={() => void save()}
            loading={markMutation.isPending}
            style={{ marginTop: spacing.sm }}
          />
        ) : null}
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
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
  bulkRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 8 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
  statusRow: { flexDirection: 'row', gap: 6 },
  statusBtn: {
    width: 36,
    height: 36,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
  },
});
