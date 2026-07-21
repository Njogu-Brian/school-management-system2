import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { EmptyState } from '../feedback/EmptyState';
import { useTheme } from '../theme/ThemeContext';

export interface LeaveBalanceCardData {
  id: number;
  leaveTypeName: string;
  remainingDays: number;
  usedDays: number;
  entitlementDays: number;
}

export interface LeaveBalanceCardProps {
  balances: LeaveBalanceCardData[];
}

export const LeaveBalanceCards: React.FC<LeaveBalanceCardProps> = ({ balances }) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();

  if (balances.length === 0) {
    return (
      <EmptyState
        title="No leave balances"
        message="No leave balances configured for the active year."
        icon="calendar-outline"
      />
    );
  }

  return (
    <View style={[styles.grid, { gap: spacing.sm }]}>
      {balances.map((b) => (
        <View
          key={b.id}
          style={[
            styles.cell,
            elevation[1],
            {
              backgroundColor: palette.surfaceRaised,
              borderColor: palette.borderSubtle,
              borderRadius: radius.card,
              padding: spacing.md,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              fontWeight: '600',
            }}
          >
            {b.leaveTypeName}
          </Text>
          <Text
            style={{
              color: palette.textPrimary,
              fontSize: typography.headline.fontSize,
              fontWeight: '700',
              marginTop: 4,
            }}
          >
            {b.remainingDays}d
          </Text>
          <Text
            style={{
              color: palette.textSecondary,
              fontSize: typography.overline.fontSize,
              marginTop: 2,
            }}
          >
            {b.usedDays} used · {b.entitlementDays} entitled
          </Text>
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap' },
  cell: {
    borderWidth: StyleSheet.hairlineWidth,
    minWidth: '46%',
    flex: 1,
  },
});
