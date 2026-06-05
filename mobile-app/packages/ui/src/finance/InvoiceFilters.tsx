import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { invoiceStatusLabel } from './InvoiceStatusBadge';
import type { InvoiceStatusFilter } from './types';

const OPTIONS: InvoiceStatusFilter[] = ['all', 'issued', 'partially_paid', 'paid', 'overdue'];

export interface InvoiceFiltersProps {
  status: InvoiceStatusFilter;
  onStatusChange: (status: InvoiceStatusFilter) => void;
}

export const InvoiceFilters: React.FC<InvoiceFiltersProps> = ({ status, onStatusChange }) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={[styles.row, { gap: spacing.xs, paddingBottom: spacing.sm }]}
    >
      {OPTIONS.map((option) => {
        const active = status === option;
        const label = option === 'all' ? 'All' : invoiceStatusLabel(option);
        return (
          <Pressable
            key={option}
            onPress={() => onStatusChange(option)}
            style={[
              styles.chip,
              {
                borderRadius: radius.full,
                backgroundColor: active ? `${colors.primary}18` : palette.surface,
                borderColor: active ? colors.primary : palette.border,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.xs,
              },
            ]}
          >
            <Text
              style={{
                color: active ? colors.primary : palette.textSecondary,
                fontSize: fontSizes.xs,
                fontWeight: '700',
              }}
            >
              {label}
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
