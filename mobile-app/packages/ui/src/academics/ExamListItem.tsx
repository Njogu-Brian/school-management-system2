import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AccentIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import { ExamStatusBadge } from './ExamStatusBadge';
import type { ExamListItemData } from './types';

export interface ExamListItemProps {
  exam: ExamListItemData;
}

export const ExamListItem: React.FC<ExamListItemProps> = ({ exam }) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const meta = [exam.classroomName, exam.subjectName, exam.examTypeName].filter(Boolean).join(' · ');

  const body = (
    <View
      style={[
        styles.row,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
      <AccentIcon name="school-outline" tone="blue" size={44} iconSize={20} style={{ marginRight: spacing.sm }} />
      <View style={{ flex: 1 }}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.titleSmall.fontSize,
            fontWeight: '700',
          }}
          numberOfLines={1}
        >
          {exam.name}
        </Text>
        {meta ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.body.fontSize,
              marginTop: 2,
            }}
            numberOfLines={1}
          >
            {meta}
          </Text>
        ) : null}
        {exam.startDate ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: 2,
            }}
          >
            {exam.startDate}
          </Text>
        ) : null}
        <View style={{ marginTop: spacing.xs }}>
          <ExamStatusBadge status={exam.status} compact />
        </View>
      </View>
      <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
    </View>
  );

  if (exam.onPress) {
    return (
      <Pressable
        onPress={exam.onPress}
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
});
