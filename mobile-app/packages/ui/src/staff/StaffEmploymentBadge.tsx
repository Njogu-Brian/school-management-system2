import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { StaffEmploymentStatusUi } from './types';

const LABELS: Record<StaffEmploymentStatusUi, string> = {
  active: 'Active',
  on_leave: 'On leave',
  suspended: 'Suspended',
  terminated: 'Terminated',
};

const COLORS: Record<StaffEmploymentStatusUi, { bg: string; fg: string }> = {
  active: { bg: '#10b98122', fg: '#059669' },
  on_leave: { bg: '#f59e0b22', fg: '#d97706' },
  suspended: { bg: '#ef444422', fg: '#dc2626' },
  terminated: { bg: '#6b728022', fg: '#4b5563' },
};

export interface StaffEmploymentBadgeProps {
  status: StaffEmploymentStatusUi | null;
  compact?: boolean;
}

export const StaffEmploymentBadge: React.FC<StaffEmploymentBadgeProps> = ({
  status,
  compact,
}) => {
  const { typography, radius, spacing } = useTheme();
  if (!status) return null;

  const palette = COLORS[status] ?? COLORS.active;

  return (
    <View
      style={[
        styles.badge,
        {
          backgroundColor: palette.bg,
          borderRadius: radius.full,
          paddingHorizontal: compact ? spacing.xs : spacing.sm,
          paddingVertical: compact ? 2 : spacing.xs,
        },
      ]}
    >
      <Text
        style={{
          color: palette.fg,
          fontSize: compact ? typography.tiny.fontSize : typography.overline.fontSize,
        }}
      >
        {LABELS[status]}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: { alignSelf: 'flex-start' },
});
