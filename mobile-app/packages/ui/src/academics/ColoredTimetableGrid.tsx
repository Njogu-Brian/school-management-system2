import React, { useMemo } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
import { useTheme } from '../theme';

const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as const;

export interface ColoredTimetableSlot {
  id: number;
  day: string;
  period?: number;
  start_time: string;
  end_time: string;
  subject_id: number;
  subject_name: string;
  teacher_name?: string | null;
  classroom_name?: string | null;
  is_break?: boolean;
}

const SUBJECT_COLORS = [
  '#1D4ED8',
  '#0F766E',
  '#B45309',
  '#7C3AED',
  '#BE185D',
  '#0369A1',
  '#15803D',
  '#C2410C',
  '#4338CA',
  '#0E7490',
];

function normalizeDay(day: string): string {
  const raw = day.trim();
  const map: Record<string, string> = {
    mon: 'Monday',
    monday: 'Monday',
    tue: 'Tuesday',
    tues: 'Tuesday',
    tuesday: 'Tuesday',
    wed: 'Wednesday',
    wednesday: 'Wednesday',
    thu: 'Thursday',
    thur: 'Thursday',
    thurs: 'Thursday',
    thursday: 'Thursday',
    fri: 'Friday',
    friday: 'Friday',
  };
  return map[raw.toLowerCase()] ?? raw;
}

function subjectColor(subjectId: number, name: string): string {
  const key = subjectId > 0 ? subjectId : hashString(name);
  return SUBJECT_COLORS[Math.abs(key) % SUBJECT_COLORS.length];
}

function hashString(value: string): number {
  let h = 0;
  for (let i = 0; i < value.length; i += 1) {
    h = (h * 31 + value.charCodeAt(i)) | 0;
  }
  return h;
}

function trimTime(value: string): string {
  return value.length >= 5 ? value.slice(0, 5) : value;
}

function isBreakSlot(slot: ColoredTimetableSlot): boolean {
  if (slot.is_break) return true;
  const name = (slot.subject_name || '').toLowerCase();
  return name === 'break' || name === 'lunch' || name.includes('break') || name.includes('lunch');
}

export interface ColoredTimetableGridProps {
  slots: ColoredTimetableSlot[];
  showTeacher?: boolean;
  showClassroom?: boolean;
}

export const ColoredTimetableGrid: React.FC<ColoredTimetableGridProps> = ({
  slots,
  showTeacher = false,
  showClassroom = false,
}) => {
  const { palette, spacing, typography, radius } = useTheme();

  const { periods, cells } = useMemo(() => {
    const periodMap = new Map<string, { key: string; period: number; start: string; end: string }>();
    const cellMap = new Map<string, ColoredTimetableSlot>();

    for (const slot of slots) {
      const day = normalizeDay(slot.day);
      const start = trimTime(slot.start_time || '08:00');
      const end = trimTime(slot.end_time || '08:40');
      const period = slot.period && slot.period > 0 ? slot.period : 0;
      const key = period > 0 ? `p-${period}` : `t-${start}`;
      if (!periodMap.has(key)) {
        periodMap.set(key, { key, period, start, end });
      }
      cellMap.set(`${day}|${key}`, slot);
    }

    const periodList = Array.from(periodMap.values()).sort((a, b) => {
      if (a.period && b.period) return a.period - b.period;
      return a.start.localeCompare(b.start);
    });

    return { periods: periodList, cells: cellMap };
  }, [slots]);

  if (slots.length === 0) {
    return (
      <EmptyState
        title="No timetable"
        message="The school has not saved a timetable for this term yet."
        icon="calendar-outline"
      />
    );
  }

  const cellWidth = 88;
  const labelWidth = 58;

  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false}>
      <View>
        <View style={styles.row}>
          <View style={[styles.timeCol, { width: labelWidth }]} />
          {WEEKDAYS.map((day) => (
            <View key={day} style={[styles.headerCell, { width: cellWidth, paddingVertical: spacing.xs }]}>
              <Text style={[typography.caption, { color: palette.textMuted, fontWeight: '700', textAlign: 'center' }]}>
                {day.slice(0, 3)}
              </Text>
            </View>
          ))}
        </View>
        {periods.map((period) => (
          <View key={period.key} style={styles.row}>
            <View style={[styles.timeCol, { width: labelWidth, paddingRight: spacing.xs }]}>
              <Text style={[typography.caption, { color: palette.textMuted, fontWeight: '600' }]}>
                {period.period > 0 ? `P${period.period}` : trimTime(period.start)}
              </Text>
              <Text style={[typography.caption, { color: palette.textMuted, fontSize: 10 }]}>
                {trimTime(period.start)}
              </Text>
            </View>
            {WEEKDAYS.map((day) => {
              const slot = cells.get(`${day}|${period.key}`);
              if (!slot) {
                return (
                  <View
                    key={day}
                    style={[
                      styles.cell,
                      {
                        width: cellWidth,
                        borderColor: palette.border,
                        backgroundColor: palette.surfaceMuted,
                        borderRadius: radius.sm,
                      },
                    ]}
                  />
                );
              }
              const brk = isBreakSlot(slot);
              const color = brk ? palette.textMuted : subjectColor(slot.subject_id, slot.subject_name);
              return (
                <View
                  key={day}
                  style={[
                    styles.cell,
                    {
                      width: cellWidth,
                      borderColor: brk ? palette.border : color,
                      backgroundColor: brk ? palette.surfaceMuted : `${color}22`,
                      borderRadius: radius.sm,
                    },
                  ]}
                >
                  <Text
                    numberOfLines={2}
                    style={{
                      color: brk ? palette.textMuted : color,
                      fontWeight: '700',
                      fontSize: 11,
                    }}
                  >
                    {slot.subject_name || (brk ? 'Break' : '—')}
                  </Text>
                  {showClassroom && slot.classroom_name ? (
                    <Text numberOfLines={1} style={{ color: palette.textSecondary, fontSize: 10 }}>
                      {slot.classroom_name}
                    </Text>
                  ) : null}
                  {showTeacher && slot.teacher_name ? (
                    <Text numberOfLines={1} style={{ color: palette.textSecondary, fontSize: 10 }}>
                      {slot.teacher_name}
                    </Text>
                  ) : null}
                </View>
              );
            })}
          </View>
        ))}
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'stretch',
    marginBottom: 4,
  },
  timeCol: {
    justifyContent: 'center',
  },
  headerCell: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  cell: {
    minHeight: 58,
    marginRight: 4,
    padding: 6,
    borderWidth: 1,
    justifyContent: 'center',
  },
});
