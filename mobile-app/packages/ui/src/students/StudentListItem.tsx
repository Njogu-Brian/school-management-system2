import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { StudentStatusBadge } from './StudentStatusBadge';
import type { StudentListItemData } from './types';

export interface StudentListItemProps {
  student: StudentListItemData;
}

export const StudentListItem: React.FC<StudentListItemProps> = ({ student }) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  const classLine = [student.classLabel, student.streamName].filter(Boolean).join(' · ');

  const body = (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
        },
        shadows.sm,
      ]}
    >
      {student.avatarUrl ? (
        <Image source={{ uri: student.avatarUrl }} style={styles.avatar} />
      ) : (
        <View style={[styles.avatar, styles.avatarPlaceholder, { backgroundColor: palette.accent }]}>
          <Ionicons name="person-outline" size={22} color={colors.primary} />
        </View>
      )}

      <View style={styles.content}>
        <Text
          style={[styles.name, { color: palette.textPrimary, fontSize: fontSizes.md }]}
          numberOfLines={1}
        >
          {student.fullName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
          {student.admissionNumber}
        </Text>
        {classLine ? (
          <Text
            style={[styles.classLine, { color: palette.textSecondary, fontSize: fontSizes.sm }]}
            numberOfLines={1}
          >
            {classLine}
          </Text>
        ) : null}
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <StudentStatusBadge kind="enrollment" enrollmentStatus={student.enrollmentStatus} compact />
          {student.feeStatus ? (
            <StudentStatusBadge kind="fee" feeStatus={student.feeStatus} compact />
          ) : null}
        </View>
      </View>

      <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} />
    </View>
  );

  if (student.onPress) {
    return (
      <Pressable
        onPress={student.onPress}
        accessibilityRole="button"
        style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1, marginBottom: spacing.sm }]}
      >
        {body}
      </Pressable>
    );
  }

  return <View style={{ marginBottom: spacing.sm }}>{body}</View>;
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  avatar: { width: 48, height: 48, borderRadius: 24, marginRight: 12 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  content: { flex: 1, marginRight: 8 },
  name: { fontWeight: '700' },
  classLine: { marginTop: 2 },
  badges: { flexDirection: 'row', flexWrap: 'wrap' },
});
