import { formatFinanceAmount, useFinanceDashboardKpis } from '@erp/core';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Soft3DIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';

export type FinanceListKpiStripVariant = 'billing' | 'collections';

export type FinanceListKpiStripProps = {
  variant: FinanceListKpiStripVariant;
  /** Called when a KPI cell is pressed (e.g. open arrears list). */
  onCellPress?: (key: string) => void;
};

/**
 * Compact summary strip for Billing / Collections list heroes.
 * Uses GET /finance/summary via useFinanceDashboardKpis (same source as Finance dashboard).
 */
export const FinanceListKpiStrip: React.FC<FinanceListKpiStripProps> = ({
  variant,
  onCellPress,
}) => {
  const { palette, spacing, typography, radius } = useTheme();
  const kpisQuery = useFinanceDashboardKpis({ enabled: true });
  const data = kpisQuery.data;

  const cells =
    variant === 'billing'
      ? [
          {
            key: 'outstanding',
            label: 'Outstanding',
            value: formatFinanceAmount(data?.outstandingFees ?? 0),
            icon: 'receipt-outline' as const,
          },
          {
            key: 'arrears',
            label: 'In arrears',
            value: String(data?.studentsInArrears ?? 0),
            icon: 'people-outline' as const,
          },
        ]
      : [
          {
            key: 'today',
            label: 'Today',
            value: formatFinanceAmount(data?.collectedToday ?? 0),
            icon: 'cash-outline' as const,
          },
          {
            key: 'month',
            label: 'This month',
            value: formatFinanceAmount(data?.collectedThisMonth ?? 0),
            icon: 'wallet-outline' as const,
          },
        ];

  return (
    <View style={[styles.row, { gap: spacing.sm }]}>
      {cells.map((cell) => {
        const interactive = Boolean(onCellPress);
        const Inner = (
          <>
            <Soft3DIcon name={cell.icon} size={32} />
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                fontWeight: '600',
                marginTop: spacing.xs,
              }}
            >
              {cell.label}
            </Text>
            <Text
              style={{
                color: palette.textMain,
                fontSize: typography.body.fontSize,
                fontWeight: '800',
                marginTop: 2,
              }}
              numberOfLines={1}
            >
              {kpisQuery.isLoading ? '…' : cell.value}
            </Text>
          </>
        );

        const cellStyle = [
          styles.cell,
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            borderRadius: radius.card,
            padding: spacing.mdSm,
          },
        ];

        if (interactive) {
          return (
            <Pressable
              key={cell.key}
              accessibilityRole="button"
              accessibilityLabel={cell.label}
              onPress={() => onCellPress?.(cell.key)}
              style={cellStyle}
            >
              {Inner}
            </Pressable>
          );
        }

        return (
          <View key={cell.key} style={cellStyle}>
            {Inner}
          </View>
        );
      })}
    </View>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row' },
  cell: {
    flex: 1,
    borderWidth: StyleSheet.hairlineWidth,
  },
});
