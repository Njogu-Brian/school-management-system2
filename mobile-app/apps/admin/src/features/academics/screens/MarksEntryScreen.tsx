import { studentsApi, useEnterMarks, useExamDetail, useMarks } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'MarksEntry'>;

export const MarksEntryScreen: React.FC<Props> = ({ route, navigation }) => {
  const { examId, classroomId, subjectId, classroomName, subjectName } = route.params;
  const { colors, palette, spacing, fontSizes } = useTheme();
  const examQuery = useExamDetail(examId);
  const marksQuery = useMarks({ exam_id: examId, subject_id: subjectId, classroom_id: classroomId });
  const enterMarks = useEnterMarks();
  const [students, setStudents] = useState<Array<{ id: number; full_name: string }>>([]);
  const [marks, setMarks] = useState<Record<number, { marks: string; remarks: string }>>({});
  const [loadingStudents, setLoadingStudents] = useState(true);

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
    rows.forEach((row) => {
      map[row.studentId] = { marks: String(row.marks ?? ''), remarks: row.remarks ?? '' };
    });
    setMarks(map);
  }, [marksQuery.data]);

  const onSave = async () => {
    const payload = students
      .map((s) => {
        const entry = marks[s.id];
        const value = Number(entry?.marks);
        if (!entry?.marks || Number.isNaN(value)) return null;
        return { student_id: s.id, marks: value, remarks: entry.remarks || undefined };
      })
      .filter(Boolean) as { student_id: number; marks: number; remarks?: string }[];

    if (payload.length === 0) {
      Alert.alert('No marks', 'Enter at least one valid mark before saving.');
      return;
    }

    try {
      const res = await enterMarks.mutateAsync({
        exam_id: examId,
        subject_id: subjectId,
        classroom_id: classroomId,
        marks: payload,
      });
      Alert.alert('Saved', res.message ?? 'Marks saved.');
      navigation.goBack();
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
        {loading ? (
          <ActivityIndicator color={colors.primary} />
        ) : (
          students.map((student) => (
            <View key={student.id} style={{ marginBottom: spacing.md, borderBottomWidth: 1, borderBottomColor: palette.border, paddingBottom: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600', marginBottom: spacing.xs }}>{student.full_name}</Text>
              <TextField
                label="Marks"
                value={marks[student.id]?.marks ?? ''}
                onChangeText={(v) => setMarks((prev) => ({ ...prev, [student.id]: { marks: v, remarks: prev[student.id]?.remarks ?? '' } }))}
                keyboardType="numeric"
              />
              <TextField
                label="Remarks"
                value={marks[student.id]?.remarks ?? ''}
                onChangeText={(v) => setMarks((prev) => ({ ...prev, [student.id]: { marks: prev[student.id]?.marks ?? '', remarks: v } }))}
              />
            </View>
          ))
        )}
        <Button label="Save marks" onPress={() => void onSave()} loading={enterMarks.isPending} />
      </ScrollView>
    </ScreenContainer>
  );
};
