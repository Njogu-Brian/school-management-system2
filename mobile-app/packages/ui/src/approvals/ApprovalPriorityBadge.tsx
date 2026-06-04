import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ApprovalPriority } from './types';

const LABELS: Record<ApprovalPriority, string> = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
};

export interface ApprovalPriorityBadgeProps {
  priority: ApprovalPriority;
  compact?: boolean;
}

export const ApprovalPriorityBadge: React.FC<ApprovalPriorityBadgeProps> = ({
  priority,
  compact,
}) => {
  const { palette, colors, fontSizes, radius } = useTheme();

  const tone = (() => {
    switch (priority) {
      case 'critical':
        return { bg: `${colors.error}20`, fg: colors.error };
      case 'high':
        return { bg: `${colors.warning}22`, fg: colors.warning };
      case 'medium':
        return { bg: `${colors.primary}14`, fg: colors.primary };
      default:
        return { bg: `${palette.textSecondary}18`, fg: palette.textSecondary };
    }
  })();

  return (
    <View
      style={[
        styles.badge,
        compact && styles.compact,
        { backgroundColor: tone.bg, borderRadius: radius.sm },
      ]}
    >
      <Text
        style={[
          styles.text,
          { color: tone.fg, fontSize: compact ? fontSizes.xs - 1 : fontSizes.xs },
        ]}
      >
        {LABELS[priority]}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    alignSelf: 'flex-start',
  },
  compact: { paddingHorizontal: 6, paddingVertical: 2 },
  text: { fontWeight: '700', letterSpacing: 0.3, textTransform: 'uppercase' },
});
