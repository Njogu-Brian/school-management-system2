import {
  useActivityAttendance,
  useActivityStudents,
  useSaveActivityAttendance,
  type AttendanceMarkStatus,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import DateTimePicker from '@react-native-community/datetimepicker';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useEffect, useMemo, useState } from 'react';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'ActivityAttendance'>;

/** Activities are presence-based; `late` still counts as attended when saved. */
type ActivityStatus = 'present' | 'absent' | 'late';
const STATUS_OPTIONS: ActivityStatus[] = ['present', 'absent', 'late'];

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
  status: ActivityStatus;
  active: boolean;
  onPress: () => void;
  colors: { success: string; error: string; warning: string };
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
        { backgroundColor: active ? bg : palette.surfaceMuted, borderColor: active ? bg : 'transparent' },
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

export const ActivityAttendanceScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { activityId, activityName } = route.params;
  const { colors, palette, spacing, typography } = useTheme();

  const [selectedDate, setSelectedDate] = useState(() => new Date());
  const dateStr = formatDateYmd(selectedDate);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [statusById, setStatusById] = useState<Record<number, ActivityStatus>>({});
  const [serverSnapshot, setServerSnapshot] = useState<Record<number, ActivityStatus>>({});

  const studentsQuery = useActivityStudents(activityId, dateStr);
  const attendanceQuery = useActivityAttendance(activityId, dateStr);
  const saveMutation = useSaveActivityAttendance();

  const students = studentsQuery.data ?? [];
  const loading = studentsQuery.isLoading || attendanceQuery.isLoading;

  // Hydrate: default everyone to absent, mark attended students present from the server.
  useEffect(() => {
    if (studentsQuery.data == null || attendanceQuery.data == null) return;
    const attended = new Set((attendanceQuery.data ?? []).map((r) => r.student_id));
    const next: Record<number, ActivityStatus> = {};
    for (const s of studentsQuery.data) {
      next[s.id] = attended.has(s.id) ? 'present' : 'absent';
    }
    setStatusById(next);
    setServerSnapshot(next);
  }, [studentsQuery.data, attendanceQuery.data]);

  const setStatus = (studentId: number, status: ActivityStatus) => {
    setStatusById((prev) => ({ ...prev, [studentId]: status }));
  };

  const markAll = (status: ActivityStatus) => {
    setStatusById(() => {
      const next: Record<number, ActivityStatus> = {};
      for (const s of students) next[s.id] = status;
      return next;
    });
  };

  const isDirty = useMemo(() => {
    if (students.length === 0) return false;
    return students.some((s) => (statusById[s.id] ?? 'absent') !== (serverSnapshot[s.id] ?? 'absent'));
  }, [students, statusById, serverSnapshot]);

  const attendedCount = useMemo(
    () => students.filter((s) => (statusById[s.id] ?? 'absent') !== 'absent').length,
    [students, statusById],
  );

  const submit = async () => {
    const records = students.map((s) => ({
      student_id: s.id,
      status: (statusById[s.id] ?? 'absent') as AttendanceMarkStatus,
    }));
    if (records.length === 0) {
      showError('Nothing to submit', 'No students are enrolled in this activity.');
      return;
    }
    try {
      await saveMutation.mutateAsync({ activityId, date: dateStr, records });
      const snap: Record<number, ActivityStatus> = {};
      for (const s of students) snap[s.id] = statusById[s.id] ?? 'absent';
      setServerSnapshot(snap);
      showSuccess('Saved', 'Activity attendance saved.');
    } catch (err) {
      showError('Could not save', (err as Error).message);
    }
  };

  const showSubmit = students.length > 0 && isDirty;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} clearFloatingTabBar={false}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title={activityName}
          subtitle="Mark today's activity attendance"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />

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

        {students.length > 0 ? (
          <View style={styles.bulkRow}>
            <Pressable onPress={() => markAll('present')}>
              <Text style={{ color: colors.success, fontWeight: '700' }}>All Present</Text>
            </Pressable>
            <Pressable onPress={() => markAll('absent')}>
              <Text style={{ color: colors.error, fontWeight: '700' }}>All Absent</Text>
            </Pressable>
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
              {attendedCount}/{students.length}
            </Text>
          </View>
        ) : null}

        {loading ? (
          <SkeletonListRows variant="avatar" count={6} />
        ) : (
          <FlatList
            data={students}
            keyExtractor={(item) => String(item.id)}
            style={{ flex: 1 }}
            contentContainerStyle={{ flexGrow: 1, paddingBottom: showSubmit ? spacing.sm : spacing.lg }}
            renderItem={({ item }) => {
              const status = statusById[item.id] ?? 'absent';
              return (
                <View style={[styles.row, { borderColor: palette.border, backgroundColor: palette.surfaceRaised }]}>
                  <View style={{ flex: 1, marginRight: spacing.sm }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.full_name}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                      {item.admission_number}
                    </Text>
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
                        typography={typography}
                      />
                    ))}
                  </View>
                </View>
              );
            }}
            ListEmptyComponent={
              <EmptyState
                title="No students"
                message="No students are enrolled in this activity yet."
                icon="people-outline"
              />
            }
          />
        )}

        {showSubmit ? (
          <View
            style={{
              paddingTop: spacing.sm,
              paddingBottom: spacing.md,
              backgroundColor: palette.background,
              borderTopWidth: StyleSheet.hairlineWidth,
              borderTopColor: palette.border,
            }}
          >
            <Button label="Submit attendance" onPress={() => void submit()} loading={saveMutation.isPending} />
          </View>
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
  bulkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexWrap: 'wrap',
    gap: 8,
    marginVertical: 8,
  },
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
