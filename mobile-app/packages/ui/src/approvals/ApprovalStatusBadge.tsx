import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ApprovalStatus } from './types';

const LABELS: Record<ApprovalStatus, string> = {
  pending: 'Pending',
  approved: 'Approved',
  rejected: 'Rejected',
  escalated: 'Escalated',
  expired: 'Expired',
};

export interface ApprovalStatusBadgeProps {
  status: ApprovalStatus;
  compact?: boolean;
}

export const ApprovalStatusBadge: React.FC<ApprovalStatusBadgeProps> = ({
  status,
  compact,
}) => {
  const { palette, colors, fontSizes, radius } = useTheme();

  const tone = (() => {
    switch (status) {
      case 'approved':
        return { bg: `${colors.success}18`, fg: colors.success };
      case 'rejected':
        return { bg: `${colors.error}18`, fg: colors.error };
      case 'escalated':
        return { bg: `${colors.warning}22`, fg: colors.warning };
      case 'expired':
        return { bg: `${palette.textSecondary}22`, fg: palette.textSecondary };
      default:
        return { bg: `${colors.primary}14`, fg: colors.primary };
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
        {LABELS[status]}
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
