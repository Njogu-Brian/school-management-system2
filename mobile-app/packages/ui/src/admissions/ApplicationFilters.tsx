import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { applicationStatusLabel } from './ApplicationStatusBadge';
import type { ApplicationStatusFilter } from './types';

const FILTER_OPTIONS: ApplicationStatusFilter[] = [
  'all',
  'pending',
  'under_review',
  'waitlisted',
  'enrolled',
  'rejected',
];

export interface ApplicationFiltersProps {
  status: ApplicationStatusFilter;
  onStatusChange: (status: ApplicationStatusFilter) => void;
}

export const ApplicationFilters: React.FC<ApplicationFiltersProps> = ({
  status,
  onStatusChange,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={[styles.row, { gap: spacing.xs, paddingBottom: spacing.sm }]}
    >
      {FILTER_OPTIONS.map((option) => {
        const active = status === option;
        const label = option === 'all' ? 'All' : applicationStatusLabel(option);
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
