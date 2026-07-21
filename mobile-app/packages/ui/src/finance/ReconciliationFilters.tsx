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
  const { palette, spacing, typography, radius } = useTheme();

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
