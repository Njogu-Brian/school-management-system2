import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AccentIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { AssessmentCardData } from './types';

export interface AssessmentCardProps {
  data: AssessmentCardData;
}

export const AssessmentCard: React.FC<AssessmentCardProps> = ({ data }) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const { item } = data;
  const score =
    item.scoreDisplay ??
    (item.scorePercent != null ? `${item.scorePercent}%` : item.gradeLabel ?? '—');

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
      <AccentIcon name="analytics-outline" tone="violet" size={44} iconSize={20} style={{ marginRight: spacing.sm }} />
      <View style={{ flex: 1 }}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.titleSmall.fontSize,
            fontWeight: '700',
          }}
          numberOfLines={1}
        >
          {item.title}
        </Text>
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginTop: 2,
          }}
        >
          {item.typeLabel}
          {item.subjectName ? ` · ${item.subjectName}` : ''}
        </Text>
        {item.assessedOn ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: 2,
            }}
          >
            {item.assessedOn}
          </Text>
        ) : null}
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.body.fontSize,
            fontWeight: '600',
            marginTop: spacing.xs,
          }}
        >
          {score}
        </Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
    </View>
  );

  if (data.onPress) {
    return (
      <Pressable
        onPress={data.onPress}
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
