import {
  useCreateConcern,
  useInfiniteStaffList,
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
import React, { useState } from 'react';
import { ScrollView } from 'react-native';
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

export const ConcernCreateScreen: React.FC<Props> = ({ navigation }) => {
  const { spacing } = useTheme();
  const createMutation = useCreateConcern();
  const [search, setSearch] = useState('');
  const studentsQuery = useInfiniteStudentList({
    search: search.trim() || undefined,
    classroomId: null,
    streamId: null,
    status: 'active',
    gender: 'all',
    perPage: 20,
  });
  const staffQuery = useInfiniteStaffList({
    departmentId: null,
    staffCategoryId: null,
    employmentStatus: 'all',
    gender: 'all',
    role: null,
    perPage: 25,
  });

  const students = studentsQuery.data?.pages.flatMap((p) => p.items) ?? [];
  const staffItems = staffQuery.data?.pages.flatMap((p) => p.items) ?? [];

  const [studentId, setStudentId] = useState<number | null>(null);
  const [category, setCategory] = useState<ConcernCategory>('academic');
  const [description, setDescription] = useState('');
  const [staffIds, setStaffIds] = useState<number[]>([]);

  const toggleStaff = (id: number) => {
    setStaffIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const submit = async () => {
    if (!studentId || !description.trim()) {
      showError('Missing fields', 'Select a student and enter the concern details.');
      return;
    }
    if (staffIds.length === 0) {
      showError('Select staff', 'Choose at least one concerned staff member to notify.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        student_id: studentId,
        category,
        description: description.trim(),
        staff_ids: staffIds,
      });
      showSuccess('Raised', 'Concern saved. Concerned staff notified.');
      navigation.goBack();
    } catch (e) {
      showError('Error', e instanceof Error ? e.message : 'Could not raise concern.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing['3xl'] }}>
        <AcademicScreenHeader title="Raise concern" onBack={() => navigation.goBack()} />
        <TextField label="Search student" value={search} onChangeText={setSearch} placeholder="Name or admission #" />
        <FilterChipRow label="Student">
          {students.slice(0, 15).map((s) => (
            <FilterChip
              key={s.id}
              label={s.fullName}
              active={studentId === s.id}
              onPress={() => setStudentId(s.id)}
            />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Category">
          {CATEGORIES.map((c) => (
            <FilterChip
              key={c}
              label={c}
              active={category === c}
              onPress={() => setCategory(c)}
            />
          ))}
        </FilterChipRow>
        <TextField
          label="Problem raised by parent"
          value={description}
          onChangeText={setDescription}
          multiline
        />
        <FilterChipRow label="Concerned staff">
          {staffItems.slice(0, 25).map((s) => (
            <FilterChip
              key={s.id}
              label={s.fullName}
              active={staffIds.includes(s.id)}
              onPress={() => toggleStaff(s.id)}
            />
          ))}
        </FilterChipRow>
        <Button
          label="Save & notify"
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.md }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
