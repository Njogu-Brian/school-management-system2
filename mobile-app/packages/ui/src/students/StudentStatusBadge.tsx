import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { StudentEnrollmentStatus, StudentFeeStatus } from './types';

export type StudentStatusKind = 'enrollment' | 'fee';

export interface StudentStatusBadgeProps {
  kind: StudentStatusKind;
  enrollmentStatus?: StudentEnrollmentStatus;
  feeStatus?: StudentFeeStatus;
  compact?: boolean;
}

export const StudentStatusBadge: React.FC<StudentStatusBadgeProps> = ({
  kind,
  enrollmentStatus = 'active',
  feeStatus,
  compact,
}) => {
  const { palette, colors, fontSizes, radius } = useTheme();

  const label =
    kind === 'fee'
      ? feeStatus === 'pending'
        ? 'Fees pending'
        : feeStatus === 'cleared'
          ? 'Fees cleared'
          : 'Fees —'
      : enrollmentStatus === 'active'
        ? 'Active'
        : String(enrollmentStatus);

  const tone = (() => {
    if (kind === 'fee') {
      if (feeStatus === 'pending') return { bg: `${colors.warning}22`, fg: colors.warning };
      if (feeStatus === 'cleared') return { bg: `${colors.success}18`, fg: colors.success };
      return { bg: `${palette.textSecondary}18`, fg: palette.textSecondary };
    }
    if (enrollmentStatus === 'active') return { bg: `${colors.success}18`, fg: colors.success };
    return { bg: `${palette.textSecondary}22`, fg: palette.textSecondary };
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
        {label}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: { paddingHorizontal: 8, paddingVertical: 3, alignSelf: 'flex-start' },
  compact: { paddingHorizontal: 6, paddingVertical: 2 },
  text: { fontWeight: '700', letterSpacing: 0.3, textTransform: 'uppercase' },
});
