import { useClassroomSubjects, useClassrooms, useCreateSpeedTest } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { Text } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const CreateSpeedTestScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography } = useTheme();
  const classesQuery = useClassrooms();
  const createMutation = useCreateSpeedTest();
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [questionCount, setQuestionCount] = useState('10');
  const [maxMarks, setMaxMarks] = useState('10');
  const subjectsQuery = useClassroomSubjects(classroomId);

  useEffect(() => {
    setSubjectId(null);
  }, [classroomId]);

  const submit = async () => {
    const questions = Number(questionCount);
    const marks = Number(maxMarks);
    if (!classroomId || !subjectId) {
      showError('Missing class', 'Choose the class and subject.');
      return;
    }
    if (!Number.isFinite(questions) || questions < 1) {
      showError('Questions', 'Enter how many questions this speed test has.');
      return;
    }
    if (!Number.isFinite(marks) || marks < 1) {
      showError('Marks', 'Enter the total marks for the test.');
      return;
    }
    try {
      const created = await createMutation.mutateAsync({
        classroom_id: classroomId,
        subject_id: subjectId,
        question_count: questions,
        max_marks: marks,
        title: title.trim() || undefined,
      });
      showSuccess('Created', 'Enter marks for each student.');
      navigation.replace('SpeedTestMarks', { batchKey: created.batch_key, title: created.title });
    } catch (err) {
      showError('Could not create', err instanceof Error ? err.message : 'Try again.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Create speed test" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary, marginBottom: spacing.md, fontSize: typography.caption.fontSize }}>
          Example: 5 questions in Mathematics, or 30 in CRE. Parents see results once you save marks.
        </Text>
        <TextField label="Title (optional)" value={title} onChangeText={setTitle} placeholder="Speed test — Mathematics" />
        <TextField
          label="Number of questions"
          value={questionCount}
          onChangeText={setQuestionCount}
          keyboardType="number-pad"
        />
        <TextField label="Total marks" value={maxMarks} onChangeText={setMaxMarks} keyboardType="number-pad" />
        <FilterChipRow label="Class" wrap>
          {(classesQuery.data ?? []).map((c) => (
            <FilterChip key={c.id} label={c.name} active={classroomId === c.id} onPress={() => setClassroomId(c.id)} />
          ))}
        </FilterChipRow>
        {classesQuery.isLoading ? (
          <Text style={{ color: palette.textMuted, marginBottom: spacing.sm }}>Loading classes…</Text>
        ) : null}
        {classesQuery.isError ? (
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
            Could not load your classes. Pull to refresh and try again.
          </Text>
        ) : null}
        <FilterChipRow label="Subject you teach" wrap>
          {!classroomId
            ? null
            : (subjectsQuery.data ?? []).map((s) => (
                <FilterChip key={s.id} label={s.name} active={subjectId === s.id} onPress={() => setSubjectId(s.id)} />
              ))}
        </FilterChipRow>
        <Button label="Create and enter marks" loading={createMutation.isPending} onPress={() => void submit()} />
    </ScreenContainer>
  );
};
