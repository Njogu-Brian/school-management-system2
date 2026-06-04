import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { ApprovalPriority, ApprovalSourceType, ApprovalStatus } from './types';

export interface FilterChip<T extends string> {
  value: T;
  label: string;
}

export interface ApprovalFiltersProps {
  status: ApprovalStatus | 'all';
  priority: ApprovalPriority | 'all';
  sourceType: ApprovalSourceType | 'all';
  onStatusChange: (value: ApprovalStatus | 'all') => void;
  onPriorityChange: (value: ApprovalPriority | 'all') => void;
  onSourceTypeChange: (value: ApprovalSourceType | 'all') => void;
}

const STATUS_CHIPS: FilterChip<ApprovalStatus | 'all'>[] = [
  { value: 'all', label: 'All' },
  { value: 'pending', label: 'Pending' },
  { value: 'escalated', label: 'Escalated' },
  { value: 'expired', label: 'Expired' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

const PRIORITY_CHIPS: FilterChip<ApprovalPriority | 'all'>[] = [
  { value: 'all', label: 'All priorities' },
  { value: 'critical', label: 'Critical' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
];

const SOURCE_CHIPS: FilterChip<ApprovalSourceType | 'all'>[] = [
  { value: 'all', label: 'All types' },
  { value: 'leave_request', label: 'Leave' },
  { value: 'lesson_plan', label: 'Lesson plans' },
];

function ChipRow<T extends string>({
  chips,
  selected,
  onSelect,
}: {
  chips: FilterChip<T>[];
  selected: T;
  onSelect: (v: T) => void;
}) {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={[styles.row, { gap: spacing.xs, paddingVertical: spacing.xs }]}
    >
      {chips.map((chip) => {
        const active = chip.value === selected;
        return (
          <Pressable
            key={chip.value}
            onPress={() => onSelect(chip.value)}
            style={[
              styles.chip,
              {
                borderRadius: radius.full,
                borderColor: active ? colors.primary : palette.border,
                backgroundColor: active ? `${colors.primary}14` : palette.surface,
              },
            ]}
          >
            <Text
              style={{
                color: active ? colors.primary : palette.textSecondary,
                fontSize: fontSizes.xs,
                fontWeight: active ? '700' : '500',
              }}
            >
              {chip.label}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

export const ApprovalFilters: React.FC<ApprovalFiltersProps> = ({
  status,
  priority,
  sourceType,
  onStatusChange,
  onPriorityChange,
  onSourceTypeChange,
}) => {
  const { spacing, palette, fontSizes } = useTheme();

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <Text style={[styles.label, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
        Status
      </Text>
      <ChipRow chips={STATUS_CHIPS} selected={status} onSelect={onStatusChange} />
      <Text
        style={[
          styles.label,
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.xs },
        ]}
      >
        Priority
      </Text>
      <ChipRow chips={PRIORITY_CHIPS} selected={priority} onSelect={onPriorityChange} />
      <Text
        style={[
          styles.label,
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.xs },
        ]}
      >
        Type
      </Text>
      <ChipRow chips={SOURCE_CHIPS} selected={sourceType} onSelect={onSourceTypeChange} />
    </View>
  );
};

const styles = StyleSheet.create({
  label: { fontWeight: '600', letterSpacing: 0.4, textTransform: 'uppercase', marginLeft: 2 },
  row: { flexDirection: 'row', alignItems: 'center' },
  chip: {
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
});
