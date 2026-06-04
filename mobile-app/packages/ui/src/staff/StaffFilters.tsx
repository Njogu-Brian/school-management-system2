import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
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

function FilterRow<T extends string>({
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
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <Text style={[styles.sectionLabel, { color: palette.textSecondary, fontSize: fontSizes.xs }]}>
        {label}
      </Text>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={[styles.row, { gap: spacing.xs, paddingVertical: spacing.xs }]}
      >
        {chips.map((chip) => {
          const active =
            selected === chip.value || (selected == null && chip.value === ('all' as T));
          return (
            <Pressable
              key={String(chip.value)}
              onPress={() => onSelect(chip.value === ('all' as T) ? null : chip.value)}
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
                  fontSize: fontSizes.sm,
                  fontWeight: active ? '600' : '400',
                }}
              >
                {chip.label}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>
    </View>
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
    <FilterRow
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
    <FilterRow
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
      <RoleFilterRow
        label="Role"
        chips={roleOptions}
        selected={role}
        onSelect={onRoleChange}
      />
      <FilterRow
        label="Employment status"
        chips={empChips}
        selected={employmentStatus}
        onSelect={(v) => onEmploymentStatusChange((v ?? 'all') as StaffEmploymentStatusFilterUi)}
      />
      <FilterRow
        label="Gender"
        chips={genderChips}
        selected={gender}
        onSelect={(v) => onGenderChange((v ?? 'all') as StaffGenderFilterUi)}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  sectionLabel: { fontWeight: '600', marginBottom: 2, textTransform: 'uppercase' },
  row: { flexDirection: 'row', alignItems: 'center' },
  chip: {
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
});
