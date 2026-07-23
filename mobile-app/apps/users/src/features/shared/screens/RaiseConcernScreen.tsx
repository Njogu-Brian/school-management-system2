import {
  useCreateConcern,
  useCurrentUser,
  useInfiniteStudentList,
  type ConcernCategory,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Pressable, Text } from 'react-native';
import type { ConcernsSharedParamList } from '../types/concerns';
import { showError, showSuccess } from '../utils/feedback';

const CATEGORIES: Array<{ id: ConcernCategory; label: string }> = [
  { id: 'academic', label: 'Academic' },
  { id: 'teacher', label: 'Teacher' },
  { id: 'financial', label: 'Financial' },
  { id: 'transport', label: 'Transport' },
  { id: 'meals', label: 'Meals' },
  { id: 'administration', label: 'Admin' },
];

/**
 * Shared across every role (Teacher, Parent, Driver, Student). The student picker
 * is skipped when a `studentId` is passed in, or when the signed-in user is a
 * student themselves (`user.studentId`) — otherwise it lists students in scope
 * (server already scopes `/students` per role: own children, assigned classes, etc.).
 */
export const RaiseConcernScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ConcernsSharedParamList, 'RaiseConcern'>>();
  const { palette, spacing, typography, radius } = useTheme();
  const create = useCreateConcern();
  const user = useCurrentUser();

  const selfStudentId = user?.studentId ?? null;
  const [studentId, setStudentId] = useState<number | null>(
    route.params?.studentId ?? selfStudentId ?? null,
  );
  const [category, setCategory] = useState<ConcernCategory>('academic');
  const [description, setDescription] = useState('');

  const needsPicker = !route.params?.studentId && !selfStudentId;

  const listQuery = useInfiniteStudentList(
    {
      search: '',
      classroomId: null,
      streamId: null,
      status: 'active',
      perPage: 40,
    },
    { enabled: needsPicker },
  );
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const submit = async () => {
    if (!studentId) {
      showError('Select a student', 'Choose who this concern is about.');
      return;
    }
    if (!description.trim()) {
      showError('Description required', 'Please describe the concern.');
      return;
    }
    try {
      await create.mutateAsync({
        student_id: studentId,
        category,
        description: description.trim(),
      });
      showSuccess('Concern submitted', 'The school team will follow up.');
      navigation.goBack();
    } catch (err) {
      showError('Submit failed', err instanceof Error ? err.message : 'Could not create concern.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Raise concern" onBack={() => navigation.goBack()} />

      {needsPicker ? (
        <>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Student</Text>
          {students.length === 0 ? (
            <EmptyState title="No students" message="No students found in your scope." icon="people-outline" />
          ) : (
            students.map((s) => (
              <Pressable
                key={s.id}
                onPress={() => setStudentId(s.id)}
                style={{
                  backgroundColor: studentId === s.id ? `${palette.border}` : palette.surface,
                  borderColor: studentId === s.id ? palette.textPrimary : palette.border,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[s.admissionNumber, s.className].filter(Boolean).join(' · ')}
                </Text>
              </Pressable>
            ))
          )}
        </>
      ) : null}

      <FilterChipRow label="Category">
        {CATEGORIES.map((c) => (
          <FilterChip
            key={c.id}
            label={c.label}
            active={category === c.id}
            onPress={() => setCategory(c.id)}
          />
        ))}
      </FilterChipRow>

      <TextField
        label="Description"
        value={description}
        onChangeText={setDescription}
        placeholder="Describe what happened and how we can help"
        multiline
      />

      <Button
        label="Submit concern"
        loading={create.isPending}
        onPress={() => void submit()}
        style={{ marginTop: spacing.md }}
      />
    </ScreenContainer>
  );
};
