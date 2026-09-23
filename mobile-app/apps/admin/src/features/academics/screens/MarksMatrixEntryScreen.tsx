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
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<AcademicsStackParamList, 'MarksMatrixEntry'>;

type EntryValue = { marks: string; remarks: string };

type MatrixDraft = {
  values: Record<string, EntryValue>;
  serverSnapshot: Record<string, EntryValue>;
};

type FocusId = 'all' | number;

export const MarksMatrixEntryScreen: React.FC<Props> = ({ navigation, route }) => {
  const { examTypeId, classroomId, streamId, examTypeName, classroomName, streamName } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const networkStatus = useNetworkStatus();
  const [values, setValues] = useState<Record<string, EntryValue>>({});
  const [search, setSearch] = useState('');
  const [hasLocalDraft, setHasLocalDraft] = useState(false);
  const [focusId, setFocusId] = useState<FocusId>('all');
  const didInitFocus = useRef(false);

  const serverSnapshotRef = useRef<Record<string, EntryValue>>({});
  const hydratedRef = useRef(false);
  const draftKey = marksMatrixDraftKey(examTypeId, classroomId, streamId);
  const { draft, setDraft, loaded: draftLoaded, clearDraft } = useOfflineDraft<MatrixDraft>(draftKey);
  const draftRef = useRef(draft);
  draftRef.current = draft;

  const matrixQuery = useMarksMatrix(
    { exam_type_id: examTypeId, classroom_id: classroomId, stream_id: streamId },
    { enabled: true },
  );
  const saveMutation = useEnterMarksMatrix();

  const students = matrixQuery.data?.students ?? [];
  const exams = matrixQuery.data?.exams ?? [];

  const keyOf = (studentId: number, examId: number) => `${studentId}-${examId}`;

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

  // Start on the first subject for easiest bulk entry; user can still pick All.
  useEffect(() => {
    if (didInitFocus.current || exams.length === 0) return;
    didInitFocus.current = true;
    setFocusId(exams[0].id);
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

  const setCell = (studentId: number, examId: number, field: keyof EntryValue, value: string) => {
    const k = keyOf(studentId, examId);
    setValues((prev) => ({
      ...prev,
      [k]: { marks: prev[k]?.marks ?? '', remarks: prev[k]?.remarks ?? '', [field]: value },
    }));
    setHasLocalDraft(true);
  };

  const enteredCount = useMemo(() => {
    let n = 0;
    for (const s of students) {
      for (const e of visibleExams) {
        const v = values[keyOf(s.id, e.id)];
        if (v?.marks.trim()) n += 1;
      }
    }
    return n;
  }, [students, visibleExams, values]);

  const totalCells = students.length * Math.max(visibleExams.length, 0);

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

  const contextLabel = [
    classroomName ?? `Class #${classroomId}`,
    examTypeName ?? `Exam type #${examTypeId}`,
    streamName,
  ]
    .filter(Boolean)
    .join(' · ');

  const save = async () => {
    if (nonEmptyEntries.length === 0) {
      showError('Nothing to save', 'Enter at least one score or remark.');
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

      if (result === 'queued') {
        showSuccess('Queued offline', 'Matrix marks will sync when you reconnect.');
      } else {
        showSuccess('Success', 'Marks saved.');
        await clearDraft();
        navigation.goBack();
      }
    } catch (err) {
      showError('Error', (err as Error).message);
    }
  };

  return (
    <ScreenContainer
      scroll={false}
      style={{ flex: 1 }}
      clearFloatingTabBar={matrixQuery.isLoading || exams.length === 0}
    >
      <DockedActionLayout
        header={
          <View style={{ padding: spacing.md, paddingBottom: 0 }}>
            <AcademicScreenHeader
              title="Bulk marks entry"
              subtitle="One subject at a time, or all subjects — nothing hidden"
              onBack={() => navigation.goBack()}
            />
            {matrixQuery.isLoading || exams.length === 0 ? null : (
              <>
                <MarksEntryProgress
                  entered={enteredCount}
                  total={totalCells}
                  draftSaved={hasLocalDraft}
                  offline={networkStatus === 'offline'}
                  contextLabel={contextLabel}
                />
                <TextField
                  label="Search students"
                  value={search}
                  onChangeText={setSearch}
                  placeholder="Name or admission #"
                />
                <FilterChipRow label="Subject focus" wrap>
                  <FilterChip
                    label={`All (${exams.length})`}
                    active={focusId === 'all'}
                    onPress={() => setFocusId('all')}
                  />
                  {exams.map((e) => (
                    <FilterChip
                      key={e.id}
                      label={e.subject_name || e.name}
                      active={focusId === e.id}
                      onPress={() => setFocusId(e.id)}
                    />
                  ))}
                </FilterChipRow>
                <Text
                  style={{
                    color: palette.textSecondary,
                    fontSize: typography.caption.fontSize,
                    marginBottom: spacing.sm,
                  }}
                >
                  {filteredStudents.length} student{filteredStudents.length === 1 ? '' : 's'}
                  {focusId === 'all'
                    ? ` · showing all ${exams.length} subjects`
                    : ` · focused on ${visibleExams[0]?.subject_name ?? 'subject'}`}
                </Text>
              </>
            )}
          </View>
        }
        footer={
          !matrixQuery.isLoading && exams.length > 0 ? (
            <FooterDock>
              <Button
                label={
                  networkStatus === 'offline'
                    ? `Queue ${nonEmptyEntries.length} entries`
                    : `Save ${nonEmptyEntries.length} entries`
                }
                onPress={() => void save()}
                loading={saveMutation.isPending}
              />
            </FooterDock>
          ) : null
        }
      >
        {matrixQuery.isLoading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : exams.length === 0 ? (
          <Text
            style={{
              color: palette.textSecondary,
              textAlign: 'center',
              marginTop: 24,
              paddingHorizontal: spacing.md,
            }}
          >
            No open exams in marking status for this class and exam type.
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
