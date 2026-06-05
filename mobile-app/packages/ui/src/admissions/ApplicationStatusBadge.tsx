import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ApplicationStatusFilter } from './types';

const STATUS_COLORS: Record<string, { bg: string; fg: string }> = {
  pending: { bg: '#fef3c7', fg: '#b45309' },
  under_review: { bg: '#dbeafe', fg: '#1d4ed8' },
  waitlisted: { bg: '#ede9fe', fg: '#6d28d9' },
  enrolled: { bg: '#dcfce7', fg: '#15803d' },
  rejected: { bg: '#fee2e2', fg: '#b91c1c' },
};

export interface ApplicationStatusBadgeProps {
  status: ApplicationStatusFilter | string;
  compact?: boolean;
}

export function applicationStatusLabel(status: string): string {
  switch (status) {
    case 'pending':
      return 'Pending';
    case 'under_review':
      return 'Under Review';
    case 'waitlisted':
      return 'Waitlisted';
    case 'enrolled':
      return 'Enrolled';
    case 'rejected':
      return 'Rejected';
    default:
      return status;
  }
}

export const ApplicationStatusBadge: React.FC<ApplicationStatusBadgeProps> = ({
  status,
  compact,
}) => {
  const { fontSizes, radius } = useTheme();
  const palette = STATUS_COLORS[status] ?? { bg: '#f3f4f6', fg: '#374151' };

  return (
    <View
      style={[
        styles.badge,
        {
          backgroundColor: palette.bg,
          borderRadius: radius.full,
          paddingHorizontal: compact ? 8 : 10,
          paddingVertical: compact ? 2 : 4,
        },
      ]}
    >
      <Text style={{ color: palette.fg, fontSize: compact ? fontSizes.xs : fontSizes.sm, fontWeight: '700' }}>
        {applicationStatusLabel(status)}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: { alignSelf: 'flex-start' },
});
