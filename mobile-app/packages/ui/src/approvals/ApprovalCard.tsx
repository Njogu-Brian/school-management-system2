import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { ApprovalPriorityBadge } from './ApprovalPriorityBadge';
import { ApprovalStatusBadge } from './ApprovalStatusBadge';
import type { ApprovalCardData, ApprovalPriority } from './types';

export interface ApprovalCardProps {
  item: ApprovalCardData;
}

function priorityStripeColor(
  priority: ApprovalPriority,
  colors: { error: string; warning: string; success: string },
): string {
  switch (priority) {
    case 'critical':
    case 'high':
      return colors.error;
    case 'medium':
      return colors.warning;
    case 'low':
    default:
      return colors.success;
  }
}

export const ApprovalCard: React.FC<ApprovalCardProps> = ({ item }) => {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();
  const stripe = priorityStripeColor(item.priority, colors);

  const content = (
    <View
      style={[
        styles.card,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderLeftColor: stripe,
          borderRadius: radius.card,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <View style={styles.topRow}>
        <View style={[styles.titleBlock, { marginRight: spacing.sm }]}>
          <Text
            style={[
              styles.title,
              {
                color: palette.textPrimary,
                fontSize: typography.titleSmall.fontSize,
                fontWeight: typography.titleSmall.fontWeight,
              },
            ]}
            numberOfLines={1}
          >
            {item.title}
          </Text>
          {item.sourceLabel ? (
            <Text
              style={[
                styles.source,
                {
                  color: palette.textSecondary,
                  fontSize: typography.overline.fontSize,
                  marginTop: spacing.xs,
                },
              ]}
            >
              {item.sourceLabel}
            </Text>
          ) : null}
        </View>
        <Ionicons name="chevron-forward" size={18} color={palette.textMuted} />
      </View>

      <Text
        style={[
          styles.subtitle,
          {
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginTop: spacing.xs,
          },
        ]}
        numberOfLines={2}
      >
        {item.subtitle}
      </Text>

      <View style={[styles.badges, { marginTop: spacing.sm, gap: spacing.xs }]}>
        <ApprovalStatusBadge status={item.status} compact />
        <ApprovalPriorityBadge priority={item.priority} compact />
      </View>

      {item.requestedAtLabel ? (
        <Text
          style={[
            styles.meta,
            {
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              marginTop: spacing.xs,
            },
          ]}
        >
          {item.requestedAtLabel}
        </Text>
      ) : null}
    </View>
  );

  if (item.onPress) {
    return (
      <Pressable
        onPress={item.onPress}
        accessibilityRole="button"
        style={({ pressed }) => [{ opacity: pressed ? 0.92 : 1 }]}
      >
        {content}
      </Pressable>
    );
  }

  return content;
};

const styles = StyleSheet.create({
  card: {
    borderWidth: StyleSheet.hairlineWidth,
    borderLeftWidth: 4,
  },
  topRow: { flexDirection: 'row', alignItems: 'flex-start' },
  titleBlock: { flex: 1 },
  title: {},
  source: { fontWeight: '500' },
  subtitle: {},
  badges: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
  meta: { fontWeight: '500' },
});
