import React from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import { FilterChip } from '../primitives/FilterChip';
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
  const { spacing } = useTheme();

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
          <FilterChip
            key={option}
            label={label}
            active={active}
            onPress={() => onStatusChange(option)}
          />
        );
      })}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
});
