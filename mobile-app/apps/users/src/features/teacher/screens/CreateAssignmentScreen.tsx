import { useClassroomSubjects, useCreateHomework, useSettingsClasses } from '@erp/core';
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
import React, { useEffect, useState } from 'react';
import { ScrollView, Text } from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

export const CreateAssignmentScreen: React.FC = () => {
  const navigation = useNavigation();
  const { palette, spacing, typography } = useTheme();
  const classesQuery = useSettingsClasses();
  const createMutation = useCreateHomework();

  const [title, setTitle] = useState('');
  const [instructions, setInstructions] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const subjectsQuery = useClassroomSubjects(classroomId);

  useEffect(() => {
    setSubjectId(null);
  }, [classroomId]);

  const submit = async () => {
    if (!title.trim() || !dueDate || !classroomId || !subjectId) {
      showError('Missing fields', 'Title, due date, class, and subject are required.');
      return;
    }
    try {
      await createMutation.mutateAsync({
        title: title.trim(),
        instructions: instructions.trim() || undefined,
        due_date: dueDate,
        classroom_id: classroomId,
        subject_id: subjectId,
        target_scope: 'class',
      });
      showSuccess('Created', 'Homework is visible to parents and students.');
      navigation.goBack();
    } catch (err) {
      showError('Create failed', err instanceof Error ? err.message : 'Could not create homework.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Create homework" onBack={() => navigation.goBack()} />
        <TextField label="Title" value={title} onChangeText={setTitle} />
        <TextField label="Due date (YYYY-MM-DD)" value={dueDate} onChangeText={setDueDate} />
        <TextField label="Instructions" value={instructions} onChangeText={setInstructions} multiline />
        <FilterChipRow label="Class">
          {(classesQuery.data ?? []).slice(0, 30).map((c) => (
            <FilterChip
              key={c.id}
              label={c.name}
              active={classroomId === c.id}
              onPress={() => setClassroomId(c.id)}
            />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Subject you teach">
          {!classroomId ? null : (subjectsQuery.data ?? []).slice(0, 40).map((s) => (
            <FilterChip
              key={s.id}
              label={s.name}
              active={subjectId === s.id}
              onPress={() => setSubjectId(s.id)}
            />
          ))}
        </FilterChipRow>
        {!classroomId ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
            Select a class to see the subjects you teach.
          </Text>
        ) : null}
        <Button
          label="Publish homework"
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.md }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
