import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface FinanceFieldRow {
  label: string;
  value: string | null | undefined;
}

export interface FinanceFieldSectionProps {
  title: string;
  rows: FinanceFieldRow[];
}

export const FinanceFieldSection: React.FC<FinanceFieldSectionProps> = ({ title, rows }) => {
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.card,
          padding: spacing.md,
          marginBottom: spacing.md,
        },
      ]}
    >
      <Text
        style={{
          color: palette.textMain,
          fontSize: typography.titleSmall.fontSize,
          fontWeight: '700',
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      {rows.map((row) => (
        <View
          key={row.label}
          style={[styles.row, { borderBottomColor: palette.border, paddingVertical: spacing.sm }]}
        >
          <Text style={{ color: palette.textSub, fontSize: typography.body.fontSize, flex: 1 }}>{row.label}</Text>
          <Text
            style={{
              color: palette.textMain,
              fontSize: typography.body.fontSize,
              fontWeight: '600',
              flex: 1.2,
              textAlign: 'right',
            }}
          >
            {row.value?.trim() ? row.value : '—'}
          </Text>
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
