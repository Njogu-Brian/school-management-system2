import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { StudentStatusBadge } from '../students/StudentStatusBadge';
import { useTheme } from '../theme/ThemeContext';
import type { Student360HeaderData } from './types';

export interface Student360HeaderProps {
  student: Student360HeaderData;
}

export const Student360Header: React.FC<Student360HeaderProps> = ({ student }) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <View
      style={[
        styles.card,
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
        <View style={[styles.avatar, styles.avatarPh, { backgroundColor: palette.accent }]}>
          <Ionicons name="person" size={36} color={colors.primary} />
        </View>
      )}
      <View style={styles.meta}>
        <Text style={[styles.name, { color: palette.textPrimary, fontSize: fontSizes.lg }]}>
          {student.fullName}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
          {student.admissionNumber}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }}>
          {student.classLabel}
        </Text>
        <View style={[styles.badges, { marginTop: spacing.xs, gap: spacing.xs }]}>
          <StudentStatusBadge
            kind="enrollment"
            enrollmentStatus={student.enrollmentStatus ?? 'active'}
            compact
          />
          {student.feeStatus ? (
            <StudentStatusBadge kind="fee" feeStatus={student.feeStatus} compact />
          ) : null}
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  avatar: { width: 72, height: 72, borderRadius: 36, marginRight: 14 },
  avatarPh: { alignItems: 'center', justifyContent: 'center' },
  meta: { flex: 1 },
  name: { fontWeight: '700' },
  badges: { flexDirection: 'row', flexWrap: 'wrap' },
});
