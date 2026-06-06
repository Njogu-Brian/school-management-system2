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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  const classLine = [student.classLabel, student.streamName].filter(Boolean).join(' · ');

  const body = (
    <View
      style={[
        styles.row,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      {student.avatarUrl ? (
        <Image source={{ uri: student.avatarUrl }} style={[styles.avatar, { borderRadius: radius.lg }]} />
      ) : (
        <View
          style={[
            styles.avatar,
            styles.avatarPlaceholder,
            { backgroundColor: `${colors.primary}12`, borderRadius: radius.lg },
          ]}
        >
          <Ionicons name="person-outline" size={22} color={colors.primary} />
        </View>
      )}

      <View style={styles.content}>
        <Text
          style={[
            styles.name,
            { color: palette.textPrimary, fontSize: typography.body.fontSize, fontWeight: '600' },
          ]}
          numberOfLines={1}
        >
          {student.fullName}
        </Text>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
          {student.admissionNumber}
        </Text>
        {classLine ? (
          <Text
            style={[
              styles.classLine,
              { color: palette.textSecondary, fontSize: typography.caption.fontSize },
            ]}
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

      <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
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
  avatar: { width: 48, height: 48, marginRight: 12 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  content: { flex: 1, marginRight: 8 },
  name: {},
  classLine: { marginTop: 2 },
  badges: { flexDirection: 'row', flexWrap: 'wrap' },
});
