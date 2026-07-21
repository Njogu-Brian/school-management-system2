import type { FinanceTransactionViewFilter } from './types';
import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

const OPTIONS: FinanceTransactionViewFilter[] = [
  'all',
  'auto-assigned',
  'collected',
  'draft',
  'manual-assigned',
  'unassigned',
  'archived',
  'swimming',
];

const LABELS: Record<FinanceTransactionViewFilter, string> = {
  all: 'All',
  'auto-assigned': 'Auto assigned',
  collected: 'Collected',
  draft: 'Draft',
  'manual-assigned': 'Manual assigned',
  unassigned: 'Unassigned',
  archived: 'Archived',
  swimming: 'Swimming',
  duplicate: 'Duplicate',
};

export interface TransactionViewFiltersProps {
  view: FinanceTransactionViewFilter;
  onViewChange: (view: FinanceTransactionViewFilter) => void;
}

export const TransactionViewFilters: React.FC<TransactionViewFiltersProps> = ({
  view,
  onViewChange,
}) => {
  const { palette, spacing, typography, radius } = useTheme();

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={[styles.row, { gap: spacing.xs, paddingBottom: spacing.sm }]}
    >
      {OPTIONS.map((option) => {
        const active = view === option;
        return (
          <Pressable
            key={option}
            onPress={() => onViewChange(option)}
            style={[
              styles.chip,
              {
                borderRadius: radius.chip,
                backgroundColor: active ? `${palette.primary}18` : palette.surface,
                borderColor: active ? palette.primary : palette.border,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.xs,
              },
            ]}
          >
            <Text
              style={{
                color: active ? palette.primary : palette.textSub,
                fontSize: typography.caption.fontSize,
                fontWeight: '700',
              }}
            >
              {LABELS[option]}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
  chip: { borderWidth: StyleSheet.hairlineWidth },
});
