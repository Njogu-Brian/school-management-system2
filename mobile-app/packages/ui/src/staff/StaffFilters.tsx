import React from 'react';
import { View } from 'react-native';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';
import { useTheme } from '../theme/ThemeContext';
import type { StaffEmploymentStatusFilterUi, StaffGenderFilterUi } from './types';

type Chip<T extends string> = { value: T; label: string };

export interface StaffFiltersProps {
  departmentId: number | null;
  staffCategoryId: number | null;
  role: string | null;
  employmentStatus: StaffEmploymentStatusFilterUi;
  gender: StaffGenderFilterUi;
  departmentOptions: Array<{ value: number; label: string }>;
  categoryOptions: Array<{ value: number; label: string }>;
  roleOptions: Array<{ value: string; label: string }>;
  employmentStatusOptions: Array<{ value: StaffEmploymentStatusFilterUi; label: string }>;
  genderOptions: Array<{ value: StaffGenderFilterUi; label: string }>;
  onDepartmentChange: (value: number | null) => void;
  onCategoryChange: (value: number | null) => void;
  onRoleChange: (value: string | null) => void;
  onEmploymentStatusChange: (value: StaffEmploymentStatusFilterUi) => void;
  onGenderChange: (value: StaffGenderFilterUi) => void;
}

function StringFilterRow<T extends string>({
  label,
  chips,
  selected,
  onSelect,
}: {
  label: string;
  chips: Chip<T>[];
  selected: T | null;
  onSelect: (v: T | null) => void;
}) {
  return (
    <FilterChipRow label={label}>
      {chips.map((chip) => {
        const active =
          selected === chip.value || (selected == null && chip.value === ('all' as T));
        return (
          <FilterChip
            key={String(chip.value)}
            label={chip.label}
            active={active}
            onPress={() => onSelect(chip.value === ('all' as T) ? null : chip.value)}
          />
        );
      })}
    </FilterChipRow>
  );
}

function IdFilterRow({
  label,
  chips,
  selected,
  onSelect,
}: {
  label: string;
  chips: Array<{ value: number; label: string }>;
  selected: number | null;
  onSelect: (v: number | null) => void;
}) {
  const allChips: Chip<string>[] = [
    { value: 'all', label: 'All' },
    ...chips.map((c) => ({ value: String(c.value), label: c.label })),
  ];

  return (
    <StringFilterRow
      label={label}
      chips={allChips}
      selected={selected == null ? 'all' : String(selected)}
      onSelect={(v) => onSelect(v == null || v === 'all' ? null : Number(v))}
    />
  );
}

function RoleFilterRow({
  label,
  chips,
  selected,
  onSelect,
}: {
  label: string;
  chips: Array<{ value: string; label: string }>;
  selected: string | null;
  onSelect: (v: string | null) => void;
}) {
  const allChips: Chip<string>[] = [
    { value: 'all', label: 'All' },
    ...chips.map((c) => ({ value: c.value, label: c.label })),
  ];

  return (
    <StringFilterRow
      label={label}
      chips={allChips}
      selected={selected ?? 'all'}
      onSelect={(v) => onSelect(v == null || v === 'all' ? null : v)}
    />
  );
}

export const StaffFilters: React.FC<StaffFiltersProps> = ({
  departmentId,
  staffCategoryId,
  role,
  employmentStatus,
  gender,
  departmentOptions,
  categoryOptions,
  roleOptions,
  employmentStatusOptions,
  genderOptions,
  onDepartmentChange,
  onCategoryChange,
  onRoleChange,
  onEmploymentStatusChange,
  onGenderChange,
}) => {
  const { spacing } = useTheme();

  const empChips: Chip<StaffEmploymentStatusFilterUi>[] = employmentStatusOptions.map((o) => ({
    value: o.value,
    label: o.label,
  }));

  const genderChips: Chip<StaffGenderFilterUi>[] = genderOptions.map((o) => ({
    value: o.value,
    label: o.label,
  }));

  return (
    <View style={{ marginBottom: spacing.md }}>
      <IdFilterRow
        label="Department"
        chips={departmentOptions}
        selected={departmentId}
        onSelect={onDepartmentChange}
      />
      <IdFilterRow
        label="Category"
        chips={categoryOptions}
        selected={staffCategoryId}
        onSelect={onCategoryChange}
      />
      <RoleFilterRow label="Role" chips={roleOptions} selected={role} onSelect={onRoleChange} />
      <StringFilterRow
        label="Employment status"
        chips={empChips}
        selected={employmentStatus}
        onSelect={(v) => onEmploymentStatusChange((v ?? 'all') as StaffEmploymentStatusFilterUi)}
      />
      <StringFilterRow
        label="Gender"
        chips={genderChips}
        selected={gender}
        onSelect={(v) => onGenderChange((v ?? 'all') as StaffGenderFilterUi)}
      />
    </View>
  );
};
