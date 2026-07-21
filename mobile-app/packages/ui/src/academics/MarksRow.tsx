import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { MarksRowData } from './types';

export interface MarksRowProps {
  row: MarksRowData;
}

export const MarksRow: React.FC<MarksRowProps> = ({ row }) => {
  const { palette, spacing, typography } = useTheme();

  return (
    <View
      style={[
        styles.row,
        {
          borderBottomColor: palette.border,
          paddingVertical: spacing.sm,
          paddingHorizontal: spacing.xs,
        },
      ]}
    >
      <Text
        style={{
          flex: 2,
          color: palette.textPrimary,
          fontSize: typography.body.fontSize,
          fontWeight: '600',
        }}
        numberOfLines={1}
      >
        {row.studentName}
      </Text>
      <Text
        style={{
          flex: 1,
          color: palette.textPrimary,
          fontSize: typography.body.fontSize,
          textAlign: 'right',
        }}
      >
        {row.marks}/{row.totalMarks}
      </Text>
      <Text
        style={{
          flex: 1,
          color: palette.textSecondary,
          fontSize: typography.body.fontSize,
          textAlign: 'right',
        }}
      >
        {row.percentage}%
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
