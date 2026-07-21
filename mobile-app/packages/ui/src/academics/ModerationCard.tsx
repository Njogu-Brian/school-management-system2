import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { AccentIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { ModerationCardData } from './types';

export interface ModerationCardProps {
  data: ModerationCardData;
}

export const ModerationCard: React.FC<ModerationCardProps> = ({ data }) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const { plan } = data;
  const meta = [plan.className, plan.subjectName, plan.teacherName].filter(Boolean).join(' · ');

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
      <AccentIcon
        name="shield-checkmark-outline"
        tone={plan.isLate ? 'rose' : 'amber'}
        size={44}
        iconSize={20}
        style={{ marginRight: spacing.sm }}
      />
      <View style={{ flex: 1 }}>
        <Text
          style={{
            color: palette.textPrimary,
            fontSize: typography.titleSmall.fontSize,
            fontWeight: '700',
          }}
          numberOfLines={2}
        >
          {plan.topic}
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
        {plan.plannedDate ? (
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginTop: 2,
            }}
          >
            {plan.plannedDate}
            {plan.isLate ? ' · Late' : ''}
          </Text>
        ) : null}
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
