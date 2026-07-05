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
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'MarksEntry'>;

type MarksDraft = {
  marks: Record<number, { marks: string; remarks: string }>;
  serverSnapshot: Record<number, { marks: string; remarks: string }>;
};

export const MarksEntryScreen: React.FC<Props> = ({ route, navigation }) => {
  const { examId, classroomId, subjectId, classroomName, subjectName } = route.params;
  const { colors, palette, spacing, fontSizes } = useTheme();
  const networkStatus = useNetworkStatus();
  const examQuery = useExamDetail(examId);
  const marksQuery = useMarks({ exam_id: examId, subject_id: subjectId, classroom_id: classroomId });
  const enterMarks = useEnterMarks();
  const [students, setStudents] = useState<Array<{ id: number; full_name: string }>>([]);
  const [marks, setMarks] = useState<Record<number, { marks: string; remarks: string }>>({});
  const [loadingStudents, setLoadingStudents] = useState(true);
  const [hasLocalDraft, setHasLocalDraft] = useState(false);

  const serverSnapshotRef = useRef<Record<number, { marks: string; remarks: string }>>({});
  const draftKey = marksDraftKey(examId, subjectId, classroomId);
  const { draft, setDraft, loaded: draftLoaded, clearDraft } = useOfflineDraft<MarksDraft>(draftKey);

  useEffect(() => {
    void (async () => {
      setLoadingStudents(true);
      try {
        const res = await studentsApi.list({ class_id: classroomId, per_page: 100 });
        if (res.success && res.data) {
          setStudents(res.data.data.map((s) => ({ id: s.id, full_name: s.full_name })));
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

    if (draftLoaded && draft?.marks) {
      setMarks({ ...map, ...draft.marks });
      serverSnapshotRef.current = draft.serverSnapshot ?? snapshot;
      setHasLocalDraft(true);
    } else {
      setMarks(map);
    }
  }, [marksQuery.data, draft, draftLoaded]);

  useEffect(() => {
    if (students.length === 0) return;
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

  const onSave = async () => {
    const payload = students
      .map((s) => {
        const entry = marks[s.id];
        const value = Number(entry?.marks);
        if (!entry?.marks || Number.isNaN(value)) return null;
        return {
          student_id: s.id,
          marks: value,
          remarks: entry.remarks || undefined,
          student_name: s.full_name,
        };
      })
      .filter(Boolean) as {
      student_id: number;
      marks: number;
      remarks?: string;
      student_name: string;
    }[];

    if (payload.length === 0) {
      Alert.alert('No marks', 'Enter at least one valid mark before saving.');
      return;
    }

    const syncPayload = {
      exam_id: examId,
      subject_id: subjectId,
      classroom_id: classroomId,
      label: `${examQuery.data?.name ?? `Exam #${examId}`} · ${subjectName}`,
      marks: payload.map(({ student_id, marks: m, remarks }) => ({ student_id, marks: m, remarks })),
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
        Alert.alert('Queued offline', 'Marks will sync when you reconnect.');
      } else {
        Alert.alert('Saved', 'Marks saved.');
        await clearDraft();
        navigation.goBack();
      }
    } catch (err) {
      Alert.alert('Save failed', (err as Error).message);
    }
  };

  const loading = examQuery.isLoading || marksQuery.isLoading || loadingStudents;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Enter marks"
          subtitle={`${classroomName} · ${subjectName}`}
          onBack={() => navigation.goBack()}
        />
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginBottom: spacing.md }}>
          {examQuery.data?.name ?? `Exam #${examId}`}
        </Text>
        {hasLocalDraft ? (
          <Text style={{ color: colors.primary, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
            Draft auto-saved on this device.
          </Text>
        ) : null}
        {loading ? (
          <ActivityIndicator color={colors.primary} />
        ) : (
          students.map((student) => (
            <View
              key={student.id}
              style={{
                marginBottom: spacing.md,
                borderBottomWidth: 1,
                borderBottomColor: palette.border,
                paddingBottom: spacing.sm,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600', marginBottom: spacing.xs }}>
                {student.full_name}
              </Text>
              <TextField
                label="Marks"
                value={marks[student.id]?.marks ?? ''}
                onChangeText={(v) => updateMark(student.id, 'marks', v)}
                keyboardType="numeric"
              />
              <TextField
                label="Remarks"
                value={marks[student.id]?.remarks ?? ''}
                onChangeText={(v) => updateMark(student.id, 'remarks', v)}
              />
            </View>
          ))
        )}
        <Button
          label={networkStatus === 'offline' ? 'Queue marks' : 'Save marks'}
          onPress={() => void onSave()}
          loading={enterMarks.isPending}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
