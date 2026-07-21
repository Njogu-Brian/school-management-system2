import React from 'react';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';

const STATUS_OPTIONS = [
  { value: '', label: 'All' },
  { value: 'draft', label: 'Draft' },
  { value: 'marking', label: 'Marking' },
  { value: 'moderation', label: 'Moderation' },
  { value: 'published', label: 'Published' },
];

export interface ExamFiltersProps {
  status: string;
  onStatusChange: (status: string) => void;
}

export const ExamFilters: React.FC<ExamFiltersProps> = ({ status, onStatusChange }) => (
  <FilterChipRow label="Status">
    {STATUS_OPTIONS.map((opt) => (
      <FilterChip
        key={opt.label}
        label={opt.label}
        active={status === opt.value}
        onPress={() => onStatusChange(opt.value)}
      />
    ))}
  </FilterChipRow>
);
