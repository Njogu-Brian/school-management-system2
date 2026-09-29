import {
  useAcademicContext,
  useClassroomSubjects,
  useClassrooms,
  useCreateLessonPlan,
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
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { ScrollView, Text } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const CreateLessonPlanScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography } = useTheme();
  const classesQuery = useClassrooms();
  const createMutation = useCreateLessonPlan();

  const today = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const tomorrow = useMemo(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
  }, []);

  const [title, setTitle] = useState('');
  const [plannedDate, setPlannedDate] = useState(today);
  const [outcomes, setOutcomes] = useState('');
  const [introduction, setIntroduction] = useState('');
  const [development, setDevelopment] = useState('');
  const [assessment, setAssessment] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [subjectId, setSubjectId] = useState<number | null>(null);
  const [yearId, setYearId] = useState<number | null>(null);
  const [termId, setTermId] = useState<number | null>(null);

  const academicQuery = useAcademicContext(yearId ?? undefined);
  const subjectsQuery = useClassroomSubjects(classroomId);

  const years = academicQuery.data?.academic_years ?? [];
  const terms = academicQuery.data?.terms ?? [];

  useEffect(() => {
    if (yearId != null) return;
    const active = academicQuery.data?.active_academic_year_id;
    if (active) setYearId(active);
  }, [academicQuery.data?.active_academic_year_id, yearId]);

  useEffect(() => {
    if (termId != null) return;
    const current = academicQuery.data?.current_term_id;
    if (current) setTermId(current);
  }, [academicQuery.data?.current_term_id, termId]);

  useEffect(() => {
    setSubjectId(null);
  }, [classroomId]);

  const submit = async () => {
    if (!title.trim() || !plannedDate || !classroomId || !subjectId || !yearId || !termId) {
      showError('Missing fields', 'Title, date, class, subject, year, and term are required.');
      return;
    }
    if (plannedDate !== today && plannedDate !== tomorrow) {
      showError('Invalid date', 'Plans can only be created for today or tomorrow.');
      return;
    }
    try {
      const created = await createMutation.mutateAsync({
        title: title.trim(),
        planned_date: plannedDate,
        classroom_id: classroomId,
        subject_id: subjectId,
        academic_year_id: yearId,
        term_id: termId,
        learning_outcomes: outcomes.trim() || undefined,
        introduction: introduction.trim() || undefined,
        lesson_development: development.trim() || undefined,
        assessment: assessment.trim() || undefined,
      });
      showSuccess('Draft saved', 'Open the plan to submit it for review.');
      navigation.replace('LessonPlanDetail', { lessonPlanId: created.id, topic: created.topic });
    } catch (err) {
      showError('Create failed', err instanceof Error ? err.message : 'Could not create lesson plan.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Create lesson plan" onBack={() => navigation.goBack()} />
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
          Drafts are limited to today or tomorrow. Year and term default to what is active in the system.
        </Text>
        <TextField label="Title / topic" value={title} onChangeText={setTitle} />
        <FilterChipRow label="Planned date">
          <FilterChip label={`Today (${today})`} active={plannedDate === today} onPress={() => setPlannedDate(today)} />
          <FilterChip
            label={`Tomorrow (${tomorrow})`}
            active={plannedDate === tomorrow}
            onPress={() => setPlannedDate(tomorrow)}
          />
        </FilterChipRow>
        <FilterChipRow label="Academic year" wrap>
          {years.map((y) => (
            <FilterChip
              key={y.id}
              label={`${y.label ?? y.year ?? y.id}${y.is_active ? ' (active)' : ''}`}
              active={yearId === y.id}
              onPress={() => {
                setYearId(y.id);
                setTermId(null);
              }}
            />
          ))}
        </FilterChipRow>
        {academicQuery.isError ? (
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
            Could not load academic year/term. Check your connection and try again.
          </Text>
        ) : null}
        <FilterChipRow label="Term" wrap>
          {terms.map((t) => (
            <FilterChip
              key={t.id}
              label={`${t.name}${t.is_current ? ' (current)' : ''}`}
              active={termId === t.id}
              onPress={() => setTermId(t.id)}
            />
          ))}
        </FilterChipRow>
        <FilterChipRow label="Your class" wrap>
          {(classesQuery.data ?? []).map((c) => (
            <FilterChip
              key={c.id}
              label={c.name}
              active={classroomId === c.id}
              onPress={() => setClassroomId(c.id)}
            />
          ))}
        </FilterChipRow>
        {classesQuery.isLoading ? (
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>Loading your classes…</Text>
        ) : null}
        {classesQuery.isError ? (
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
            Could not load your assigned classes.
          </Text>
        ) : null}
        <FilterChipRow label="Subject you teach in this class" wrap>
          {!classroomId
            ? null
            : (subjectsQuery.data ?? []).map((s) => (
                <FilterChip key={s.id} label={s.name} active={subjectId === s.id} onPress={() => setSubjectId(s.id)} />
              ))}
        </FilterChipRow>
        {classroomId && subjectsQuery.isLoading ? (
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>Loading subjects…</Text>
        ) : null}
        {classroomId && !subjectsQuery.isLoading && (subjectsQuery.data ?? []).length === 0 ? (
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
            No subjects assigned to you in this class.
          </Text>
        ) : null}
        <TextField label="Learning outcomes" value={outcomes} onChangeText={setOutcomes} multiline />
        <TextField label="Introduction" value={introduction} onChangeText={setIntroduction} multiline />
        <TextField label="Lesson development" value={development} onChangeText={setDevelopment} multiline />
        <TextField label="Assessment" value={assessment} onChangeText={setAssessment} multiline />
        <Button
          label="Save draft"
          onPress={() => void submit()}
          loading={createMutation.isPending}
          style={{ marginTop: spacing.md }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
