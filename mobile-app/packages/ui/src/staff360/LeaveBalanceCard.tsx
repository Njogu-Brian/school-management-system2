import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
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
  const { palette, spacing, fontSizes, radius, shadows } = useTheme();

  if (balances.length === 0) {
    return (
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
        No leave balances configured for the active year.
      </Text>
    );
  }

  return (
    <View style={[styles.grid, { gap: spacing.sm }]}>
      {balances.map((b) => (
        <View
          key={b.id}
          style={[
            styles.cell,
            {
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderRadius: radius.md,
              padding: spacing.md,
            },
            shadows.sm,
          ]}
        >
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
            {b.leaveTypeName}
          </Text>
          <Text
            style={{
              color: palette.textPrimary,
              fontSize: fontSizes.xl,
              fontWeight: '700',
              marginTop: 4,
            }}
          >
            {b.remainingDays}d
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
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
