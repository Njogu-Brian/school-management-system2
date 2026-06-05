import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ReconciliationQueueFilter } from './types';

const OPTIONS: ReconciliationQueueFilter[] = ['pending', 'confirmed', 'rejected'];

const LABELS: Record<ReconciliationQueueFilter, string> = {
  pending: 'Pending',
  confirmed: 'Confirmed',
  rejected: 'Rejected',
};

export interface ReconciliationFiltersProps {
  queue: ReconciliationQueueFilter;
  onQueueChange: (queue: ReconciliationQueueFilter) => void;
}

export const ReconciliationFilters: React.FC<ReconciliationFiltersProps> = ({
  queue,
  onQueueChange,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={[styles.row, { gap: spacing.xs, paddingBottom: spacing.sm }]}
    >
      {OPTIONS.map((option) => {
        const active = queue === option;
        return (
          <Pressable
            key={option}
            onPress={() => onQueueChange(option)}
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
