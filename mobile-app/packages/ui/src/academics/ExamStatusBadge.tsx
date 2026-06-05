import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

const STATUS_COLORS: Record<string, { bg: string; fg: string }> = {
  draft: { bg: '#E5E7EB', fg: '#374151' },
  open: { bg: '#DBEAFE', fg: '#1D4ED8' },
  marking: { bg: '#FEF3C7', fg: '#B45309' },
  moderation: { bg: '#FFEDD5', fg: '#C2410C' },
  approved: { bg: '#D1FAE5', fg: '#047857' },
  published: { bg: '#DCFCE7', fg: '#15803D' },
  locked: { bg: '#FEE2E2', fg: '#B91C1C' },
};

export interface ExamStatusBadgeProps {
  status: string;
  compact?: boolean;
}

export const ExamStatusBadge: React.FC<ExamStatusBadgeProps> = ({ status, compact }) => {
  const { fontSizes, radius } = useTheme();
  const key = status.toLowerCase();
  const colors = STATUS_COLORS[key] ?? { bg: '#F3F4F6', fg: '#4B5563' };
  const label = key.charAt(0).toUpperCase() + key.slice(1);

  return (
    <View
      style={[
        styles.badge,
        {
          backgroundColor: colors.bg,
          borderRadius: radius.sm,
          paddingHorizontal: compact ? 6 : 8,
          paddingVertical: compact ? 2 : 4,
        },
      ]}
    >
      <Text style={{ color: colors.fg, fontSize: compact ? fontSizes.xs - 1 : fontSizes.xs, fontWeight: '700' }}>
        {label}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: { alignSelf: 'flex-start' },
});
