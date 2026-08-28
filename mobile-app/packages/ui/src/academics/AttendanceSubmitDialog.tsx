import React from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Button } from '../primitives/Button';
import { useTheme } from '../theme/ThemeContext';

export type AttendanceSubmitSummary = {
  present: number;
  absent: number;
  late: number;
  total: number;
  absentNames: string[];
};

export function summarizeAttendanceMarks(
  students: Array<{ id: number; name: string }>,
  statusById: Record<number, string>,
): AttendanceSubmitSummary {
  let present = 0;
  let absent = 0;
  let late = 0;
  const absentNames: string[] = [];
  for (const student of students) {
    const status = statusById[student.id] ?? 'unmarked';
    if (status === 'present') {
      present += 1;
    } else if (status === 'absent') {
      absent += 1;
      absentNames.push(student.name);
    } else if (status === 'late') {
      late += 1;
    }
  }
  return { present, absent, late, total: present + absent + late, absentNames };
}

export interface AttendanceSubmitDialogProps {
  visible: boolean;
  date: string;
  summary: AttendanceSubmitSummary;
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

function StatCard({
  label,
  value,
  tone,
}: {
  label: string;
  value: number;
  tone: 'success' | 'danger' | 'warning' | 'brand';
}) {
  const { semantic, palette, spacing, typography, radius } = useTheme();
  const colors = semantic[tone];
  return (
    <View
      style={[
        styles.statCard,
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          borderLeftColor: colors.fg,
          paddingVertical: spacing.sm,
          paddingHorizontal: spacing.sm,
        },
      ]}
    >
      <Text
        style={{
          color: colors.fg,
          fontSize: typography.title.fontSize,
          fontWeight: '800',
        }}
      >
        {value}
      </Text>
      <Text
        style={{
          color: palette.textMuted,
          fontSize: typography.caption.fontSize,
          fontWeight: '600',
          marginTop: 2,
        }}
      >
        {label}
      </Text>
    </View>
  );
}

/** Confirm attendance before sending — present / absent / late / total + absent names. */
export const AttendanceSubmitDialog: React.FC<AttendanceSubmitDialogProps> = ({
  visible,
  date,
  summary,
  loading = false,
  onConfirm,
  onCancel,
}) => {
  const { palette, spacing, typography, radius, opacity, elevation, isDark } = useTheme();
  const dialogBg = isDark ? '#2B3444' : '#FFFFFF';
  const textMain = isDark ? '#F3F6FB' : palette.textMain;
  const textSub = isDark ? '#C5CEDC' : palette.textSub;
  const border = isDark ? 'rgba(255,255,255,0.14)' : palette.borderSubtle;

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={loading ? undefined : onCancel}
      statusBarTranslucent
    >
      <View style={styles.root}>
        <Pressable
          style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${Math.min(opacity.scrim, 0.5)})` }]}
          onPress={loading ? undefined : onCancel}
          accessibilityRole="button"
          accessibilityLabel="Cancel"
        />
        <View style={styles.center} pointerEvents="box-none">
          <View
            style={[
              styles.card,
              elevation[4] ?? elevation[3],
              {
                backgroundColor: dialogBg,
                borderRadius: radius.dialog,
                padding: spacing.lg,
                borderColor: border,
              },
            ]}
          >
            <Text
              style={{
                color: textMain,
                fontSize: typography.title.fontSize,
                fontWeight: typography.title.fontWeight,
              }}
            >
              Confirm attendance
            </Text>
            <Text
              style={{
                color: textSub,
                fontSize: typography.body.fontSize,
                marginTop: spacing.xs,
                marginBottom: spacing.md,
              }}
            >
              Date: {date}
            </Text>

            <ScrollView
              style={{ maxHeight: 360 }}
              nestedScrollEnabled
              keyboardShouldPersistTaps="handled"
            >
              <View style={[styles.statGrid, { gap: spacing.sm, marginBottom: spacing.md }]}>
                <StatCard label="Present" value={summary.present} tone="success" />
                <StatCard label="Absent" value={summary.absent} tone="danger" />
                <StatCard label="Late" value={summary.late} tone="warning" />
                <StatCard label="Total" value={summary.total} tone="brand" />
              </View>

              {summary.absent > 0 ? (
                <View style={{ marginBottom: spacing.md }}>
                  <Text
                    style={{
                      color: textMain,
                      fontSize: typography.body.fontSize,
                      fontWeight: '700',
                      marginBottom: spacing.xs,
                    }}
                  >
                    Absent students
                  </Text>
                  {summary.absentNames.map((name, index) => (
                    <Text
                      key={`${index}-${name}`}
                      style={{
                        color: textSub,
                        fontSize: typography.body.fontSize,
                        lineHeight: typography.body.lineHeight,
                        paddingVertical: 2,
                      }}
                    >
                      {name}
                    </Text>
                  ))}
                </View>
              ) : (
                <Text
                  style={{
                    color: textSub,
                    fontSize: typography.body.fontSize,
                    marginBottom: spacing.md,
                  }}
                >
                  No students marked absent.
                </Text>
              )}
            </ScrollView>

            <View style={{ gap: spacing.sm, marginTop: spacing.md }}>
              <Button
                label="Confirm & Submit"
                onPress={onConfirm}
                loading={loading}
              />
              <Button label="Cancel" onPress={onCancel} variant="ghost" disabled={loading} />
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: { flex: 1 },
  scrim: { ...StyleSheet.absoluteFillObject },
  center: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    zIndex: 2,
    elevation: 8,
  },
  card: {
    width: '100%',
    maxWidth: 420,
    maxHeight: '88%',
    borderWidth: StyleSheet.hairlineWidth,
    zIndex: 3,
  },
  statGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  statCard: {
    flexGrow: 1,
    flexBasis: '46%',
    minWidth: 120,
    borderWidth: StyleSheet.hairlineWidth,
    borderLeftWidth: 4,
  },
});
