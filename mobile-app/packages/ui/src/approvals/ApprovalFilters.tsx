import React from 'react';
import { View } from 'react-native';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';
import { useTheme } from '../theme/ThemeContext';
import type { ApprovalPriority, ApprovalSourceType, ApprovalStatus } from './types';

export interface ApprovalFilterChipOption<T extends string> {
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

const STATUS_CHIPS: ApprovalFilterChipOption<ApprovalStatus | 'all'>[] = [
  { value: 'all', label: 'All' },
  { value: 'pending', label: 'Pending' },
  { value: 'escalated', label: 'Escalated' },
  { value: 'expired', label: 'Expired' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

const PRIORITY_CHIPS: ApprovalFilterChipOption<ApprovalPriority | 'all'>[] = [
  { value: 'all', label: 'All priorities' },
  { value: 'critical', label: 'Critical' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
];

const SOURCE_CHIPS: ApprovalFilterChipOption<ApprovalSourceType | 'all'>[] = [
  { value: 'all', label: 'All types' },
  { value: 'leave_request', label: 'Leave' },
  { value: 'lesson_plan', label: 'Lesson plans' },
  { value: 'online_admission', label: 'Admissions' },
];

function ChipRow<T extends string>({
  label,
  chips,
  selected,
  onSelect,
}: {
  label: string;
  chips: ApprovalFilterChipOption<T>[];
  selected: T;
  onSelect: (v: T) => void;
}) {
  return (
    <FilterChipRow label={label}>
      {chips.map((chip) => (
        <FilterChip
          key={chip.value}
          label={chip.label}
          active={chip.value === selected}
          onPress={() => onSelect(chip.value)}
        />
      ))}
    </FilterChipRow>
  );
}

/**
 * Chip rows for the approvals filter sheet (FilterBottomSheet via ApprovalsInbox).
 * Keep FilterChip usage; do not render as an always-visible multi-row wall on the list.
 */
export const ApprovalFilters: React.FC<ApprovalFiltersProps> = ({
  status,
  priority,
  sourceType,
  onStatusChange,
  onPriorityChange,
  onSourceTypeChange,
}) => {
  const { spacing } = useTheme();

  return (
    <View style={{ marginBottom: spacing.md }}>
      <ChipRow label="Status" chips={STATUS_CHIPS} selected={status} onSelect={onStatusChange} />
      <ChipRow
        label="Priority"
        chips={PRIORITY_CHIPS}
        selected={priority}
        onSelect={onPriorityChange}
      />
      <ChipRow
        label="Type"
        chips={SOURCE_CHIPS}
        selected={sourceType}
        onSelect={onSourceTypeChange}
      />
    </View>
  );
};
