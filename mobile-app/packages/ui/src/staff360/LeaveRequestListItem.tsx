import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface LeaveRequestListItemData {
  id: number;
  leaveTypeName: string;
  startDate: string;
  endDate: string;
  days: number;
  status: string;
  reason?: string | null;
}

export interface LeaveRequestListItemProps {
  item: LeaveRequestListItemData;
}

function statusColor(status: string, colors: { success: string; warning: string; error: string }) {
  const s = status.toLowerCase();
  if (s === 'approved') return colors.success;
  if (s === 'pending') return colors.warning;
  if (s === 'rejected') return colors.error;
  return undefined;
}

export const LeaveRequestListItem: React.FC<LeaveRequestListItemProps> = ({ item }) => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const tint = statusColor(item.status, colors);

  return (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <View style={styles.header}>
        <Text
          style={{
            color: palette.textPrimary,
            fontWeight: '600',
            fontSize: typography.bodyLarge.fontSize,
          }}
        >
          {item.leaveTypeName}
        </Text>
        <Text
          style={{
            color: tint ?? palette.textSecondary,
            fontSize: typography.overline.fontSize,
            fontWeight: '700',
            textTransform: 'capitalize',
          }}
        >
          {item.status}
        </Text>
      </View>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.caption.fontSize,
          marginTop: 4,
        }}
      >
        {item.startDate} → {item.endDate} · {item.days} day{item.days === 1 ? '' : 's'}
      </Text>
      {item.reason ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.overline.fontSize,
            marginTop: 4,
          }}
        >
          {item.reason}
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
