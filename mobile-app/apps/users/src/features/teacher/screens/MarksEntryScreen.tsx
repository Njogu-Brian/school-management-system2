import {
  marksDraftKey,
  queueOrExecute,
  studentsApi,
  SYNC_KINDS,
  useEnterMarks,
  useExamDetail,
  useMarks,
  useNetworkStatus,
  useOfflineDraft,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  DockedActionLayout,
  FooterDock,
  MarksEntryProgress,
  MarksStudentCard,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, FlatList, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'MarksEntry'>;

type MarksDraft = {
  marks: Record<number, { marks: string; remarks: string }>;
  serverSnapshot: Record<number, { marks: string; remarks: string }>;
};

type StudentRow = { id: number; full_name: string; admission_number?: string | null };

export const MarksEntryScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { examId, classroomId, subjectId, classroomName, subjectName } = route.params;
  const { colors, spacing } = useTheme();
  const networkStatus = useNetworkStatus();
  const examQuery = useExamDetail(examId);
  const marksQuery = useMarks({ exam_id: examId, subject_id: subjectId, classroom_id: classroomId });
  const enterMarks = useEnterMarks();
  const [students, setStudents] = useState<StudentRow[]>([]);
  const [marks, setMarks] = useState<Record<number, { marks: string; remarks: string }>>({});
  const [search, setSearch] = useState('');
  const [loadingStudents, setLoadingStudents] = useState(true);
  const [hasLocalDraft, setHasLocalDraft] = useState(false);

  const serverSnapshotRef = useRef<Record<number, { marks: string; remarks: string }>>({});
  const hydratedRef = useRef(false);
  const draftKey = marksDraftKey(examId, subjectId, classroomId);
  const { draft, setDraft, loaded: draftLoaded, clearDraft } = useOfflineDraft<MarksDraft>(draftKey);
  const draftRef = useRef(draft);
  draftRef.current = draft;

  const maxMarks = examQuery.data?.totalMarks ?? 100;
  const minMarks = 0;

  useEffect(() => {
    void (async () => {
      setLoadingStudents(true);
      try {
        const res = await studentsApi.list({ class_id: classroomId, per_page: 100 });
        if (res.success && res.data) {
          setStudents(
            res.data.data.map((s) => ({
              id: s.id,
              full_name: s.full_name,
              admission_number: s.admission_number,
            })),
          );
        }
      } finally {
        setLoadingStudents(false);
      }
    })();
  }, [classroomId]);

  useEffect(() => {
    const rows = marksQuery.data ?? [];
    const map: Record<number, { marks: string; remarks: string }> = {};
    const snapshot: Record<number, { marks: string; remarks: string }> = {};
    rows.forEach((row) => {
      const entry = { marks: String(row.marks ?? ''), remarks: row.remarks ?? '' };
      map[row.studentId] = entry;
      snapshot[row.studentId] = entry;
    });
    serverSnapshotRef.current = snapshot;

    const savedDraft = draftLoaded ? draftRef.current : null;
    if (savedDraft?.marks) {
      setMarks({ ...map, ...savedDraft.marks });
      serverSnapshotRef.current = savedDraft.serverSnapshot ?? snapshot;
      setHasLocalDraft(true);
    } else {
      setMarks(map);
    }
    hydratedRef.current = true;
  }, [marksQuery.data, draftLoaded]);

  useEffect(() => {
    if (!hydratedRef.current || students.length === 0) return;
    setDraft({ marks, serverSnapshot: serverSnapshotRef.current });
  }, [marks, students.length, setDraft]);

  const updateMark = (studentId: number, field: 'marks' | 'remarks', value: string) => {
    setMarks((prev) => ({
      ...prev,
      [studentId]: {
        marks: field === 'marks' ? value : (prev[studentId]?.marks ?? ''),
        remarks: field === 'remarks' ? value : (prev[studentId]?.remarks ?? ''),
      },
    }));
    setHasLocalDraft(true);
  };

  const filteredStudents = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return students;
    return students.filter(
      (s) =>
        s.full_name.toLowerCase().includes(q) ||
        (s.admission_number ?? '').toLowerCase().includes(q),
    );
  }, [students, search]);

  const enteredCount = useMemo(
    () => students.filter((s) => (marks[s.id]?.marks ?? '').trim() !== '').length,
    [students, marks],
  );

  const onSave = async () => {
    const payload = students
      .map((s) => {
        const entry = marks[s.id];
        const value = Number(entry?.marks);
        if (!entry?.marks || Number.isNaN(value)) {
          if (entry?.remarks?.trim()) {
            return {
              student_id: s.id,
              marks: 0,
              remarks: entry.remarks.trim(),
            };
          }
          return null;
        }
        return {
          student_id: s.id,
          marks: value,
          remarks: entry.remarks || undefined,
        };
      })
      .filter(Boolean) as { student_id: number; marks: number; remarks?: string }[];

    if (payload.length === 0) {
      showError('No marks', 'Enter at least one valid mark before saving.');
      return;
    }

    const syncPayload = {
      exam_id: examId,
      subject_id: subjectId,
      classroom_id: classroomId,
      label: `${examQuery.data?.name ?? `Exam #${examId}`} · ${subjectName}`,
      marks: payload,
      baseSnapshot: serverSnapshotRef.current,
    };

    try {
      const result = await queueOrExecute(
        SYNC_KINDS.EXAM_MARKS_BATCH,
        syncPayload,
        async () => {
          await enterMarks.mutateAsync({
            exam_id: examId,
            subject_id: subjectId,
            classroom_id: classroomId,
            marks: syncPayload.marks,
          });
        },
        networkStatus,
        { label: syncPayload.label },
      );

      if (result === 'queued') {
        showSuccess('Queued offline', 'Marks will sync when you reconnect.');
      } else {
        showSuccess('Saved', 'Marks saved.');
        await clearDraft();
        navigation.goBack();
      }
    } catch (err) {
      showError('Save failed', (err as Error).message);
    }
  };

  const loading = examQuery.isLoading || marksQuery.isLoading || loadingStudents;
  const contextLabel = [
    classroomName,
    subjectName,
    examQuery.data?.name ?? `Exam #${examId}`,
    `Max ${maxMarks}`,
  ]
    .filter(Boolean)
    .join(' · ');

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} clearFloatingTabBar={false}>
      <DockedActionLayout
        header={
          <View style={{ padding: spacing.md, paddingBottom: 0 }}>
            <AcademicScreenHeader
              title="Enter marks"
              subtitle="Full roster — score, % and remarks on every row"
              onBack={() => navigation.goBack()}
            />
            {!loading ? (
              <>
                <MarksEntryProgress
                  entered={enteredCount}
                  total={students.length}
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
              </>
            ) : null}
          </View>
        }
        footer={
          <FooterDock>
            <Button
              label={
                networkStatus === 'offline'
                  ? `Queue ${enteredCount} marks`
                  : `Save ${enteredCount} marks`
              }
              onPress={() => void onSave()}
              loading={enterMarks.isPending}
            />
          </FooterDock>
        }
      >
        {loading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : (
          <FlatList
            data={filteredStudents}
            keyExtractor={(item) => String(item.id)}
            style={{ flex: 1, minHeight: 0 }}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={{ padding: spacing.md, paddingTop: spacing.sm, flexGrow: 1 }}
            ListEmptyComponent={
              <View style={{ padding: spacing.lg }}>
                <ActivityIndicator color={colors.primary} />
              </View>
            }
            renderItem={({ item: student, index }) => {
              const entry = marks[student.id] ?? { marks: '', remarks: '' };
              return (
                <MarksStudentCard
                  index={index + 1}
                  fullName={student.full_name}
                  admissionNumber={student.admission_number}
                  slots={[
                    {
                      keyId: String(student.id),
                      title: subjectName,
                      subtitle: examQuery.data?.name,
                      minMarks,
                      maxMarks,
                      marks: entry.marks,
                      remarks: entry.remarks,
                      onChangeMarks: (v) => updateMark(student.id, 'marks', v),
                      onChangeRemarks: (v) => updateMark(student.id, 'remarks', v),
                    },
                  ]}
                />
              );
            }}
          />
        )}
      </DockedActionLayout>
    </ScreenContainer>
  );
};
