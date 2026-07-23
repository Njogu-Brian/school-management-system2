import {
  useConcernStaffOptions,
  useCreateConcern,
  useInfiniteStudentList,
  type ConcernCategory,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, ScrollView, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'ConcernCreate'>;

const CATEGORIES: ConcernCategory[] = [
  'financial',
  'academic',
  'teacher',
  'transport',
  'meals',
  'administration',
];

type SelectedStudent = { id: number; fullName: string };
type SelectedStaff = { id: number; fullName: string };

export const ConcernCreateScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography, radius } = useTheme();
  const createMutation = useCreateConcern();

  const [studentSearchDraft, setStudentSearchDraft] = useState('');
  const [studentSearch, setStudentSearch] = useState('');
  const [staffSearchDraft, setStaffSearchDraft] = useState('');
  const [staffSearch, setStaffSearch] = useState('');
  const [selectedStudents, setSelectedStudents] = useState<SelectedStudent[]>([]);
  const [selectedStaff, setSelectedStaff] = useState<SelectedStaff[]>([]);
  const [category, setCategory] = useState<ConcernCategory>('academic');
  const [description, setDescription] = useState('');

  const studentsQuery = useInfiniteStudentList(
    {
      search: studentSearch.trim() || undefined,
      classroomId: null,
      streamId: null,
      status: 'active',
      gender: 'all',
      perPage: 20,
    },
    { enabled: studentSearch.trim().length >= 1 },
  );
  const staffQuery = useConcernStaffOptions(staffSearch, {
    enabled: staffSearch.trim().length >= 2,
  });

  const students = useMemo(
    () => studentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [studentsQuery.data],
  );
  const staffItems = staffQuery.data ?? [];

  const toggleStudent = (item: SelectedStudent) => {
    setSelectedStudents((prev) =>
      prev.some((s) => s.id === item.id) ? prev.filter((s) => s.id !== item.id) : [...prev, item],
    );
  };

  const toggleStaff = (item: SelectedStaff) => {
    setSelectedStaff((prev) =>
      prev.some((s) => s.id === item.id) ? prev.filter((s) => s.id !== item.id) : [...prev, item],
    );
  };

  const submit = async () => {
    if (selectedStudents.length === 0 || !description.trim()) {
      showError('Missing fields', 'Search and select at least one student, then describe the concern.');
      return;
    }
    if (selectedStaff.length === 0) {
      showError('Select staff', 'Search and choose at least one concerned staff member to notify.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        student_ids: selectedStudents.map((s) => s.id),
        category,
        description: description.trim(),
        staff_ids: selectedStaff.map((s) => s.id),
      });
      const n = selectedStudents.length;
      showSuccess(
        n === 1 ? 'Raised' : `${n} concerns raised`,
        'Concerned staff notified.',
      );
      navigation.goBack();
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Could not raise concern.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing['3xl'] }}>
        <AcademicScreenHeader
          title="Raise concern"
          subtitle="Search students & staff — multi-select supported"
          onBack={() => navigation.goBack()}
        />

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
          Students
        </Text>
        <TextField
          label="Search student"
          value={studentSearchDraft}
          onChangeText={setStudentSearchDraft}
          placeholder="Name or admission #"
          returnKeyType="search"
          onSubmitEditing={() => setStudentSearch(studentSearchDraft.trim())}
        />
        <Button
          label="Search students"
          variant="secondary"
          onPress={() => setStudentSearch(studentSearchDraft.trim())}
          style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
        />
        {studentSearch.trim().length === 0 ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
            Use search to find students — the full list is not shown.
          </Text>
        ) : studentsQuery.isLoading ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>Searching…</Text>
        ) : students.length === 0 ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>No matches.</Text>
        ) : (
          students.slice(0, 20).map((s) => {
            const active = selectedStudents.some((x) => x.id === s.id);
            return (
              <Pressable
                key={s.id}
                onPress={() => toggleStudent({ id: s.id, fullName: s.fullName })}
                style={{
                  backgroundColor: active ? `${colors.primary}14` : palette.surfaceRaised,
                  borderColor: active ? colors.primary : palette.borderSubtle,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginTop: spacing.sm,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[s.admissionNumber, s.className].filter(Boolean).join(' · ')}
                  {active ? ' · Selected' : ''}
                </Text>
              </Pressable>
            );
          })
        )}

        {selectedStudents.length > 0 ? (
          <FilterChipRow label={`Selected students (${selectedStudents.length})`}>
            {selectedStudents.map((s) => (
              <FilterChip key={s.id} label={s.fullName} active onPress={() => toggleStudent(s)} />
            ))}
          </FilterChipRow>
        ) : null}

        <FilterChipRow label="Category">
          {CATEGORIES.map((c) => (
            <FilterChip key={c} label={c} active={category === c} onPress={() => setCategory(c)} />
          ))}
        </FilterChipRow>

        <TextField
          label="Problem raised by parent"
          value={description}
          onChangeText={setDescription}
          multiline
        />

        <Text
          style={{
            color: palette.textPrimary,
            fontWeight: '700',
            marginTop: spacing.md,
            marginBottom: spacing.xs,
          }}
        >
          Concerned staff
        </Text>
        <TextField
          label="Search staff"
          value={staffSearchDraft}
          onChangeText={setStaffSearchDraft}
          placeholder="Name or employee # (min 2 characters)"
          returnKeyType="search"
          onSubmitEditing={() => setStaffSearch(staffSearchDraft.trim())}
        />
        <Button
          label="Search staff"
          variant="secondary"
          onPress={() => setStaffSearch(staffSearchDraft.trim())}
          style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
        />
        {staffSearch.trim().length < 2 ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
            Search and select who should be notified.
          </Text>
        ) : staffQuery.isLoading ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>Searching…</Text>
        ) : staffItems.length === 0 ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>No matches.</Text>
        ) : (
          staffItems.map((s) => {
            const active = selectedStaff.some((x) => x.id === s.id);
            return (
              <Pressable
                key={s.id}
                onPress={() => toggleStaff({ id: s.id, fullName: s.fullName })}
                style={{
                  backgroundColor: active ? `${colors.primary}14` : palette.surfaceRaised,
                  borderColor: active ? colors.primary : palette.borderSubtle,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginTop: spacing.sm,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[s.employeeNumber, s.jobTitle].filter(Boolean).join(' · ')}
                  {active ? ' · Selected' : ' · Tap to tag'}
                </Text>
              </Pressable>
            );
          })
        )}

        {selectedStaff.length > 0 ? (
          <FilterChipRow label={`Tagged staff (${selectedStaff.length})`}>
            {selectedStaff.map((s) => (
              <FilterChip key={s.id} label={s.fullName} active onPress={() => toggleStaff(s)} />
            ))}
          </FilterChipRow>
        ) : null}

        <Button
          label={
            selectedStudents.length > 1
              ? `Save & notify (${selectedStudents.length})`
              : 'Save & notify'
          }
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.md }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
