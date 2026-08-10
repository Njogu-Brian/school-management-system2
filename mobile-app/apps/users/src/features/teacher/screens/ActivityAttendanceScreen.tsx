import {
  attendanceApi,
  useActivityAttendance,
  useActivityStudents,
  useSaveActivityAttendance,
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
import { FlatList, Platform, Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'ActivityAttendance'>;

function formatDateYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

/**
 * Activity roll — checkbox attendance (attended / not), school-day gated.
 * Does not send class-attendance SMS; matches portal presence-based activity marking.
 */
export const ActivityAttendanceScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { activityId, activityName } = route.params;
  const { colors, palette, spacing, typography } = useTheme();

  const [selectedDate, setSelectedDate] = useState(() => new Date());
  const dateStr = formatDateYmd(selectedDate);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [attendedById, setAttendedById] = useState<Record<number, boolean>>({});
  const [serverSnapshot, setServerSnapshot] = useState<Record<number, boolean>>({});
  const [schoolDayOk, setSchoolDayOk] = useState<boolean | null>(null);
  const [schoolDayMessage, setSchoolDayMessage] = useState<string | null>(null);

  const studentsQuery = useActivityStudents(activityId, dateStr);
  const attendanceQuery = useActivityAttendance(activityId, dateStr);
  const saveMutation = useSaveActivityAttendance();

  const students = studentsQuery.data ?? [];
  const loading = studentsQuery.isLoading || attendanceQuery.isLoading;

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await attendanceApi.getSchoolDay(dateStr);
        if (cancelled) return;
        if (!res.success || !res.data) {
          setSchoolDayOk(null);
          setSchoolDayMessage(null);
          return;
        }
        if (!res.data.is_school_day) {
          setSchoolDayOk(false);
          setSchoolDayMessage('This date is not a school day (weekend, holiday, or break).');
        } else {
          setSchoolDayOk(true);
          setSchoolDayMessage(null);
        }
      } catch {
        if (!cancelled) {
          setSchoolDayOk(null);
          setSchoolDayMessage(null);
        }
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [dateStr]);

  useEffect(() => {
    if (studentsQuery.data == null || attendanceQuery.data == null) return;
    const attended = new Set((attendanceQuery.data ?? []).map((r) => r.student_id));
    const next: Record<number, boolean> = {};
    for (const s of studentsQuery.data) {
      next[s.id] = attended.has(s.id);
    }
    setAttendedById(next);
    setServerSnapshot(next);
  }, [studentsQuery.data, attendanceQuery.data]);

  const toggle = (studentId: number) => {
    setAttendedById((prev) => ({ ...prev, [studentId]: !prev[studentId] }));
  };

  const markAll = (value: boolean) => {
    const next: Record<number, boolean> = {};
    for (const s of students) next[s.id] = value;
    setAttendedById(next);
  };

  const isDirty = useMemo(() => {
    if (students.length === 0) return false;
    return students.some((s) => (attendedById[s.id] ?? false) !== (serverSnapshot[s.id] ?? false));
  }, [students, attendedById, serverSnapshot]);

  const attendedCount = useMemo(
    () => students.filter((s) => attendedById[s.id]).length,
    [students, attendedById],
  );

  const submit = async () => {
    if (schoolDayOk === false) {
      showError('Not a school day', schoolDayMessage ?? 'Pick a valid school day.');
      return;
    }
    const records = students.map((s) => ({
      student_id: s.id,
      status: (attendedById[s.id] ? 'present' : 'absent') as 'present' | 'absent',
    }));
    if (records.length === 0) {
      showError('Nothing to submit', 'No students are enrolled in this activity.');
      return;
    }
    try {
      await saveMutation.mutateAsync({ activityId, date: dateStr, records });
      const snap: Record<number, boolean> = {};
      for (const s of students) snap[s.id] = !!attendedById[s.id];
      setServerSnapshot(snap);
      showSuccess('Saved', 'Activity roll saved.');
    } catch (err) {
      showError('Could not save', (err as Error).message);
    }
  };

  const canSubmit = students.length > 0 && schoolDayOk !== false && isDirty;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} clearFloatingTabBar={false}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md, flex: 1 }}>
        <AcademicScreenHeader
          title={activityName}
          subtitle="Activity roll"
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

        {schoolDayMessage ? (
          <View style={[styles.warnBanner, { backgroundColor: `${colors.warning}18`, borderColor: colors.warning }]}>
            <Text style={{ color: colors.warning, fontSize: typography.body.fontSize }}>{schoolDayMessage}</Text>
          </View>
        ) : null}

        {students.length > 0 && schoolDayOk !== false ? (
          <View style={styles.bulkRow}>
            <Pressable onPress={() => markAll(true)}>
              <Text style={{ color: colors.primary, fontWeight: '700' }}>Select all</Text>
            </Pressable>
            <Pressable onPress={() => markAll(false)}>
              <Text style={{ color: palette.textSecondary, fontWeight: '700' }}>Clear all</Text>
            </Pressable>
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
              {attendedCount}/{students.length} attended
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
            contentContainerStyle={{ flexGrow: 1, paddingBottom: canSubmit ? spacing.sm : spacing.lg }}
            renderItem={({ item }) => {
              const attended = !!attendedById[item.id];
              return (
                <Pressable
                  onPress={() => schoolDayOk !== false && toggle(item.id)}
                  disabled={schoolDayOk === false}
                  style={[
                    styles.row,
                    {
                      borderColor: palette.border,
                      backgroundColor: palette.surfaceRaised,
                      opacity: schoolDayOk === false ? 0.55 : 1,
                    },
                  ]}
                >
                  <View style={{ flex: 1, marginRight: spacing.sm }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.full_name}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                      {item.admission_number}
                    </Text>
                  </View>
                  <View style={{ alignItems: 'flex-end', gap: 4 }}>
                    <Switch
                      value={attended}
                      onValueChange={() => toggle(item.id)}
                      disabled={schoolDayOk === false}
                    />
                    <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                      {attended ? 'Attended' : 'Not attended'}
                    </Text>
                  </View>
                </Pressable>
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

        {canSubmit ? (
          <View
            style={{
              paddingTop: spacing.sm,
              paddingBottom: spacing.md,
              backgroundColor: palette.background,
              borderTopWidth: StyleSheet.hairlineWidth,
              borderTopColor: palette.border,
            }}
          >
            <Button label="Submit roll" onPress={() => void submit()} loading={saveMutation.isPending} />
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
  warnBanner: {
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
});
