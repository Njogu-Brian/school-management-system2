import React, { useMemo } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { FilterChip } from '../primitives/FilterChip';
import { useTheme } from '../theme/ThemeContext';
export type StudentEnrollmentStatusFilter = 'all' | 'active' | 'fee_pending' | 'fee_cleared';
export type StudentGenderFilter = 'all' | 'male' | 'female' | 'other';

type Chip<T extends string> = { value: T; label: string };

export interface StudentFiltersProps {
  gradeLevel: number | string | null;
  classroomId: number | null;
  streamId: number | null;
  status: StudentEnrollmentStatusFilter;
  gender: StudentGenderFilter;
  gradeOptions: Array<{ value: number | string; label: string }>;
  classOptions: Array<{ value: number; label: string }>;
  streamOptions: Array<{ value: number; label: string }>;
  onGradeChange: (value: number | string | null) => void;
  onClassroomChange: (value: number | null) => void;
  onStreamChange: (value: number | null) => void;
  onStatusChange: (value: StudentEnrollmentStatusFilter) => void;
  onGenderChange: (value: StudentGenderFilter) => void;
}

const STATUS_CHIPS: Chip<StudentEnrollmentStatusFilter>[] = [
  { value: 'all', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'fee_pending', label: 'Fees pending' },
  { value: 'fee_cleared', label: 'Fees cleared' },
];

const GENDER_CHIPS: Chip<StudentGenderFilter>[] = [
  { value: 'all', label: 'All' },
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' },
];

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
  const { palette, typography, spacing } = useTheme();

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <Text
        style={[
          styles.sectionLabel,
          {
            color: palette.textMuted,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginBottom: spacing.xs,
          },
        ]}
      >
        {label.toUpperCase()}
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
            <FilterChip
              key={String(chip.value)}
              label={chip.label}
              active={active}
              onPress={() => onSelect(chip.value === ('all' as T) ? null : chip.value)}
            />
          );
        })}
      </ScrollView>
    </View>
  );
}

export const StudentFilters: React.FC<StudentFiltersProps> = ({
  gradeLevel,
  classroomId,
  streamId,
  status,
  gender,
  gradeOptions,
  classOptions,
  streamOptions,
  onGradeChange,
  onClassroomChange,
  onStreamChange,
  onStatusChange,
  onGenderChange,
}) => {
  const gradeChips = useMemo<Chip<string>[]>(
    () => [
      { value: 'all', label: 'All grades' },
      ...gradeOptions.map((g) => ({ value: String(g.value), label: g.label })),
    ],
    [gradeOptions],
  );

  const classChips = useMemo<Chip<string>[]>(
    () => [
      { value: 'all', label: 'All classes' },
      ...classOptions.map((c) => ({ value: String(c.value), label: c.label })),
    ],
    [classOptions],
  );

  const streamChips = useMemo<Chip<string>[]>(
    () => [
      { value: 'all', label: 'All streams' },
      ...streamOptions.map((s) => ({ value: String(s.value), label: s.label })),
    ],
    [streamOptions],
  );

  return (
    <View>
      <FilterRow
        label="Grade"
        chips={gradeChips}
        selected={gradeLevel != null ? String(gradeLevel) : null}
        onSelect={(v) => {
          if (v == null || v === 'all') onGradeChange(null);
          else {
            const match = gradeOptions.find((g) => String(g.value) === v);
            onGradeChange(match?.value ?? v);
          }
        }}
      />
      <FilterRow
        label="Class"
        chips={classChips}
        selected={classroomId != null ? String(classroomId) : null}
        onSelect={(v) => {
          if (v == null || v === 'all') onClassroomChange(null);
          else onClassroomChange(Number(v));
        }}
      />
      {streamOptions.length > 0 ? (
        <FilterRow
          label="Stream"
          chips={streamChips}
          selected={streamId != null ? String(streamId) : null}
          onSelect={(v) => {
            if (v == null || v === 'all') onStreamChange(null);
            else onStreamChange(Number(v));
          }}
        />
      ) : null}
      <FilterRow
        label="Status"
        chips={STATUS_CHIPS}
        selected={status}
        onSelect={(v) => onStatusChange((v ?? 'all') as StudentEnrollmentStatusFilter)}
      />
      <FilterRow
        label="Gender"
        chips={GENDER_CHIPS}
        selected={gender}
        onSelect={(v) => onGenderChange((v ?? 'all') as StudentGenderFilter)}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  sectionLabel: { fontWeight: '600', marginLeft: 2 },
  row: { flexDirection: 'row', alignItems: 'center' },
});
