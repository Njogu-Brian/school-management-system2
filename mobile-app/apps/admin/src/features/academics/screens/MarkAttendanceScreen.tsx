import {
  attendanceApi,
  useClassrooms,
  useMarkAttendance,
  studentsApi,
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
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'MarkAttendance'>;

type StudentRow = { id: number; name: string; admission: string };

const STATUS_CYCLE: AttendanceMarkStatus[] = ['unmarked', 'present', 'absent', 'late'];
const STATUS_LABEL: Record<AttendanceMarkStatus, string> = {
  unmarked: '—',
  present: 'Present',
  absent: 'Absent',
  late: 'Late',
};

export const MarkAttendanceScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const today = new Date().toISOString().slice(0, 10);
  const classroomsQuery = useClassrooms();
  const markMutation = useMarkAttendance();

  const [classId, setClassId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);
  const [streams, setStreams] = useState<Array<{ id: number; name: string }>>([]);
  const [students, setStudents] = useState<StudentRow[]>([]);
  const [statusById, setStatusById] = useState<Record<number, AttendanceMarkStatus>>({});
  const [loading, setLoading] = useState(false);

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
        attendanceApi.getClassAttendance({ date: today, class_id: classId, stream_id: streamId }),
      ]);
      const rows: StudentRow[] = (listRes.data?.data ?? []).map((s) => ({
        id: s.id,
        name: s.full_name,
        admission: s.admission_number,
      }));
      setStudents(rows);
      const byId: Record<number, AttendanceMarkStatus> = {};
      const existing = new Map(
        (attRes.data ?? []).map((r) => [r.student_id, r.status as AttendanceMarkStatus]),
      );
      for (const s of rows) {
        byId[s.id] = existing.get(s.id) ?? 'unmarked';
      }
      setStatusById(byId);
    } catch (err) {
      Alert.alert('Error', err instanceof Error ? err.message : 'Failed to load class.');
    } finally {
      setLoading(false);
    }
  }, [classId, streamId, today]);

  useEffect(() => {
    if (classId) void loadStudents();
  }, [classId, streamId, loadStudents]);

  const cycleStatus = (studentId: number) => {
    setStatusById((prev) => {
      const current = prev[studentId] ?? 'unmarked';
      const idx = STATUS_CYCLE.indexOf(current);
      const next = STATUS_CYCLE[(idx + 1) % STATUS_CYCLE.length];
      return { ...prev, [studentId]: next };
    });
  };

  const markAll = (status: AttendanceMarkStatus) => {
    setStatusById((prev) => {
      const next = { ...prev };
      for (const s of students) next[s.id] = status;
      return next;
    });
  };

  const save = async () => {
    if (!classId) return;
    try {
      await markMutation.mutateAsync({
        date: today,
        class_id: classId,
        stream_id: streamId,
        records: students.map((s) => ({
          student_id: s.id,
          status: statusById[s.id] ?? 'unmarked',
        })),
      });
      Alert.alert('Saved', 'Attendance updated successfully.');
    } catch (err) {
      Alert.alert('Error', err instanceof Error ? err.message : 'Could not save attendance.');
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
          subtitle={`Today · ${today}`}
          onBack={() => navigation.goBack()}
        />

        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.xs }}>
          Class
        </Text>
        <FilterChipRow>
          {classrooms.map((c) => (
            <FilterChip
              key={c.id}
              label={c.name}
              active={classId === c.id}
              onPress={() => setClassId(c.id)}
            />
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
              <Text style={{ color: colors.primary, fontWeight: '600' }}>All present</Text>
            </Pressable>
            <Pressable onPress={() => markAll('absent')}>
              <Text style={{ color: colors.error, fontWeight: '600' }}>All absent</Text>
            </Pressable>
            <Text style={{ color: palette.textMuted, fontSize: fontSizes.xs }}>
              {markedCount}/{students.length} marked
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
                <Pressable
                  onPress={() => cycleStatus(item.id)}
                  style={[
                    styles.row,
                    { borderColor: palette.border, backgroundColor: palette.surfaceRaised },
                  ]}
                >
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.name}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                      {item.admission}
                    </Text>
                  </View>
                  <Text
                    style={{
                      color:
                        status === 'present'
                          ? colors.success
                          : status === 'absent'
                            ? colors.error
                            : status === 'late'
                              ? colors.warning
                              : palette.textMuted,
                      fontWeight: '700',
                    }}
                  >
                    {STATUS_LABEL[status]}
                  </Text>
                </Pressable>
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

        {classId && students.length > 0 ? (
          <Button
            label="Save attendance"
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
  bulkRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
});
