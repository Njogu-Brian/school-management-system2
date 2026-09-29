import {
  marksMatrixDraftKey,
  queueOrExecute,
  SYNC_KINDS,
  useEnterMarksMatrix,
  useMarksMatrix,
  useNetworkStatus,
  useOfflineDraft,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  DockedActionLayout,
  FilterChip,
  FilterChipRow,
  FooterDock,
  MarksEntryProgress,
  MarksStudentCard,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Nav = StackNavigationProp<TeacherStackParamList>;
type Route = RouteProp<TeacherStackParamList, 'MarksMatrixEntry'>;

type EntryValue = { marks: string; remarks: string };
type MatrixDraft = {
  values: Record<string, EntryValue>;
  serverSnapshot: Record<string, EntryValue>;
};
type FocusId = 'all' | number;
type Phase = 'entry' | 'review' | 'success';

const keyOf = (studentId: number, examId: number) => `${studentId}-${examId}`;

export const MarksMatrixEntryScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const {
    examTypeId,
    classroomId,
    streamId,
    academicYearId,
    termId,
    examTypeName,
    classroomName,
    streamName,
    academicYearName,
    termName,
    selectedExamIds,
  } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const networkStatus = useNetworkStatus();

  const [values, setValues] = useState<Record<string, EntryValue>>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [search, setSearch] = useState('');
  const [hasLocalDraft, setHasLocalDraft] = useState(false);
  const [focusId, setFocusId] = useState<FocusId>('all');
  const [phase, setPhase] = useState<Phase>('entry');
  const [savedCount, setSavedCount] = useState(0);
  const didInitFocus = useRef(false);

  const serverSnapshotRef = useRef<Record<string, EntryValue>>({});
  const hydratedRef = useRef(false);
  const draftKey = marksMatrixDraftKey(examTypeId, classroomId, streamId);
  const { draft, setDraft, loaded: draftLoaded, clearDraft } = useOfflineDraft<MatrixDraft>(draftKey);
  const draftRef = useRef(draft);
  draftRef.current = draft;

  const matrixQuery = useMarksMatrix(
    {
      exam_type_id: examTypeId,
      classroom_id: classroomId,
      stream_id: streamId,
      academic_year_id: academicYearId,
      term_id: termId,
    },
    { enabled: true },
  );
  const saveMutation = useEnterMarksMatrix();

  const students = matrixQuery.data?.students ?? [];
  const allExams = matrixQuery.data?.exams ?? [];
  const exams = useMemo(() => {
    if (!selectedExamIds?.length) return allExams;
    const allowed = new Set(selectedExamIds);
    return allExams.filter((e) => allowed.has(e.id));
  }, [allExams, selectedExamIds]);

  useEffect(() => {
    if (!matrixQuery.data) return;
    const next: Record<string, EntryValue> = {};
    const snapshot: Record<string, EntryValue> = {};
    for (const m of matrixQuery.data.existing_marks) {
      const entry = {
        marks: m.marks == null ? '' : String(m.marks),
        remarks: m.remarks ?? '',
      };
      const k = keyOf(m.student_id, m.exam_id);
      next[k] = entry;
      snapshot[k] = entry;
    }
    serverSnapshotRef.current = snapshot;

    const savedDraft = draftLoaded ? draftRef.current : null;
    if (savedDraft?.values) {
      setValues({ ...next, ...savedDraft.values });
      serverSnapshotRef.current = savedDraft.serverSnapshot ?? snapshot;
      setHasLocalDraft(true);
    } else {
      setValues(next);
    }
    hydratedRef.current = true;
  }, [matrixQuery.data, draftLoaded]);

  useEffect(() => {
    if (!hydratedRef.current || students.length === 0) return;
    setDraft({ values, serverSnapshot: serverSnapshotRef.current });
  }, [values, students.length, setDraft]);

  useEffect(() => {
    if (didInitFocus.current || exams.length === 0) return;
    didInitFocus.current = true;
    setFocusId(exams.length === 1 ? exams[0].id : 'all');
  }, [exams]);

  const visibleExams = useMemo(() => {
    if (focusId === 'all') return exams;
    return exams.filter((e) => e.id === focusId);
  }, [exams, focusId]);

  const filteredStudents = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return students;
    return students.filter(
      (s) =>
        s.full_name.toLowerCase().includes(q) ||
        (s.admission_number ?? '').toLowerCase().includes(q),
    );
  }, [students, search]);

  const applyCell = (studentId: number, examId: number, field: keyof EntryValue, value: string) => {
    const k = keyOf(studentId, examId);
    if (field === 'marks') {
      const exam = exams.find((e) => e.id === examId);
      const max = exam?.max_marks ?? 100;
      const min = exam?.min_marks ?? 0;
      if (value.trim() === '') {
        setErrors((prev) => {
          const next = { ...prev };
          delete next[k];
          return next;
        });
      } else if (!/^\d+(\.\d+)?$/.test(value.trim())) {
        setErrors((prev) => ({ ...prev, [k]: 'Enter a valid number.' }));
      } else {
        const num = Number(value);
        if (num < min || num > max) {
          setErrors((prev) => ({ ...prev, [k]: `Maximum mark is ${max}.` }));
        } else {
          setErrors((prev) => {
            const next = { ...prev };
            delete next[k];
            return next;
          });
        }
      }
    }
    setValues((prev) => ({
      ...prev,
      [k]: { marks: prev[k]?.marks ?? '', remarks: prev[k]?.remarks ?? '', [field]: value },
    }));
    setHasLocalDraft(true);
  };

  const setCell = (studentId: number, examId: number, field: keyof EntryValue, value: string) => {
    applyCell(studentId, examId, field, value);
  };

  const changedFromSaved = useMemo(() => {
    const changes: { label: string; from: string; to: string }[] = [];
    for (const s of students) {
      for (const e of exams) {
        const k = keyOf(s.id, e.id);
        const prev = (serverSnapshotRef.current[k]?.marks ?? '').trim();
        const next = (values[k]?.marks ?? '').trim();
        if (prev !== '' && next !== '' && prev !== next) {
          changes.push({
            label: `${s.full_name} · ${e.subject_name || e.name}`,
            from: prev,
            to: next,
          });
        }
      }
    }
    return changes;
  }, [students, exams, values]);

  const enteredCount = useMemo(() => {
    let n = 0;
    for (const s of students) {
      for (const e of visibleExams) {
        if ((values[keyOf(s.id, e.id)]?.marks ?? '').trim()) n += 1;
      }
    }
    return n;
  }, [students, visibleExams, values]);

  const totalCells = students.length * Math.max(visibleExams.length, 0);
  const emptyCount = Math.max(totalCells - enteredCount, 0);

  const allEnteredCount = useMemo(() => {
    let n = 0;
    for (const s of students) {
      for (const e of exams) {
        if ((values[keyOf(s.id, e.id)]?.marks ?? '').trim()) n += 1;
      }
    }
    return n;
  }, [students, exams, values]);

  const allTotalCells = students.length * Math.max(exams.length, 0);

  const nonEmptyEntries = useMemo(() => {
    const entries: { student_id: number; exam_id: number; marks?: number; remarks?: string }[] = [];
    for (const s of students) {
      for (const e of exams) {
        const v = values[keyOf(s.id, e.id)];
        if (!v) continue;
        const hasScore = v.marks.trim() !== '';
        const hasRemark = v.remarks.trim() !== '';
        if (!hasScore && !hasRemark) continue;
        const markNum = hasScore ? Number(v.marks) : undefined;
        if (hasScore && Number.isNaN(markNum)) continue;
        if (hasScore && (markNum! < e.min_marks || markNum! > e.max_marks)) continue;
        entries.push({
          student_id: s.id,
          exam_id: e.id,
          marks: markNum,
          remarks: hasRemark ? v.remarks.trim() : undefined,
        });
      }
    }
    return entries;
  }, [students, exams, values]);

  const hasBlockingErrors = Object.keys(errors).length > 0;

  const contextLabel = [
    academicYearName,
    termName,
    classroomName ?? `Class #${classroomId}`,
    examTypeName ?? `Exam type #${examTypeId}`,
    streamName,
  ]
    .filter(Boolean)
    .join(' · ');

  const goReview = () => {
    if (nonEmptyEntries.length === 0) {
      showError('Nothing to save', 'Enter at least one score or remark.');
      return;
    }
    if (hasBlockingErrors) {
      showError('Fix invalid marks', 'Some values are above the maximum or not numbers.');
      return;
    }
    if (changedFromSaved.length > 0) {
      const sample = changedFromSaved
        .slice(0, 4)
        .map((c) => `${c.label}: ${c.from} → ${c.to}`)
        .join('\n');
      const more =
        changedFromSaved.length > 4 ? `\n…and ${changedFromSaved.length - 4} more` : '';
      Alert.alert(
        'Confirm mark changes',
        `You are changing saved marks. Senior teachers and admins will be notified.\n\n${sample}${more}`,
        [
          { text: 'Go back', style: 'cancel' },
          { text: 'Continue', onPress: () => setPhase('review') },
        ],
      );
      return;
    }
    setPhase('review');
  };

  const save = async () => {
    if (nonEmptyEntries.length === 0) {
      showError('Nothing to save', 'Enter at least one score or remark.');
      return;
    }
    if (hasBlockingErrors) {
      showError('Fix invalid marks', 'Some values are above the maximum or not numbers.');
      return;
    }

    const syncPayload = {
      exam_type_id: examTypeId,
      classroom_id: classroomId,
      stream_id: streamId,
      label: `Marks matrix · ${contextLabel}`,
      entries: nonEmptyEntries,
      baseSnapshot: serverSnapshotRef.current,
    };

    try {
      const result = await queueOrExecute(
        SYNC_KINDS.EXAM_MARKS_MATRIX,
        syncPayload,
        async () => {
          await saveMutation.mutateAsync({
            exam_type_id: examTypeId,
            classroom_id: classroomId,
            stream_id: streamId,
            entries: nonEmptyEntries,
          });
        },
        networkStatus,
        { label: syncPayload.label },
      );

      setSavedCount(nonEmptyEntries.length);
      if (result === 'queued') {
        showSuccess('Queued offline', 'Matrix marks will sync when you reconnect.');
      } else {
        showSuccess('Saved', 'Marks saved as draft.');
      }
      await clearDraft();
      setPhase('success');
    } catch (err) {
      showError('Error', (err as Error).message);
    }
  };

  if (phase === 'success') {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Marks saved" subtitle="Draft saved for review" onBack={() => navigation.navigate('MarksHub')} />
        <View style={[styles.card, { borderColor: palette.border, backgroundColor: palette.surface, padding: spacing.lg }]}>
          <Text style={{ color: colors.primary, fontWeight: '800', fontSize: 22, marginBottom: spacing.sm }}>✓ Marks saved</Text>
          <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }}>
            Your marks have been saved successfully{networkStatus === 'offline' ? ' (queued offline)' : ''}.
          </Text>
          <Text style={{ color: palette.textPrimary }}>Class: {classroomName ?? classroomId}</Text>
          <Text style={{ color: palette.textPrimary }}>
            Subjects: {exams.map((e) => e.subject_name || e.name).join(', ')}
          </Text>
          <Text style={{ color: palette.textPrimary }}>Students: {students.length}</Text>
          <Text style={{ color: palette.textPrimary, marginBottom: spacing.lg }}>Entries saved: {savedCount}</Text>
          <Button label="Back to Marks" onPress={() => navigation.navigate('MarksHub')} style={{ marginBottom: spacing.sm }} />
          <Button label="Enter another class" variant="secondary" onPress={() => navigation.navigate('MarksMatrixSetup')} />
        </View>
      </ScreenContainer>
    );
  }

  if (phase === 'review') {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Review marks" subtitle="Confirm before saving" onBack={() => setPhase('entry')} />
        <View style={[styles.card, { borderColor: palette.border, backgroundColor: palette.surface, padding: spacing.md }]}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Summary</Text>
          <Text style={{ color: palette.textSecondary }}>Year: {academicYearName ?? academicYearId ?? '—'}</Text>
          <Text style={{ color: palette.textSecondary }}>Term: {termName ?? termId ?? '—'}</Text>
          <Text style={{ color: palette.textSecondary }}>Exam: {examTypeName ?? examTypeId}</Text>
          <Text style={{ color: palette.textSecondary }}>Class: {classroomName ?? classroomId}</Text>
          <Text style={{ color: palette.textSecondary }}>
            Subjects: {exams.map((e) => e.subject_name || e.name).join(', ')}
          </Text>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.sm }}>
            Entries: {allEnteredCount} / {allTotalCells}
          </Text>
          {allTotalCells - allEnteredCount > 0 ? (
            <View style={[styles.warn, { backgroundColor: '#F59E0B22', marginTop: spacing.md }]}>
              <Text style={{ color: palette.textPrimary }}>
                {allTotalCells - allEnteredCount} marks are still empty. You can still save and continue later.
              </Text>
            </View>
          ) : null}
        </View>
        <Button
          label={networkStatus === 'offline' ? `Queue ${nonEmptyEntries.length} entries` : `Save ${nonEmptyEntries.length} marks`}
          onPress={() => void save()}
          loading={saveMutation.isPending}
          style={{ marginTop: spacing.lg }}
        />
        <Button label="Go back" variant="secondary" onPress={() => setPhase('entry')} style={{ marginTop: spacing.sm }} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} clearFloatingTabBar={matrixQuery.isLoading || exams.length === 0}>
      <DockedActionLayout
        header={
          <View style={{ padding: spacing.md, paddingBottom: 0 }}>
            <AcademicScreenHeader
              title={examTypeName ?? 'Bulk marks'}
              subtitle={[academicYearName, termName, classroomName, streamName].filter(Boolean).join(' · ') || 'Enter marks'}
              onBack={() => navigation.goBack()}
            />
            {matrixQuery.isLoading || exams.length === 0 ? null : (
              <>
                <MarksEntryProgress
                  entered={enteredCount}
                  total={totalCells}
                  draftSaved={hasLocalDraft}
                  offline={networkStatus === 'offline'}
                  contextLabel={hasLocalDraft ? `${contextLabel} · Draft saved` : contextLabel}
                />
                {emptyCount > 0 ? (
                  <Text style={{ color: palette.textSecondary, marginBottom: spacing.xs, fontSize: typography.caption.fontSize }}>
                    {emptyCount} marks still empty
                  </Text>
                ) : null}
                <TextField
                  label="Search students"
                  value={search}
                  onChangeText={setSearch}
                  placeholder="Name or admission #"
                />
                <FilterChipRow label="Subject focus" wrap>
                  {exams.length > 1 ? (
                    <FilterChip label={`All (${exams.length})`} active={focusId === 'all'} onPress={() => setFocusId('all')} />
                  ) : null}
                  {exams.map((e) => (
                    <FilterChip
                      key={e.id}
                      label={`${e.subject_name || e.name} / ${e.max_marks}`}
                      active={focusId === e.id}
                      onPress={() => setFocusId(e.id)}
                    />
                  ))}
                </FilterChipRow>
              </>
            )}
          </View>
        }
        footer={
          !matrixQuery.isLoading && exams.length > 0 ? (
            <FooterDock>
              <Button label="Review & Save" onPress={goReview} />
            </FooterDock>
          ) : null
        }
      >
        {matrixQuery.isLoading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : exams.length === 0 ? (
          <Text style={{ color: palette.textSecondary, textAlign: 'center', marginTop: 24, paddingHorizontal: spacing.md }}>
            No open exams in marking for the selected year, term and subjects.
          </Text>
        ) : (
          <FlatList
            data={filteredStudents}
            keyExtractor={(item) => String(item.id)}
            style={{ flex: 1, minHeight: 0 }}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={{ padding: spacing.md, paddingTop: spacing.sm, flexGrow: 1 }}
            renderItem={({ item: s, index: idx }) => (
              <MarksStudentCard
                index={idx + 1}
                fullName={s.full_name}
                admissionNumber={s.admission_number}
                slots={visibleExams.map((e) => {
                  const k = keyOf(s.id, e.id);
                  const v = values[k] ?? { marks: '', remarks: '' };
                  return {
                    keyId: k,
                    title: e.subject_name || e.name,
                    subtitle: e.subject_name ? e.name : undefined,
                    minMarks: e.min_marks,
                    maxMarks: e.max_marks,
                    marks: v.marks,
                    remarks: v.remarks,
                    onChangeMarks: (t) => setCell(s.id, e.id, 'marks', t),
                    onChangeRemarks: (t) => setCell(s.id, e.id, 'remarks', t),
                  };
                })}
              />
            )}
          />
        )}
      </DockedActionLayout>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12 },
  warn: { padding: 12, borderRadius: 8 },
});
