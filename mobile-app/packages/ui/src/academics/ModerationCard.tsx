import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ModerationCardData } from './types';

export interface ModerationCardProps {
  data: ModerationCardData;
}

export const ModerationCard: React.FC<ModerationCardProps> = ({ data }) => {
  const { palette, spacing, fontSizes, radius, shadows } = useTheme();
  const { plan } = data;
  const meta = [plan.className, plan.subjectName, plan.teacherName].filter(Boolean).join(' · ');

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
      <View style={{ flex: 1 }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700' }} numberOfLines={2}>
          {plan.topic}
        </Text>
        {meta ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 2 }} numberOfLines={1}>
            {meta}
          </Text>
        ) : null}
        {plan.plannedDate ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
            {plan.plannedDate}
            {plan.isLate ? ' · Late' : ''}
          </Text>
        ) : null}
      </View>
      <Ionicons name="chevron-forward" size={18} color={palette.textSecondary} />
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
